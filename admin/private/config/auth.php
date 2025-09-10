<?php
function checkAdminAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin_session']) || !$_SESSION['is_admin_session']) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}
?>
