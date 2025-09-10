<?php
require_once 'auth.php';

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bottle_recycling_system (2)');

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



?>
