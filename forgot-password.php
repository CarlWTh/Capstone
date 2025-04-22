<?php
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

            // Store token in database
            $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->bind_param("sss", $token, $expires, $email);
            $stmt->execute();

            // In a real application, you would send an email with a reset link
            // For this example, we'll just show the reset link
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
            $message = "Password reset link: <a href='" . htmlspecialchars($reset_link) . "'>Reset Password</a>";
        } else {
            $message = "If that email exists in our system, we've sent a password reset link";
            // Don't reveal whether email exists or not for security
        }
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
</head>

<body class="login-body">
    <div class="login-container">
        <form class="forgot-password-form" method="POST" action="forgot-password.php">
            <h2>Forgot Password</h2>
            <p style="text-align: center; margin-bottom: 20px;">
                Enter your email to reset your password.
            </p>

            <?php if (!empty($message)): ?>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    required>
            </div>

            <button type="submit" class="login-button">
                Reset Password
            </button>
            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Login</a>
            </p>
        </form>
    </div>
</body>

</html>