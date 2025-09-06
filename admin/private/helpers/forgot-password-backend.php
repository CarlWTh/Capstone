<?php
session_start();
require_once __DIR__ . '/../config/config.php'; // Adjusted to go up from helpers to private, then into config

require_once __DIR__ . '/../libraries/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../libraries/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../libraries/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php"); 
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address";
    } else {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT admin_id, username, email FROM Admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Generate verification code and set expiration
            $verification_code = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Update database with reset token
            $update_stmt = $conn->prepare("UPDATE Admin SET reset_token = ?, reset_token_expires = ? WHERE admin_id = ?");
            $update_stmt->bind_param("ssi", $verification_code, $expires, $admin['admin_id']);
            $update_stmt->execute();

            if ($update_stmt->affected_rows === 1) {
                // Send email with verification code
                $mail = new PHPMailer(true);
                try {
                    // SMTP configuration
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                    $mail->Port       = SMTP_PORT;

                    // Email configuration
                    $mail->setFrom(EMAIL_FROM, SITE_NAME . ' Admin'); 
                    $mail->addAddress($email, $admin['username']);
                    $mail->isHTML(true);
                    $mail->Subject = 'Admin Password Reset Verification Code for ' . SITE_NAME;
                    
                    // Email body
                    $mail->Body = "Dear " . htmlspecialchars($admin['username']) . ",<br><br>" .
                                  "Your password reset verification code for " . SITE_NAME . " is: <br><br>" .
                                  "<strong>" . $verification_code . "</strong><br><br>" .
                                  "This code is valid for 1 hour. If you did not request this, please ignore this email.<br><br>" .
                                  "Regards,<br>" . SITE_NAME . " Support Team";
                    
                    $mail->AltBody = "Your password reset verification code for " . SITE_NAME . " is: " . $verification_code .
                                     "\nThis code is valid for 1 hour. If you did not request this, please ignore this email.";

                    $mail->send();
                    
                    // Redirect to verification page on success
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
            // Security: Don't reveal if email exists or not
            $success = "If that email exists in our system, a verification code has been sent.";
        }
        $stmt->close();
    }
}
?>