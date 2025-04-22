<?php
require_once 'config.php';

$error = '';
$success = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $error = "Invalid or expired token. Please request a new password reset link.";
    }
    $stmt->close();
} else {
    $error = "No token provided";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in both password fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Update password and clear reset token
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $password_hash, $token);

        if ($stmt->execute()) {
            $success = "Password updated successfully. You can now <a href='login.php'>login</a> with your new password.";
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
    <link rel="stylesheet" href="/css/styles.css">
</head>

<body class="login-body">
    <div class="login-container">
        <form class="reset-password-form" method="POST" action="reset-password.php?token=<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <h2>Reset Password</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <p><?php echo $success; ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($success) && empty($error) && isset($_GET['token'])): ?>
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter new password"
                        required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm new password"
                        required>
                </div>

                <button type="submit" class="login-button">
                    Reset Password
                </button>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Login</a>
            </p>
        </form>
    </div>
</body>

</html>