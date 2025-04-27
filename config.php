<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bottle_recycling_system');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Email settings for PHPMailer
define('SMTP_HOST', 'smtp.gmail.com');  // Change this to your SMTP server
define('SMTP_USERNAME', 'carljusper.basc@gmail.com');  // Change to your email
define('SMTP_PASSWORD', 'ztsl hxns bbkw tdqd');  // Change to your email password or app password
define('SMTP_PORT', 587);  // Common ports: 25, 465, 587
define('EMAIL_FROM', 'carljusper.basc@gmail.com');  // Change to your sending email

// Authentication check function
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header("Location: login.php");
        exit();
    }
}

// Add this function to config.php
function getUserAvatar($user_id, $conn) {
    $stmt = $conn->prepare("SELECT avatar_path FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $avatar = $user['avatar_path'] ?? '/api/placeholder/200/200';
    $stmt->close();
    return $avatar;
}
?>