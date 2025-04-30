<?php
session_start();
require_once 'config.php';

// Include PHPMailer files
require_once 'libraries/PHPMailer-master/src/Exception.php';
require_once 'libraries/PHPMailer-master/src/PHPMailer.php';
require_once 'libraries/PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
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
        // Check if the email exists
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate verification code
            $verification_code = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration
            
            // Store in database
            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $verification_code, $expires, $user['id']);
            $update_stmt->execute();
            
            if ($update_stmt->affected_rows === 1) {
                // Send email
                $mail = new PHPMailer(true);
                
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USERNAME;
                    $mail->Password   = SMTP_PASSWORD;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;

                    // Recipients
                    $mail->setFrom(EMAIL_FROM, 'Bottle Recycling System');
                    $mail->addAddress($email, $user['username']);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Verification Code';
                    $mail->Body    = "Your verification code is: <strong>$verification_code</strong>";
                    $mail->AltBody = "Your verification code is: $verification_code";

                    $mail->send();
                    header("Location: verify-code.php?email=" . urlencode($email));
                    exit();
                } catch (Exception $e) {
                    $error = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $error = "Failed to generate reset token";
            }
            $update_stmt->close();
        } else {
            // Don't reveal if email exists
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Forgot Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php else: ?>
                <p>Enter your email to receive a verification code</p>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="login-button">Send Code</button>
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Login</a>
            </p>
        </form>
    </div>
</body>
</html>