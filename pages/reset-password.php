<?php
session_start();
require_once '../config.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}
$error = '';
$success = '';
$valid_token = false;
$token = '';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT admin_id FROM admin WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $valid_token = true;
    } else {
        $error = "Invalid or expired password reset link. Please request a new one.";
    }
    $stmt->close();
} else {
    $error = "No reset token provided. Please request a password reset from the forgot password page.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE admin SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $password_hash, $token);
        $stmt->execute();
        
        if ($stmt->affected_rows === 1) {

            header("Location: login.php?password_reset=success");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
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
    <title>Reset Password - Bottle Recycling System</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Reset Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($valid_token): ?>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your new password" 
                        required
                        minlength="8"
                    >
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        placeholder="Confirm your new password" 
                        required
                        minlength="8"
                    >
                </div>
                
                <button type="submit" class="login-button">
                    Reset Password
                </button>
            <?php else: ?>
                <p style="text-align: center;">
                    <a href="forgot-password.php">Request a new password reset link</a>
                </p>
            <?php endif; ?>
            
            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Login</a>
            </p>
        </form>
    </div>
</body>
</html>