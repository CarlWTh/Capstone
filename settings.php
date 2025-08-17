<?php
require_once 'config.php';
checkAdminAuth(); 

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .settings-header {
            background: linear-gradient(135deg, #f99d41ff 0%, #f48301ff 100%);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .settings-icon {
            font-size: 24px;
        }
        
        .settings-body {
            padding: 25px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .info-value {
            color: #333;
            font-family: monospace;
        }
        
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-online { background-color: #28a745; }
        .status-warning { background-color: #ffc107; }
        .status-offline { background-color: #dc3545; }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #f15900ff;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body class="dashboard-container">
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h1><?= SITE_NAME ?></h1>
                <span class="logo-short"></span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <nav>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="bottle_deposits.php">
                        <i class="bi bi-recycle"></i>
                        <span>Bottle Deposits</span>
                    </a>
                </li>
                <li>
                    <a href="vouchers.php">
                        <i class="bi bi-ticket-perforated"></i>
                        <span>Vouchers</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Sessions</span>
                    </a>
                </li>
                <li class="">
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>
                <li>
                        <a href="profile.php">
                            <i class="bi bi-person-circle"></i>
                            <span>My Account</span>
                        </a>
                    </li>
                <li>
                    <a href="settings.php">
                        <i class="bi bi-gear"></i> 
                        <span>Settings</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="main-content">
        <div class="main-header">
            <h1><i class="bi bi-gear"></i> System Settings & Information</h1>
        </div>

        <?php displayFlashMessage(); ?>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-number"><?= date('H:i') ?></div>
                <div class="stat-label">Current Time</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $system_info['server_load'] ?></div>
                <div class="stat-label">Server Load</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $system_info['disk_free_space'] ?></div>
                <div class="stat-label">Free Space</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <span class="status-indicator status-online"></span>Online
                </div>
                <div class="stat-label">System Status</div>
            </div>
        </div>

        <div class="settings-grid">
            <!-- Internet Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-wifi"></i>
                    </div>
                    <h2>Internet Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Bottle-to-Internet Conversion Rate</label>
                            <div class="input-with-button">
                                <input type="number" name="minutes_per_bottle"
                                       class="form-control" value="<?= htmlspecialchars($current_settings['minutes_per_bottle']) ?>"
                                       min="1" max="60" required step="0.1">
                                <span class="input-suffix">minutes/bottle</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Bandwidth Control</label>
                            <div class="d-flex justify-content-between">
                                <div style="width: 48%">
                                    <label class="text-muted">Download (Mbps)</label>
                                    <input type="number" name="download_speed" value="<?= htmlspecialchars(round($current_settings['bandwidth_limit_kbps'] / 1024, 2)) ?>" min="1" max="100" class="form-control" step="0.1">
                                </div>
                                <div style="width: 48%">
                                    <label class="text-muted">Upload (Mbps)</label>
                                    <input type="number" name="upload_speed" value="<?= htmlspecialchars(round($current_settings['bandwidth_limit_kbps'] / 1024, 2)) ?>" min="1" max="100" class="form-control" step="0.1">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Maintenance Mode</label>
                            <div class="d-flex align-items-center">
                                <div class="form-check form-switch me-3">
                                    <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode"
                                        <?= $current_settings['maintenance_mode'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="maintenanceMode">Enable</label>
                                </div>
                                <small class="text-muted">When enabled, only admins can access the system</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="auto_reboot_schedule">Auto Reboot Schedule</label>
                            <input type="text" id="auto_reboot_schedule" name="auto_reboot_schedule"
                                       class="form-control" value="<?= htmlspecialchars($current_settings['auto_reboot_schedule'] ?? '') ?>"
                                       placeholder="e.g., daily 03:00 AM">
                            <small class="text-muted">Set a schedule for automatic system reboots.</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_internet_settings" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Settings -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-cpu"></i>
                    </div>
                    <h2>System Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="session_timeout">Session Timeout</label>
                            <select id="session_timeout" name="session_timeout" class="form-control">
                                <option value="1800">30 minutes</option>
                                <option value="3600" selected>1 hour</option>
                                <option value="7200">2 hours</option>
                                <option value="14400">4 hours</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="max_login_attempts">Max Login Attempts</label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" 
                                   value="5" min="3" max="10" class="form-control">
                            <small class="text-muted">Account lockout after this many failed attempts</small>
                        </div>

                        <div class="form-group">
                            <label for="backup_frequency">Backup Frequency</label>
                            <select id="backup_frequency" name="backup_frequency" class="form-control">
                                <option value="daily" selected>Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="log_retention_days">Log Retention Period</label>
                            <div class="input-with-button">
                                <input type="number" id="log_retention_days" name="log_retention_days" 
                                       value="90" min="30" max="365" class="form-control">
                                <span class="input-suffix">days</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_system_settings" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- System Information -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <h2>System Information</h2>
                </div>
                <div class="settings-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?= $system_info['php_version'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Server Software:</span>
                            <span class="info-value"><?= $system_info['server_software'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Memory Limit:</span>
                            <span class="info-value"><?= $system_info['memory_limit'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Upload Limit:</span>
                            <span class="info-value"><?= $system_info['upload_max_filesize'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Execution Time:</span>
                            <span class="info-value"><?= $system_info['max_execution_time'] ?>s</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Timezone:</span>
                            <span class="info-value"><?= $system_info['timezone'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Current Time:</span>
                            <span class="info-value"><?= $system_info['current_time'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Server Load:</span>
                            <span class="info-value"><?= $system_info['server_load'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Free Disk Space:</span>
                            <span class="info-value"><?= $system_info['disk_free_space'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total Disk Space:</span>
                            <span class="info-value"><?= $system_info['disk_total_space'] ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Information -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-database"></i>
                    </div>
                    <h2>Database Information</h2>
                </div>
                <div class="settings-body">
                    <?php
                    // Get database info
                    $db_version = $conn->query("SELECT VERSION() as version")->fetch_assoc()['version'] ?? 'Unknown';
                    $db_size_query = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema=DATABASE()");
                    $db_size = $db_size_query ? $db_size_query->fetch_assoc()['DB Size in MB'] . ' MB' : 'N/A';
                    
                    $table_count = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema=DATABASE()")->fetch_assoc()['count'] ?? 0;
                    ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">MySQL Version:</span>
                            <span class="info-value"><?= $db_version ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Database Size:</span>
                            <span class="info-value"><?= $db_size ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Table Count:</span>
                            <span class="info-value"><?= $table_count ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Connection Status:</span>
                            <span class="info-value">
                                <span class="status-indicator status-online"></span>Connected
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-3" style="margin-top: 20px;">
                        <button type="button" class="btn btn-outline-primary btn-sm" style="margin-left: 20px;" onclick="backupDatabase()">
                            <i class="bi bi-download"></i> Backup Database
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" style="margin-left: 20px;" onclick="optimizeDatabase()">
                            <i class="bi bi-gear"></i> Optimize Tables
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        const maintenanceSwitch = document.getElementById('maintenanceMode');
        if (maintenanceSwitch) {
            maintenanceSwitch.addEventListener('change', function() {
                const status = this.checked ? 'ON' : 'OFF';
                const messageBox = document.createElement('div');
                messageBox.className = 'alert alert-info';
                messageBox.innerHTML = `<p>Maintenance mode will be turned ${status}. Only admins will be able to access the system.</p>`;
                document.querySelector('.main-content').prepend(messageBox);
                setTimeout(() => messageBox.remove(), 5000); 
            });
        }

        function backupDatabase() {
            if (confirm('Are you sure you want to create a database backup?')) {
                // You would implement the backup functionality here
                alert('Database backup initiated. You will receive a notification when complete.');
            }
        }

        function optimizeDatabase() {
            if (confirm('Are you sure you want to optimize database tables?')) {
                // You would implement the optimization functionality here
                alert('Database optimization initiated. You will receive a notification when complete.');
            }
        }
    </script>
</body>
</html>