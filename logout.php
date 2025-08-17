<?php
session_start();
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['is_admin_session']);
unset($_SESSION['redirect_url']); 
unset($_SESSION['reset_admin_id']);
unset($_SESSION['reset_admin_email']);
unset($_SESSION['flash_message']);

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if (isset($_COOKIE['remember'])) { 
    setcookie('remember', '', time() - 3600, '/'); 
}

header("Location: login.php");
exit();
?>
