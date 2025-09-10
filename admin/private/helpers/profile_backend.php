<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/utils_backend.php';
require_once __DIR__ . '/activity_logs_backend.php';
checkAdminAuth();

// Get current user's profile data
$stmt = $conn->prepare("SELECT admin_id, username, email, created_at FROM Admin WHERE admin_id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        if (empty($username) || empty($email)) {
            redirectWithMessage('profile.php', 'error', 'Username and email are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('profile.php', 'error', 'Invalid email format.');
        } else {
            // Check if username or email already exists for another admin
            $stmt = $conn->prepare("SELECT admin_id FROM Admin WHERE (username = ? OR email = ?) AND admin_id != ?");
            $stmt->bind_param("ssi", $username, $email, $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                redirectWithMessage('profile.php', 'error', 'Username or email already exists.');
            } else {
                $stmt = $conn->prepare("UPDATE Admin SET username = ?, email = ? WHERE admin_id = ?");
                $stmt->bind_param("ssi", $username, $email, $_SESSION['admin_id']);

                if ($stmt->execute()) {
                    logAdminActivity('Profile Update', "Updated profile information");
                    redirectWithMessage('profile.php', 'success', 'Profile updated successfully!');
                } else {
                    redirectWithMessage('profile.php', 'error', 'Error updating profile: ' . $conn->error);
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            redirectWithMessage('profile.php', 'error', 'All password fields are required.');
        } elseif ($new_password !== $confirm_password) {
            redirectWithMessage('profile.php', 'error', 'New passwords do not match.');
        } elseif (strlen($new_password) < 8) {
            redirectWithMessage('profile.php', 'error', 'New password must be at least 8 characters long.');
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM Admin WHERE admin_id = ?");
            $stmt->bind_param("i", $_SESSION['admin_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($current_password, $row['password_hash'])) {
                    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE Admin SET password_hash = ? WHERE admin_id = ?");
                    $stmt->bind_param("si", $new_hash, $_SESSION['admin_id']);

                    if ($stmt->execute()) {
                        logAdminActivity('Password Change', "Changed account password");
                        redirectWithMessage('profile.php', 'success', 'Password changed successfully!');
                    } else {
                        redirectWithMessage('profile.php', 'error', 'Error changing password: ' . $conn->error);
                    }
                    $stmt->close();
                } else {
                    redirectWithMessage('profile.php', 'error', 'Current password is incorrect.');
                }
            } else {
                redirectWithMessage('profile.php', 'error', 'User not found.');
            }
        }
    }
}

?>