<?php   
require_once '../../../private/helpers/forgot-password-backend.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="../../css/forgot-password.css">
</head>
<body class="forgot-body">
    <div class="forgot-container">
        <form class="forgot-form" method="POST" action="forgot-password.php">
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
                <button type="submit" class="code-button">Send Code</button>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 10px;">
                <a href="login.php">Back to Admin Login</a>
            </p>
        </form>
    </div>
</body>
</html>