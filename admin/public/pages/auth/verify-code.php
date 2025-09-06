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
        <form class="login-form" id="verifyForm" autocomplete="off">
            <h2>Verify Your Admin Email</h2>
            <div id="error-message" class="error-message" style="display:none;"></div>
            <div id="success-message" class="success-message" style="display:none;"></div>
            <p>Enter the 6-digit code sent to your email.</p>
            <div class="form-group">
                <input
                    type="text"
                    name="verification_code"
                    id="verification_code"
                    placeholder="123456"
                    required
                    pattern="\d{6}"
                    title="6-digit number"
                    autocomplete="off"
                >
            </div>
            <button type="submit" class="login-button">Verify Code</button>
            <div class="links">
                <a href="#" id="resend-link">Resend Code</a>
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('verifyForm');
        const errorDiv = document.getElementById('error-message');
        const successDiv = document.getElementById('success-message');
        const resendLink = document.getElementById('resend-link');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            const formData = new FormData(form);

            fetch('verify-code-backend.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    successDiv.textContent = "Code verified! Redirecting...";
                    successDiv.style.display = 'block';
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1000);
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(() => {
                errorDiv.textContent = "An error occurred. Please try again.";
                errorDiv.style.display = 'block';
            });
        });

        resendLink.addEventListener('click', function(e) {
            e.preventDefault();
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            fetch('verify-code-backend.php?resend=true')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    successDiv.textContent = data.message;
                    successDiv.style.display = 'block';
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(() => {
                errorDiv.textContent = "An error occurred. Please try again.";
                errorDiv.style.display = 'block';
            });
        });
    });
    </script>
</body>
</html>