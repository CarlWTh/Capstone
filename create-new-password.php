<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Ensure user has verified their code
if (!isset($_SESSION['reset_user_id'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['reset_user_id'];
$debug_output = '';

// Get user information for debugging
$user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$debug_output .= "User ID for password reset: $user_id<br>";
$debug_output .= "Username: " . ($user ? $user['username'] : "Unknown") . "<br>";
$user_stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Update the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        $stmt->execute();
        
        if ($stmt->affected_rows === 1) {
            $debug_output .= "Password updated successfully.<br>";
            
            // Clear session variables
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            
            // Password reset successful
            header("Location: login.php?password_reset=success");
            exit();
        } else {
            $error = "Failed to update password. Please try again.";
            $debug_output .= "Error updating password. Affected rows: " . $stmt->affected_rows . "<br>";
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
    <title>Create New Password - Bottle Recycling System</title>
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
        <form class="login-form" method="POST">
            <h2>Create New Password</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
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
                <i class="icon-lock"></i>
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
                <i class="icon-lock"></i>
            </div>
            
            <button type="submit" class="login-button">
                Reset Password
            </button>
            
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