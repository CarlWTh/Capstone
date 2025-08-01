<?php
session_start();

// Unset all session variables specific to admin
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['is_admin_session']);
unset($_SESSION['redirect_url']); // Clear any pending redirect URLs

// Unset any password reset related session variables
unset($_SESSION['reset_admin_id']);
unset($_SESSION['reset_admin_email']);

// Unset any flash messages
unset($_SESSION['flash_message']);


// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Delete remember me cookie for admin if it exists
if (isset($_COOKIE['remember_admin_token'])) { // Changed from 'remember_token'
    setcookie('remember_admin_token', '', time() - 3600, '/'); // Changed name
}

// Redirect to login page (assuming it's the admin login page)
header("Location: login.php");
exit();
?>
