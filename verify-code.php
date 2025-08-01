<?php
session_start();
require_once 'config.php';

// Set timezone
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$email = $_GET['email'] ?? $_SESSION['reset_email'] ?? '';

if (empty($email)) {
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_code = str_replace(' ', '', trim($_POST['verification_code']));
    
    if (empty($verification_code)) {
        $error = "Please enter the verification code";
    } else {
        // Debug query
        $debug_stmt = $conn->prepare("SELECT admin_id, reset_token, reset_token_expires, NOW() as db_time FROM admin WHERE email = ?");
        $debug_stmt->bind_param("s", $email);
        $debug_stmt->execute();
        $debug_result = $debug_stmt->get_result();
        
        if ($debug_result->num_rows > 0) {
            $admin = $debug_result->fetch_assoc();
            
            // Manual verification for debugging
            $code_match = ($admin['reset_token'] === $verification_code);
            $not_expired = (strtotime($admin['reset_token_expires']) > time());
            
            if ($code_match && $not_expired) {
                // Generate new secure token
                $reset_token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                
                $update_stmt = $conn->prepare("UPDATE admin SET reset_token = ?, reset_token_expires = ? WHERE admin_id = ?");
                $update_stmt->bind_param("ssi", $reset_token, $expires, $admin['admin_id']);
                $update_stmt->execute();
                
                $_SESSION['reset_admin_id'] = $admin['admin_id'];
                header("Location: reset-password.php?token=" . urlencode($reset_token));
                exit();
            } else {
                $error = "Invalid or expired verification code. Please try again.";
            }
        } else {
            $error = "No verification request found for this email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Verify Your Email</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <p>Enter the 6-digit code sent to <strong><?= htmlspecialchars($email) ?></strong></p>
            
            <div class="form-group">
                <input 
                    type="text" 
                    name="verification_code" 
                    placeholder="123456" 
                    required
                    pattern="\d{6}"
                    title="6-digit number"
                    autocomplete="off"
                >
            </div>
            
            <button type="submit" class="login-button">Verify Code</button>
            
            <div class="links">
                <a href="forgot-password.php">Resend Code</a>
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>