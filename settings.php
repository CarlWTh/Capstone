<?php
require_once 'config.php';
checkAdminAuth();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // Process settings update
        $site_name = $_POST['site_name'];
        $site_url = $_POST['site_url'];
        $admin_email = $_POST['admin_email'];
        
        // Validate and update settings
        if (!empty($site_name) && !empty($site_url) && filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
            // In a real application, you would save these to a database or config file
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Settings updated successfully!'
            ];
            logAdminActivity('Settings Update', 'Updated system settings');
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'Please fill all fields with valid data'
            ];
        }
    } elseif (isset($_POST['change_password'])) {
        // Process password change
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password change
        if ($new_password === $confirm_password) {
            // In a real application, verify current password and update
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Password changed successfully!'
            ];
            logAdminActivity('Password Change', 'Changed account password');
        } else {
            $_SESSION['flash_message'] = [
                'type' => 'error',
                'message' => 'New passwords do not match'
            ];
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_minutes'])) {
        $minutes = (int)$_POST['minutes_per_bottle'];
        $stmt = $conn->prepare("
            INSERT INTO SystemSettings (name, value) 
            VALUES ('minutes_per_bottle', ?)
            ON DUPLICATE KEY UPDATE value = ?
        ");
        $stmt->bind_param("ii", $minutes, $minutes);
        $stmt->execute();
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
                <li class="active">
                    <a href="network_monitoring.php">
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
                <li>
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
                                       class="form-control" value="<?= MINUTES_PER_BOTTLE ?>"
                                       min="1" max="60" required>
                                <span class="input-suffix">minutes/bottle</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Bandwidth Control</label>
                            <div class="d-flex justify-content-between">
                                <div style="width: 48%">
                                    <label class="text-muted">Download (Mbps)</label>
                                    <input type="number" name="download_speed" value="5" min="1" max="100" class="form-control">
                                </div>
                                <div style="width: 48%">
                                    <label class="text-muted">Upload (Mbps)</label>
                                    <input type="number" name="upload_speed" value="2" min="1" max="100" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Maintenance Mode</label>
                            <div class="d-flex align-items-center">
                                <div class="form-check form-switch me-3">
                                    <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenance_mode">
                                    <label class="form-check-label" for="maintenanceMode">Enable</label>
                                </div>
                                <small class="text-muted">When enabled, only admins can access the system</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_minutes" class="btn btn-primary">
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
                                <input type="number" name="sms_threshold" value="80" min="1" max="100" class="form-control">
                                <span class="input-suffix">% full</span>
                            </div>
                            <small class="text-muted">Send SMS when bin reaches this capacity</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Alert Recipients</label>
                            <textarea name="sms_recipients" class="form-control" rows="3" placeholder="Enter phone numbers separated by commas">09123456789, 09987654321</textarea>
                            <small class="text-muted">Include country code (e.g. +63 for Philippines)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Alert Message</label>
                            <textarea name="sms_message" class="form-control" rows="3">Trash bin #{bin_id} is {percentage}% full. Please empty soon.</textarea>
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
                            <button type="submit" name="update_sms_settings" class="btn btn-primary">
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
                            <input type="text" id="site_name" name="site_name" value="<?php echo SITE_NAME; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="site_url">Site URL</label>
                            <div class="input-with-button">
                                <input type="url" id="site_url" name="site_url" value="<?php echo SITE_URL; ?>" required>
                                <span class="input-suffix">/</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Admin Email</label>
                            <input type="email" id="admin_email" name="admin_email" value="admin@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="timezone">Timezone</label>
                            <select id="timezone" name="timezone" class="form-control">
                                <option value="Asia/Manila" selected>Asia/Manila</option>
                                <option value="UTC">UTC</option>
                                <!-- More timezones would be added here -->
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_settings" class="btn btn-primary">
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
                alert(`Maintenance mode will be turned ${status}. Only admins will be able to access the system.`);
            });
        }
    </script>
</body>
</html>