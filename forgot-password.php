<?php
session_start();
require_once 'config.php';

// Include PHPMailer files from the libraries folder
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
$debug_output = ''; // Variable for debugging information

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address";
    } else {
        // Check if the email exists in the database
        $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Generate a 6-digit verification code
            $verification_code = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Code expires in 1 hour
            
            // For debugging
            $debug_output = "Generated code: $verification_code for email: $email<br>";
            $debug_output .= "Expiration time: $expires<br>";
            
            // Store the code in the database
            $update_stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $update_stmt->bind_param("ssi", $verification_code, $expires, $user['id']);
            $update_stmt->execute();
            
            if ($update_stmt->affected_rows === 1) {
                $debug_output .= "Code successfully stored in database.<br>";
            } else {
                $debug_output .= "Error storing code in database.<br>";
            }
            
            $update_stmt->close();
            
            // Send the verification code email
            $mail = new PHPMailer(true);
            
            try {
                // Configure PHPMailer
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = 'tls'; // or 'ssl'
                $mail->Port = SMTP_PORT;
                
                // Recipients
                $mail->setFrom(EMAIL_FROM, 'Bottle Recycling System');
                $mail->addAddress($email, $user['username']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Verification Code';
                
                $mail->Body = "
                    <p>Hello {$user['username']},</p>
                    <p>You recently requested to reset your password for your Bottle Recycling System account.</p>
                    <p>Your verification code is: <strong>{$verification_code}</strong></p>
                    <p>This code will expire in 1 hour.</p>
                    <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                    <p>Regards,<br>Bottle Recycling System Team</p>
                ";
                
                $mail->AltBody = "
                    Hello {$user['username']},
                    
                    You recently requested to reset your password for your Bottle Recycling System account.
                    
                    Your verification code is: {$verification_code}
                    
                    This code will expire in 1 hour.
                    
                    If you did not request a password reset, please ignore this email or contact support if you have concerns.
                    
                    Regards,
                    Bottle Recycling System Team
                ";
                
                $mail->send();
                $debug_output .= "Email sent successfully.<br>";
                
                // Redirect to verification page
                header("Location: verify-code.php?email=" . urlencode($email));
                exit();
                
            } catch (Exception $e) {
                $error = "Could not send verification code. Please try again later or contact support.";
                $debug_output .= "Email error: " . $mail->ErrorInfo . "<br>";
            }
            
        } else {
            // Don't reveal that the email doesn't exist for security reasons
            $success = "If that email address is in our system, a verification code has been sent to it.";
            $debug_output .= "Email not found in database: $email<br>";
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
    <title>Forgot Password - Bottle Recycling System</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            color: #333;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #007bff;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST" action="forgot-password.php">
            <h2>Forgot Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php else: ?>
                <p>Enter your email address and we'll send you a verification code to reset your password.</p>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="Enter your email address" 
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                    <i class="icon-envelope"></i>
                </div>
                
                <button type="submit" class="login-button">
                    Send Verification Code
                </button>
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Login</a>
            </p>
            
            <?php if (!empty($debug_output)): ?>
                <div class="debug-info">
                    <h3>Debug Information</h3>
                    <?php echo $debug_output; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>