<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['reset_admin_id']) || !isset($_SESSION['reset_admin_email'])) {
    header("Location: forgot-password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $admin_id = $_SESSION['reset_admin_id'];

    if (empty($password) || empty($confirm_password)) {
        $_SESSION['reset_error'] = "Please fill in all fields.";
        header("Location: create-new-password.php");
        exit();
    } elseif ($password !== $confirm_password) {
        $_SESSION['reset_error'] = "Passwords do not match.";
        header("Location: create-new-password.php");
        exit();
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Admin SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE admin_id = ?");
        if ($stmt === false) {
            $_SESSION['reset_error'] = "Error preparing statement: " . $conn->error;
            header("Location: create-new-password.php");
            exit();
        } else {
            $stmt->bind_param("si", $hashed_password, $admin_id); 
            if ($stmt->execute()) {
                $_SESSION['reset_success'] = "Password has been reset successfully. You may now log in.";
                session_unset();
                session_destroy();
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['reset_error'] = "Error updating password: " . $stmt->error; 
                header("Location: create-new-password.php");
                exit();
            }
            $stmt->close();
        }
    }
} else {
    header("Location: create-new-password.php");
    exit();
}
$conn->close();
?>