<?php
require_once 'config.php';
checkAdminAuth(); // This function is defined in config.php

// Fetch current settings from the database
$current_settings = [];
$settings_query = $conn->query("SELECT minutes_per_bottle, bandwidth_limit_kbps, bin_full_threshold, maintenance_mode, auto_reboot_schedule FROM Settings LIMIT 1");
if ($settings_query && $settings_query->num_rows > 0) {
    $current_settings = $settings_query->fetch_assoc();
} else {
    // Default values if no settings found (should be handled by initial data insertion)
    $current_settings = [
        'minutes_per_bottle' => 2, // Default to 2 minutes per bottle
        'bandwidth_limit_kbps' => 5120, // 5 Mbps
        'bin_full_threshold' => 80,
        'maintenance_mode' => 0,
        'auto_reboot_schedule' => 'daily 03:00 AM'
    ];
}

// Fetch admin email from the Admin table
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


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_general_settings'])) { // Changed name from update_settings
        // Process general settings update
        $site_name = trim($_POST['site_name']);
        $site_url = trim($_POST['site_url']);
        $admin_email = trim($_POST['admin_email']);
        $timezone = trim($_POST['timezone']);

        // Validate and update settings
        if (!empty($site_name) && !empty($site_url) && filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            // In a real application, you would save these to a database or config file
            // For SITE_NAME and SITE_URL, these are defined constants in config.php.
            // To make them dynamic, you'd need a 'SystemSettings' table or similar
            // where these values are stored and fetched.
            // For now, we'll just log the activity and show success.

            // Update email in Admin table if it belongs to the current admin
            if (isset($_SESSION['admin_id'])) {
                $admin_id = $_SESSION['admin_id'];
                $update_email_stmt = $conn->prepare("UPDATE Admin SET email = ? WHERE admin_id = ?");
                $update_email_stmt->bind_param("si", $admin_email, $admin_id);
                $update_email_stmt->execute();
                $update_email_stmt->close();
            }

            // For site_name and site_url, if they are meant to be dynamic and stored in DB:
            // You would need a table like 'SystemConfig' with 'setting_name', 'setting_value'
            // $update_site_name_stmt = $conn->prepare("INSERT INTO SystemConfig (setting_name, setting_value) VALUES ('SITE_NAME', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            // $update_site_name_stmt->bind_param("ss", $site_name, $site_name);
            // $update_site_name_stmt->execute();
            // $update_site_name_stmt->close();

            // Similar for SITE_URL

            // Update timezone (this would typically be a server-level setting or stored in DB)
            date_default_timezone_set($timezone);
            $conn->query("SET time_zone = '" . date('P') . "'"); // Set MySQL timezone based on PHP timezone

            redirectWithMessage('settings.php', 'success', 'General settings updated successfully!');
            logAdminActivity('General Settings Update', 'Updated site name, URL, admin email, and timezone.');
        } else {
            redirectWithMessage('settings.php', 'error', 'Please fill all fields with valid data for general settings.');
        }
    } elseif (isset($_POST['change_password'])) {
        // Process password change
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
                // Verify current password against the 'Admin' table
                $stmt = $conn->prepare("SELECT password_hash FROM Admin WHERE admin_id = ?");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    if (password_verify($current_password, $admin['password_hash'])) {
                        // Update password in the 'Admin' table
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
    } elseif (isset($_POST['update_internet_settings'])) { // Changed name from update_minutes
        $minutes_per_bottle = (float)$_POST['minutes_per_bottle'];
        $bandwidth_limit_mbps_download = (float)$_POST['download_speed'];
        $bandwidth_limit_mbps_upload = (float)$_POST['upload_speed'];
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $auto_reboot_schedule = $_POST['auto_reboot_schedule'] ?? null; // Added for auto reboot

        // Convert Mbps to Kbps for bandwidth_limit_kbps
        $bandwidth_limit_kbps = ($bandwidth_limit_mbps_download + $bandwidth_limit_mbps_upload) * 1024;

        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            // Update 'Settings' table
            $stmt = $conn->prepare("
                INSERT INTO Settings (admin_id, minutes_per_bottle, bandwidth_limit_kbps, maintenance_mode, auto_reboot_schedule)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE minutes_per_bottle = VALUES(minutes_per_bottle),
                                        bandwidth_limit_kbps = VALUES(bandwidth_limit_kbps),
                                        maintenance_mode = VALUES(maintenance_mode),
                                        auto_reboot_schedule = VALUES(auto_reboot_schedule),
                                        admin_id = VALUES(admin_id) -- Update admin_id if it changes
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
    } elseif (isset($_POST['update_trash_bin_settings'])) { // New section for trash bin settings
        $sms_threshold = (int)$_POST['sms_threshold'];
        $sms_recipients = trim($_POST['sms_recipients']);
        $sms_message = trim($_POST['sms_message']);

        if (isset($_SESSION['admin_id'])) {
            $admin_id = $_SESSION['admin_id'];
            // Update 'Settings' table for bin_full_threshold
            $stmt = $conn->prepare("
                INSERT INTO Settings (admin_id, bin_full_threshold)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE bin_full_threshold = VALUES(bin_full_threshold),
                                        admin_id = VALUES(admin_id)
            ");
            $stmt->bind_param("ii", $admin_id, $sms_threshold);

            if ($stmt->execute()) {
                // For sms_recipients and sms_message, these would typically be stored in the Settings table
                // or a separate table if they are per-bin settings.
                // Assuming for now they are global settings and could be stored in 'Settings' as well,
                // perhaps as JSON or separate columns. For simplicity, we'll just log.
                // In a real app, you'd add columns like 'sms_recipients_list', 'sms_alert_message' to 'Settings'.

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
    <!-- Sidebar -->
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
                        <span>Users</span>
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

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h1><i class="bi bi-gear"></i> System Settings</h1>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <img src="./img/avatar.jpg" alt="Profile" class="avatar-img"> <!-- Changed placeholder to local asset -->
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> <!-- Changed to admin_username -->
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    <a href="profile.php"><i class="bi bi-person"></i> Profile</a> <!-- Added .php extension -->
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="settings-grid">
            <!-- Internet Settings Card -->
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
                                       min="1" max="60" required step="0.1"> <!-- Added step for decimal -->
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

            <!-- Trash Bin Settings Card -->
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
                                <span class="input-suffix">% full</span>
                            </div>
                            <small class="text-muted">Send SMS when bin reaches this capacity</small>
                        </div>

                        <div class="form-group">
                            <label>Alert Recipients</label>
                            <textarea name="sms_recipients" class="form-control" rows="3" placeholder="Enter phone numbers separated by commas"><?= htmlspecialchars($current_settings['sms_recipients_list'] ?? '09123456789, 09987654321') ?></textarea>
                            <small class="text-muted">Include country code (e.g. +63 for Philippines)</small>
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
                                    <i class="bi bi-send"></i> Send Test
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

            <!-- General Settings Card -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-sliders"></i>
                    </div>
                    <h2>General Settings</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="site_name">Site Name</label>
                            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars(SITE_NAME); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="site_url">Site URL</label>
                            <div class="input-with-button">
                                <input type="url" id="site_url" name="site_url" value="<?php echo htmlspecialchars(SITE_URL); ?>" required>
                                <span class="input-suffix">/</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" value="<?= htmlspecialchars($admin_email_from_db) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Asia/Manila" <?= date_default_timezone_get() === 'Asia/Manila' ? 'selected' : '' ?>>Asia/Manila</option>
                                <option value="UTC" <?= date_default_timezone_get() === 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <!-- More timezones would be added here dynamically or manually -->
                                <option value="America/New_York" <?= date_default_timezone_get() === 'America/New_York' ? 'selected' : '' ?>>America/New_York</option>
                                <option value="Europe/London" <?= date_default_timezone_get() === 'Europe/London' ? 'selected' : '' ?>>Europe/London</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="update_general_settings" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Security Settings Card -->
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
                                <small class="help-text">Use 8+ characters with mix of letters, numbers & symbols</small>
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

            <!-- Backup Card -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </div>
                    <h2>Backup & Restore</h2>
                </div>
                <div class="settings-body">
                    <div class="backup-info">
                        <p>Last backup: <strong>2023-06-15 14:30</strong></p>
                        <p>Backup size: <strong>24.5 MB</strong></p>
                    </div>

                    <div class="backup-progress">
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: 75%"></div>
                        </div>
                        <small>Storage used: 75% of 100MB</small>
                    </div>

                    <div class="settings-actions">
                        <button class="btn btn-secondary">
                            <i class="bi bi-cloud-download"></i> Create Backup
                        </button>
                        <button class="btn btn-secondary">
                            <i class="bi bi-cloud-upload"></i> Restore
                        </button>
                        <button class="btn btn-danger">
                            <i class="bi bi-trash"></i> Clear Backups
                        </button>
                    </div>
                </div>
            </div>

            <!-- System Info Card -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-info-circle"></i>
                    </div>
                    <h2>System Information</h2>
                </div>
                <div class="settings-body">
                    <div class="system-info">
                        <div class="info-item">
                            <span class="info-label">PHP Version:</span>
                            <span class="info-value"><?php echo phpversion(); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Database:</span>
                            <span class="info-value">MySQL <?php echo $conn->server_info; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Server OS:</span>
                            <span class="info-value"><?php echo php_uname('s'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">System Load:</span>
                            <span class="info-value">0.75 (1 min avg)</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Memory Usage:</span>
                            <span class="info-value">128MB / 512MB</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-secondary">
                            <i class="bi bi-arrow-repeat"></i> Check for Updates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Profile dropdown
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        // Password strength meter
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.querySelector('.strength-meter');
            const segments = meter.querySelectorAll('.strength-segment');

            // Reset all segments
            segments.forEach(seg => {
                seg.style.backgroundColor = '#ddd';
                seg.style.borderColor = '#ddd';
            });

            // Very basic strength evaluation
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

        // Toggle maintenance mode switch
        const maintenanceSwitch = document.getElementById('maintenanceMode');
        if (maintenanceSwitch) {
            maintenanceSwitch.addEventListener('change', function() {
                const status = this.checked ? 'ON' : 'OFF';
                // Using a custom modal/message box instead of alert()
                const messageBox = document.createElement('div');
                messageBox.className = 'alert alert-info';
                messageBox.innerHTML = `<p>Maintenance mode will be turned ${status}. Only admins will be able to access the system.</p>`;
                document.querySelector('.main-content').prepend(messageBox);
                setTimeout(() => messageBox.remove(), 5000); // Remove message after 5 seconds
            });
        }
    </script>
</body>
</html>
