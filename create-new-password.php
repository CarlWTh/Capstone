<?php
session_start();
require_once 'config.php';

// Ensure the user has gone through the password reset process
if (!isset($_SESSION['reset_admin_id']) || !isset($_SESSION['reset_admin_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Retrieve admin_id from the session
    $admin_id = $_SESSION['reset_admin_id'];

    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Update the admin's password using their ID
        // Also clear any reset tokens to prevent reuse
        $stmt = $conn->prepare("UPDATE Admin SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE admin_id = ?");
        // Check if the prepare statement was successful
        if ($stmt === false) {
            $error = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("si", $hashed_password, $admin_id); // 's' for string (hashed_password), 'i' for integer (admin_id)
            if ($stmt->execute()) {
            $success = "Password has been reset successfully. You may now log in.";
            // Clear session variables after successful reset for security
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit();
            } else {
            $error = "Error updating password: " . $stmt->error; // More specific error message
            }
            $stmt->close();
        }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Reset Your Password</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="login-button">Reset Password</button>
            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Admin Login</a>
            </p>
        </form>
    </div>
</body>
</html>