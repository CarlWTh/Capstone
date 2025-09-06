<?php
session_start();
require_once '../config.php';
header('Content-Type: application/json');

checkAdminAuth();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['admin_id'];
$password_check = $conn->prepare("SELECT password_hash FROM admin WHERE admin_id = ?");
$password_check->bind_param("i", $admin_id);
$password_check->execute();
$result = $password_check->get_result();
$admin_data = $result->fetch_assoc();

$should_show_popup = false;
if (!isset($_SESSION['popup_shown'])) {
    $default_password = 'admin123'; // Replace with your actual default password
    if (password_verify($default_password, $admin_data['password_hash'])) {
        $should_show_popup = true;
        $_SESSION['popup_shown'] = true;
    }
}

$stats = [];
$stats['total_deposits'] = $conn->query("SELECT COUNT(*) FROM Transactions")->fetch_row()[0];
$stats['total_bottles'] = $conn->query("SELECT SUM(bottle_count) FROM Transactions")->fetch_row()[0];
$stats['total_vouchers'] = $conn->query("SELECT COUNT(*) FROM Voucher")->fetch_row()[0];
$stats['active_sessions'] = $conn->query("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL")->fetch_row()[0];

function getInternetStatus() {
    return [
        'status' => 'up',
        'download_speed' => '591.75',
        'upload_speed' => '1.20',
        'total_download' => '1.07',
        'total_upload' => '4.95',
        'uptime_percentage' => 99.2,
        'uptime_days' => 90,
        'uptime_hours' => 5,
        'uptime_minutes' => 32,
        'uptime_seconds' => 47,
        'last_downtime' => '2 hours ago',
        'public_ip' => '203.175.42.156',
        'local_ip' => '192.168.71.34',
        'temperature' => '38.62'
    ];
}

$internet_status = getInternetStatus();

echo json_encode([
    'should_show_popup' => $should_show_popup,
    'stats' => $stats,
    'internet_status' => $internet_status,
    'site_name' => SITE_NAME
]);
