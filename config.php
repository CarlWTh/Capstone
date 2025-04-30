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
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'carljusper.basc@gmail.com');
define('SMTP_PASSWORD', 'ztsl hxns bbkw tdqd');
define('SMTP_PORT', 587);
define('EMAIL_FROM', 'carljusper.basc@gmail.com');

// Site settings
define('SITE_NAME', 'Bottle Recycling System');
define('SITE_URL', 'http://localhost/bottle-recycling');

// Check admin authentication
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}

// Add to existing config.php, don't remove anything else
function getMinutesPerBottle() {
    global $conn;
    $result = $conn->query("SELECT value FROM SystemSettings WHERE name = 'minutes_per_bottle'");
    return $result->num_rows > 0 ? (int)$result->fetch_row()[0] : 2; // Default 2 minutes
}

define('MINUTES_PER_BOTTLE', getMinutesPerBottle());


// Log admin activity
function logAdminActivity($action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO AdminActivityLog (admin_id, action, details) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $_SESSION['user_id'], $action, $details);
    $stmt->execute();
    $stmt->close();
}

// Redirect function with message
function redirectWithMessage($url, $type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header("Location: $url");
    exit();
}

// Display flash message
function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        echo '<div class="alert alert-' . htmlspecialchars($message['type']) . '">' . 
             htmlspecialchars($message['message']) . '</div>';
        unset($_SESSION['flash_message']);
    }
}
?>