<?php
session_start();
$error = isset($_SESSION['reset_error']) ? $_SESSION['reset_error'] : '';
$success = isset($_SESSION['reset_success']) ? $_SESSION['reset_success'] : '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../../css/styles.css">
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