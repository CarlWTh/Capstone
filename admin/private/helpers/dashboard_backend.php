<?php
session_start();
require_once '../config.php';
checkAdminAuth();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$password_check = $conn->prepare("SELECT password_hash FROM admin WHERE admin_id = ?");
$password_check->bind_param("i", $admin_id);
$password_check->execute();
$result = $password_check->get_result();
$admin_data = $result->fetch_assoc();

// Show popup only if:
// 1. Popup hasn't been shown in this session
// 2. User is still using the default password
$should_show_popup = false;

if (!isset($_SESSION['popup_shown'])) {
    // Define your default password here - change this to match your actual default password
    $default_password = 'admin123'; // Replace with your actual default password
    
    // Check if current password is still the default
    if (password_verify($default_password, $admin_data['password_hash'])) {
        $should_show_popup = true;
    }
}

if ($should_show_popup) {
    $_SESSION['popup_shown'] = true;
    ?>
    <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.addEventListener('load', function() {
            Swal.fire({
                title: 'Security Reminder',
                text: 'To secure your account, we recommend changing your username and password. Additionally, add an email for your forgot password.',
                icon: 'info',
                confirmButtonText: 'OK',
                confirmButtonColor: '#04aba0',
                showCancelButton: true,
                cancelButtonText: 'Change Now',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                    // Redirect to profile/settings page when "Change Now" is clicked
                    window.location.href = 'profile.php';
                }
            });
        });
    </script>
    <?php
}

$stats = [];
$stats['total_deposits'] = $conn->query("SELECT COUNT(*) FROM Transactions")->fetch_row()[0];
$stats['total_bottles'] = $conn->query("SELECT SUM(bottle_count) FROM Transactions")->fetch_row()[0];
$stats['total_vouchers'] = $conn->query("SELECT COUNT(*) FROM Voucher")->fetch_row()[0];
$stats['active_sessions'] = $conn->query("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL")->fetch_row()[0];

function getInternetStatus() {
    // This is a placeholder - replace with actual implementation
    return [
        'status' => 'up', // up, down
        'download_speed' => '591.75', // bytes/s
        'upload_speed' => '1.20', // KB/s
        'total_download' => '1.07', // MB
        'total_upload' => '4.95', // MB
        'uptime_percentage' => 99.2,
        'uptime_days' => 90,
        'uptime_hours' => 5,
        'uptime_minutes' => 32,
        'uptime_seconds' => 47,
        'last_downtime' => '2 hours ago',
        'public_ip' => '203.175.42.156', // Public IP address
        'local_ip' => '192.168.71.34', // Local IP address
        'temperature' => '38.62' // Temperature in Celsius
    ];
}

$internet_status = getInternetStatus();
?>
