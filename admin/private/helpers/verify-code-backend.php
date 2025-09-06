<?php
session_start();
require_once '../config.php';

// ✅ Include PHPMailer
require '../libraries/PHPMailer-master/src/Exception.php';
require '../libraries/PHPMailer-master/src/PHPMailer.php';
require '../libraries/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

// ✅ Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

// ✅ Set email from session or GET
if (!isset($_SESSION['reset_admin_email'])) {
    if (!empty($_GET['email'])) {
        $_SESSION['reset_admin_email'] = $_GET['email'];
    } else {
        header("Location: forgot-password.php");
        exit();
    }
}

$email = $_SESSION['reset_admin_email'];
$error = '';
$resend_message = '';

// ✅ Display flash message for resend
if (isset($_SESSION['resend_message'])) {
    $resend_message = $_SESSION['resend_message'];
    unset($_SESSION['resend_message']);
}

// ✅ Handle Resend
if (isset($_GET['resend']) && $_GET['resend'] === 'true') {
    $new_token = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $conn->prepare("UPDATE Admin SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
    $stmt->bind_param("sss", $new_token, $expires, $email);
    $stmt->execute();
    $stmt->close();

    // Send the email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(EMAIL_FROM, SITE_NAME . ' Admin');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Your New Verification Code';
        $mail->Body    = "<p>Your new 6-digit code is: <strong>$new_token</strong></p><p>This code will expire in 1 hour.</p>";
        $mail->send();

        // ✅ Store message and redirect to avoid resubmission
        $_SESSION['resend_message'] = "A new verification code has been sent to your email.";
        header("Location: verify-code.php");
        exit();
    } catch (Exception $e) {
        $error = "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
    }
}

// ✅ Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = str_replace(' ', '', trim($_POST['verification_code'] ?? ''));

    if (empty($verification_code)) {
        $error = "Please enter the verification code.";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, reset_token, reset_token_expires FROM Admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            $is_valid = ($admin['reset_token'] === $verification_code);
            $not_expired = (strtotime($admin['reset_token_expires']) > time());

            if ($is_valid && $not_expired) {
                // ✅ Create new secure token for password reset
                $new_token = bin2hex(random_bytes(32));
                $new_expiry = date('Y-m-d H:i:s', time() + 3600);

                $update_stmt = $conn->prepare("UPDATE Admin SET reset_token = ?, reset_token_expires = ? WHERE admin_id = ?");
                $update_stmt->bind_param("ssi", $new_token, $new_expiry, $admin['admin_id']);
                $update_stmt->execute();
                $update_stmt->close();

                $_SESSION['reset_admin_id'] = $admin['admin_id'];

                header("Location: create-new-password.php?token=" . urlencode($new_token));
                exit();
            } else {
                $error = "Invalid or expired verification code.";
            }
        } else {
            $error = "No verification request found for this email.";
        }
        $stmt->close();
    }
}
?>