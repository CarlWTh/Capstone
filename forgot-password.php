<?php
session_start();
require_once 'config.php'; 

require_once 'libraries/PHPMailer-master/src/Exception.php';
require_once 'libraries/PHPMailer-master/src/PHPMailer.php';
require_once 'libraries/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php"); 
    exit();
}
$error = '';
$success = '';
$debug_output = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, username, email FROM Admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            $verification_code = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $update_stmt = $conn->prepare("UPDATE Admin SET reset_token = ?, reset_token_expires = ? WHERE admin_id = ?");
            $update_stmt->bind_param("ssi", $verification_code, $expires, $admin['admin_id']);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
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
                    $mail->addAddress($email, $admin['username']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Admin Password Reset Verification Code for ' . SITE_NAME;
                    $mail->Body    = "Dear " . htmlspecialchars($admin['username']) . ",<br><br>" .
                                     "Your password reset verification code for " . SITE_NAME . " is: <br><br>" .
                                     "<strong>" . $verification_code . "</strong><br><br>" .
                                     "This code is valid for 1 hour. If you did not request this, please ignore this email.<br><br>" .
                                     "Regards,<br>" . SITE_NAME . " Support Team";
                    $mail->AltBody = "Your password reset verification code for " . SITE_NAME . " is: " . $verification_code .
                                     "\nThis code is valid for 1 hour. If you did not request this, please ignore this email.";

                    $mail->send();
                    header("Location: verify-code.php?email=" . urlencode($email));
                    exit();
                } catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Failed to generate reset token. Please try again.";
            }
            $update_stmt->close();
        } else {
            $success = "If that email exists in our system, a verification code has been sent.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST" action="forgot-password.php">
            <h2>Forgot Admin Password</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php else: ?>
                <p>Enter your admin email to receive a verification code</p>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="login-button">Send Code</button>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Admin Login</a>
            </p>
        </form>
    </div>
</body>
</html>
