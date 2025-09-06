<?php
require_once '../../../private/config/config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../css/login.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" id="loginForm" autocomplete="off">
            <h2>Admin Login</h2>
            <div class="error-message" id="errorMsg" style="display:none;"></div>
            <div class="success-message" id="successMsg" style="display:none;"></div>
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                >
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password" 
                    required
                >
            </div>
            <div class="form-extras">
                <div class="remember-forgot">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Remember Me
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
            </div>
            <button type="submit" class="login-button">
                Login
            </button>
        </form>
    </div>
    <script src="../../js/login.js"></script>
</body>
</html>