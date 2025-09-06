<?php
require_once '../../../private/config/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code - <?= htmlspecialchars(SITE_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Verify Your Admin Email</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($resend_message)): ?>
                <div class="success-message"><?= htmlspecialchars($resend_message) ?></div>
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
                <a href="verify-code.php?resend=true">Resend Code</a>
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
</body>
</html>
