<?php
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bottle_recycling_system'); 

$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'carljusper.basc@gmail.com');
define('SMTP_PASSWORD', 'ztsl hxns bbkw tdqd'); 
define('SMTP_PORT', 587);
define('EMAIL_FROM', 'carljusper.basc@gmail.com');

define('SITE_NAME', 'Recycle for Connectivity');
define('SITE_URL', 'http://localhost/bottle-recycling');

function checkAdminAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin_session']) || !$_SESSION['is_admin_session']) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: login.php");
        exit();
    }
}

function getMinutesPerBottle() {
    global $conn;

    $result = $conn->query("SHOW TABLES LIKE 'Settings'");
    if ($result->num_rows === 0) {
        error_log("Settings table not found. Returning default minutes_per_bottle.");
        return 2;
    }

    $result = $conn->query("SELECT minutes_per_bottle FROM Settings LIMIT 1");
    return ($result && $result->num_rows > 0) ? (float)$result->fetch_row()[0] : 2.0;
}

define('MINUTES_PER_BOTTLE', getMinutesPerBottle());

function logAdminActivity($action, $details = '') {
    global $conn;

    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];

        $check_sql = "SELECT admin_id FROM Admin WHERE admin_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $admin_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();

            $stmt = $conn->prepare("INSERT INTO SystemLog (admin_id, action, details) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $admin_id, $action, $details);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Admin ID not found in Admin table.");
        }
    } else {
        error_log("Admin ID not set in session.");
    }
}

function redirectWithMessage($url, $type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header("Location: $url");
    exit();
}

function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        echo '<div class="alert alert-' . htmlspecialchars($message['type']) . '">' .
             htmlspecialchars($message['message']) . '</div>';
        unset($_SESSION['flash_message']);
    }
}
?>
