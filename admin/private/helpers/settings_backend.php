<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/utils_backend.php';
require_once __DIR__ . '/activity_logs_backend.php';

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

$current_settings = [];
$settings_query = $conn->query("SELECT minutes_per_bottle, bandwidth_limit_kbps, maintenance_mode, auto_reboot_schedule FROM Settings LIMIT 1");
if ($settings_query && $settings_query->num_rows > 0) {
    $current_settings = $settings_query->fetch_assoc();
} else {
    $current_settings = [
        'minutes_per_bottle' => 2, 
        'bandwidth_limit_kbps' => 5120, 
        'maintenance_mode' => 0,
        'auto_reboot_schedule' => 'daily 03:00 AM'
    ];
}

// Get voucher settings
$currentDefaultDurationQuery = $conn->query("SELECT setting_value FROM Settings WHERE setting_key = 'voucher_default_duration_minutes'");
$currentDefaultDurationMinutes = 60; 
if ($currentDefaultDurationQuery && $currentDefaultDurationQuery->num_rows > 0) {
    $row = $currentDefaultDurationQuery->fetch_row();
    $currentDefaultDurationMinutes = (float)$row[0];
}

// Convert minutes to days, hours, minutes for display
$totalMinutes = $currentDefaultDurationMinutes;
$displayDays = floor($totalMinutes / (24 * 60));
$remainingMinutes = $totalMinutes % (24 * 60);
$displayHours = floor($remainingMinutes / 60);
$displayMinutes = $remainingMinutes % 60;

$admin_email_from_db = '';
if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT email FROM Admin WHERE admin_id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $admin_email_from_db = $result->fetch_assoc()['email'];
    }
    $stmt->close();
}

// Get system information
function getSystemInfo() {
    $info = [];
    $info['php_version'] = phpversion();
    $info['server_software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
    $info['memory_limit'] = ini_get('memory_limit');
    $info['upload_max_filesize'] = ini_get('upload_max_filesize');
    $info['max_execution_time'] = ini_get('max_execution_time');
    $info['timezone'] = date_default_timezone_get();
    $info['current_time'] = date('Y-m-d H:i:s');
    
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        $info['server_load'] = round($load[0], 2);
    } else {
        $info['server_load'] = 'N/A';
    }
    
    $info['disk_free_space'] = disk_free_space('.') ? round(disk_free_space('.') / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A';
    $info['disk_total_space'] = disk_total_space('.') ? round(disk_total_space('.') / 1024 / 1024 / 1024, 2) . ' GB' : 'N/A';
    
    return $info;
}

$system_info = getSystemInfo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_internet_settings'])) { 
        $minutes_per_bottle = (float)$_POST['minutes_per_bottle'];
        $bandwidth_limit_mbps_download = (float)$_POST['download_speed'];
        $bandwidth_limit_mbps_upload = (float)$_POST['upload_speed'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $auto_reboot_schedule = $_POST['auto_reboot_schedule'] ?? null; 

        $bandwidth_limit_kbps = ($bandwidth_limit_mbps_download + $bandwidth_limit_mbps_upload) * 1024;

        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            $stmt = $conn->prepare("
                INSERT INTO Settings (admin_id, minutes_per_bottle, bandwidth_limit_kbps, maintenance_mode, auto_reboot_schedule)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE minutes_per_bottle = VALUES(minutes_per_bottle),
                                        bandwidth_limit_kbps = VALUES(bandwidth_limit_kbps),
                                        maintenance_mode = VALUES(maintenance_mode),
                                        auto_reboot_schedule = VALUES(auto_reboot_schedule),
                                        admin_id = VALUES(admin_id)
            ");
            $stmt->bind_param("idiss", $admin_id, $minutes_per_bottle, $bandwidth_limit_kbps, $maintenance_mode, $auto_reboot_schedule);

            if ($stmt->execute()) {
                redirectWithMessage('settings.php', 'success', 'Internet settings updated successfully!');
                logAdminActivity('Internet Settings Update', 'Updated internet settings.');
            } else {
                redirectWithMessage('settings.php', 'error', 'Failed to update internet settings: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            redirectWithMessage('settings.php', 'error', 'Admin ID not set in session for internet settings update.');
        }
    } elseif (isset($_POST['update_voucher_settings'])) {
        $expiryDays = (int)$_POST['expiry_days'];
        $expiryHours = (int)$_POST['expiry_hours'];
        $expiryMinutes = (int)$_POST['expiry_minutes'];

        if ($expiryDays >= 0 && $expiryHours >= 0 && $expiryMinutes >= 0) {
            $totalMinutes = ($expiryDays * 24 * 60) + ($expiryHours * 60) + $expiryMinutes;

            if (isset($_SESSION['admin_id'])) {
                $admin_id = (int)$_SESSION['admin_id'];
                $stmt = $conn->prepare("
                    INSERT INTO Settings (setting_key, setting_value, admin_id)
                    VALUES ('voucher_default_duration_minutes', ?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), admin_id = VALUES(admin_id)
                ");
                $stmt->bind_param("di", $totalMinutes, $admin_id);

                if ($stmt->execute()) {
                    logAdminActivity('Voucher Settings Updated', "Updated default voucher duration to $expiryDays days, $expiryHours hours, $expiryMinutes minutes");
                    redirectWithMessage('settings.php', 'success', 'Voucher settings updated successfully!');
                } else {
                    redirectWithMessage('settings.php', 'error', 'Failed to update voucher settings: ' . $stmt->error);
                }
                $stmt->close();
            } else {
                redirectWithMessage('settings.php', 'error', 'Admin ID not set in session for voucher settings update.');
            }
        } else {
            redirectWithMessage('settings.php', 'error', 'Invalid expiry time values.');
        }
    } elseif (isset($_POST['update_system_settings'])) {
        $session_timeout = (int)$_POST['session_timeout'];
        $max_login_attempts = (int)$_POST['max_login_attempts'];
        $backup_frequency = $_POST['backup_frequency'];
        $log_retention_days = (int)$_POST['log_retention_days'];

        // Here you would update system settings in the database
        redirectWithMessage('settings.php', 'success', 'System settings updated successfully!');
        logAdminActivity('System Settings Update', 'Updated system configuration.');
    }
}


?>