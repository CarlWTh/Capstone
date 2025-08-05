<?php
require_once 'config.php';
checkAdminAuth(); 

$current_settings = [];
$settings_query = $conn->query("SELECT minutes_per_bottle, bandwidth_limit_kbps, bin_full_threshold, maintenance_mode, auto_reboot_schedule FROM Settings LIMIT 1");
if ($settings_query && $settings_query->num_rows > 0) {
    $current_settings = $settings_query->fetch_assoc();
} else {
    $current_settings = [
        'minutes_per_bottle' => 2, 
        'bandwidth_limit_kbps' => 5120, 
        'bin_full_threshold' => 80,
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            redirectWithMessage('settings.php', 'error', 'All password fields are required.');
        } elseif ($new_password !== $confirm_password) {
            redirectWithMessage('settings.php', 'error', 'New passwords do not match.');
        } elseif (strlen($new_password) < 8) {
            redirectWithMessage('settings.php', 'error', 'New password must be at least 8 characters long.');
        } else {
            if (isset($_SESSION['admin_id'])) {
                $admin_id = $_SESSION['admin_id'];
                $stmt = $conn->prepare("SELECT password_hash FROM Admin WHERE admin_id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    if (password_verify($current_password, $admin['password_hash'])) {
                        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE Admin SET password_hash = ? WHERE admin_id = ?");
                        $update_stmt->bind_param("si", $new_password_hash, $admin_id);
                        if ($update_stmt->execute()) {
                            redirectWithMessage('settings.php', 'success', 'Password changed successfully!');
                            logAdminActivity('Password Change', 'Changed account password.');
                        } else {
                            redirectWithMessage('settings.php', 'error', 'Failed to change password: ' . $update_stmt->error);
                        }
                        $update_stmt->close();
                    } else {
                        redirectWithMessage('settings.php', 'error', 'Current password is incorrect.');
                    }
                } else {
                    redirectWithMessage('settings.php', 'error', 'Admin user not found.');
                }
                $stmt->close();
            } else {
                redirectWithMessage('settings.php', 'error', 'Admin ID not set in session.');
            }
        }
    } elseif (isset($_POST['update_internet_settings'])) { 
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
    } elseif (isset($_POST['update_trash_bin_settings'])) { 
        $sms_threshold = (int)$_POST['sms_threshold'];
        $sms_recipients = trim($_POST['sms_recipients']);
        $sms_message = trim($_POST['sms_message']);

        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            $stmt = $conn->prepare("
                INSERT INTO Settings (admin_id, bin_full_threshold)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE bin_full_threshold = VALUES(bin_full_threshold),
                                        admin_id = VALUES(admin_id)
            ");
            $stmt->bind_param("ii", $admin_id, $sms_threshold);

            if ($stmt->execute()) {
                redirectWithMessage('settings.php', 'success', 'Trash Bin settings updated successfully!');
                logAdminActivity('Trash Bin Settings Update', 'Updated trash bin alert settings.');
            } else {
                redirectWithMessage('settings.php', 'error', 'Failed to update trash bin settings: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            redirectWithMessage('settings.php', 'error', 'Admin ID not set in session for trash bin settings update.');
        }
    }
}

logAdminActivity('Settings Access', 'Accessed settings page');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
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
                    <a href="bins.php">
                        <i class="bi bi-trash"></i>
                        <span>Trash Bins</span>
                    </a>
                </li>
                <li class="">
                    <a href="sessions.php">
                        <i class="bi bi-wifi"></i>
                        <span>Network Monitoring</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Admins</span>
                    </a>
                </li>
                <li class="">
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
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
            <h1><i class="bi bi-gear"></i> System Settings</h1>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="settings-grid">
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

            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-trash"></i>
                    </div>
                    <h2>Trash Bin Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>SMS Alert Threshold</label>
                            <div class="input-with-button">
                                <input type="number" name="sms_threshold" value="<?= htmlspecialchars($current_settings['bin_full_threshold']) ?>" min="1" max="100" class="form-control" required>
                                <span class="input-suffix">%Full</span>
                            </div>
                            <small class="text-muted">Send SMS when bin reaches this capacity</small>
                        </div>

                        <div class="form-group">
                            <label>Alert Recipients</label>
                            <textarea name="sms_recipients" class="form-control" rows="3" placeholder="Enter phone numbers separated by commas"><?= htmlspecialchars($current_settings['sms_recipients_list'] ?? '09123456789, 09987654321') ?></textarea>
                            
                        </div>

                        <div class="form-group">
                            <label>Alert Message</label>
                            <textarea name="sms_message" class="form-control" rows="3"><?= htmlspecialchars($current_settings['sms_alert_message'] ?? 'Trash bin #{bin_id} is {percentage}% full. Please empty soon.') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Test SMS Function</label>
                            <div class="d-flex">
                                <input type="text" class="form-control me-2" placeholder="Enter test number">
                                <button type="button" class="btn btn-secondary">
                                     Send Test
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_trash_bin_settings" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h2>Security</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-segment"></div>
                                    <div class="strength-segment"></div>
                                    <div class="strength-segment"></div>
                                    <div class="strength-segment"></div>
                                </div>
                                <small class="help-text">Use 8+ characters with a mix of letters, numbers & symbols</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </div>
                    </form>
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

        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.querySelector('.strength-meter');
            const segments = meter.querySelectorAll('.strength-segment');

            segments.forEach(seg => {
                seg.style.backgroundColor = '#ddd';
                seg.style.borderColor = '#ddd';
            });

            if (password.length > 0) {
                segments[0].style.backgroundColor = '#e74c3c';
                segments[0].style.borderColor = '#e74c3c';
            }
            if (password.length >= 6) {
                segments[1].style.backgroundColor = '#f39c12';
                segments[1].style.borderColor = '#f39c12';
            }
            if (password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                segments[2].style.backgroundColor = '#2ecc71';
                segments[2].style.borderColor = '#2ecc71';
            }
            if (password.length >= 10 && /[A-Z]/.test(password) && /[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) {
                segments[3].style.backgroundColor = '#27ae60';
                segments[3].style.borderColor = '#27ae60';
            }
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
    </script>
</body>
</html>