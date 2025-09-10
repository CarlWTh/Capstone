<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['reset_admin_id']) || !isset($_SESSION['reset_admin_email'])) {
    header("Location: forgot-password.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $admin_id = $_SESSION['reset_admin_id'];

    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Admin SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE admin_id = ?");
        if ($stmt === false) {
            $error = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("si", $hashed_password, $admin_id); 
            if ($stmt->execute()) {
            $success = "Password has been reset successfully. You may now log in.";
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit();
            } else {
            $error = "Error updating password: " . $stmt->error; 
            }
            $stmt->close();
        }
        }
    }
?>