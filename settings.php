<?php
require_once 'config.php';
checkAdminAuth();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_rates'])) {
        // Update credit rates
        $new_rate = intval($_POST['credit_rate']);
        if ($new_rate > 0 && $new_rate <= 100) {
            $stmt = $conn->prepare("UPDATE credit_rates SET credits_per_unit = ?, last_updated = NOW() WHERE bottle_type = 'standard'");
            $stmt->bind_param("i", $new_rate);
            if ($stmt->execute()) {
                $message = "Credit rates updated successfully!";
            } else {
                $error = "Failed to update credit rates.";
            }
            $stmt->close();
        } else {
            $error = "Invalid credit rate value. Must be between 1 and 100.";
        }
    } elseif (isset($_POST['update_alerts'])) {
        // Update SMS alerts
        $phone = $_POST['admin_phone'];
        $bin_alerts = isset($_POST['bin_full_alerts']) ? 1 : 0;
        $error_alerts = isset($_POST['system_error_alerts']) ? 1 : 0;
        $daily_summary = isset($_POST['daily_summary']) ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE sms_alerts SET admin_phone = ?, bin_full_alerts = ?, system_error_alerts = ?, daily_summary = ?, last_updated = NOW() WHERE id = 1");
        $stmt->bind_param("siii", $phone, $bin_alerts, $error_alerts, $daily_summary);
        if ($stmt->execute()) {
            $message = "Alert settings updated successfully!";
        } else {
            $error = "Failed to update alert settings.";
        }
        $stmt->close();
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if ($new !== $confirm) {
            $error = "New passwords don't match.";
        } elseif (strlen($new) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($current, $user['password_hash'])) {
                    // Update password
                    $new_hash = password_hash($new, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                    $stmt->bind_param("si", $new_hash, $_SESSION['user_id']);
                    if ($stmt->execute()) {
                        $message = "Password changed successfully!";
                    } else {
                        $error = "Failed to update password.";
                    }
                } else {
                    $error = "Current password is incorrect.";
                }
            }
            $stmt->close();
        }
    }
}

// Get current settings
$credit_rate = 5;
$result = $conn->query("SELECT credits_per_unit FROM credit_rates WHERE bottle_type = 'standard' LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $credit_rate = $row['credits_per_unit'];
}

$sms_alerts = [
    'admin_phone' => '+639123456789',
    'bin_full_alerts' => true,
    'system_error_alerts' => true,
    'daily_summary' => true
];
$result = $conn->query("SELECT admin_phone, bin_full_alerts, system_error_alerts, daily_summary FROM sms_alerts LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $sms_alerts = $row;
}

$backup_info = [
    'last_backup' => '2024-03-27 10:15:00',
    'auto_backup' => true
];
$result = $conn->query("SELECT setting_value as last_backup FROM system_settings WHERE setting_name = 'last_backup'");
if ($result && $row = $result->fetch_assoc()) {
    $backup_info['last_backup'] = $row['last_backup'];
}
$result = $conn->query("SELECT setting_value as auto_backup FROM system_settings WHERE setting_name = 'auto_backup'");
if ($result && $row = $result->fetch_assoc()) {
    $backup_info['auto_backup'] = (bool)$row['auto_backup'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="/css/styles.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1>Recycling Admin</h1>
                    <span class="logo-short"></span>
                </div>
                
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class='bx bx-menu'></i>
                </button>
            </div>
            
            <nav>
                <ul>
                    <li class="active">
                        <a href="dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="transactions.php">
                            <i class='bx bx-transfer-alt'></i>
                            <span class="menu-text">Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="monitoring.php">
                            <i class='bx bx-line-chart'></i>
                            <span class="menu-text">System Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class='bx bx-cog'></i>
                            <span class="menu-text">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class='bx bxs-report'></i>
                            <span class="menu-text">Reports</span>
                        </a>
                    </li>
                    <li class="logout">
                        <a href="login.php">
                            <i class='bx bx-log-out'></i>
                            <span class="menu-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h2>System Settings</h2>
                <div class="user-info">
                    <span>Welcome, Admin</span>
                    <img src="/api/placeholder/40/40" alt="Admin Avatar">
                </div>
            </header>
    <?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <div class="card settings-card">
            <div class="settings-header">
                <h2>Credit Conversion Rates</h2>
                <div class="settings-icon">
                    <i class="icon-exchange"></i>
                </div>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="small-bottle-rate">Credits per Bottles</label>
                        <div class="input-with-button">
                            <input type="number" id="small-bottle-rate" name="credit_rate" 
                                   value="<?php echo htmlspecialchars($credit_rate); ?>" min="1" max="100">
                            <span class="input-suffix">credits</span>
                        </div>
                        <p class="help-text">Current: 1 Bottle = <?php echo $credit_rate; ?> Credits</p>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_rates" class="btn-primary">Update Rates</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card settings-card">
            <div class="settings-header">
                <h2>SMS Alert Settings</h2>
                <div class="settings-icon">
                    <i class="icon-bell"></i>
                </div>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="admin-phone">Admin Phone Number</label>
                        <input type="tel" id="admin-phone" name="admin_phone" 
                               placeholder="+63 XXX XXX XXXX" value="<?php echo htmlspecialchars($sms_alerts['admin_phone']); ?>">
                    </div>
                    <div class="form-group checkbox-group">
                        <label class="checkbox-container">
                            <input type="checkbox" name="bin_full_alerts" <?php echo $sms_alerts['bin_full_alerts'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Bin Full Alerts
                        </label>
                    </div>
                    <div class="form-group checkbox-group">
                        <label class="checkbox-container">
                            <input type="checkbox" name="system_error_alerts" <?php echo $sms_alerts['system_error_alerts'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            System Error Alerts
                        </label>
                    </div>
                    <div class="form-group checkbox-group">
                        <label class="checkbox-container">
                            <input type="checkbox" name="daily_summary" <?php echo $sms_alerts['daily_summary'] ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Daily Summary Report
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="update_alerts" class="btn-primary">Update Alerts</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card settings-card">
            <div class="settings-header">
                <h2>Change Admin Password</h2>
                <div class="settings-icon">
                    <i class="icon-lock"></i>
                </div>
            </div>
            <div class="settings-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" id="current-password" name="current_password" placeholder="Enter current password" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" id="new-password" name="new_password" placeholder="Enter new password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter">
                            <div class="strength-segment"></div>
                            <div class="strength-segment"></div>
                            <div class="strength-segment"></div>
                            <div class="strength-segment"></div>
                        </div>
                        <span>Password Strength: <strong>Medium</strong></span>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card settings-card">
            <div class="settings-header">
                <h2>Backup & Restore</h2>
                <div class="settings-icon">
                    <i class="icon-database"></i>
                </div>
            </div>
            <div class="settings-body">
                <div class="backup-info">
                    <p><strong>Last Backup:</strong> <?php echo date('F j, Y (g:i A)', strtotime($backup_info['last_backup'])); ?></p>
                    <div class="backup-progress">
                        <div class="progress-bar">
                            <div class="progress" style="width: 100%"></div>
                        </div>
                        <span>Automatic backup <?php echo $backup_info['auto_backup'] ? 'enabled' : 'disabled'; ?></span>
                    </div>
                </div>
                <div class="settings-actions">
                    <button class="btn-secondary">
                        <i class="icon-download"></i> Download Backup
                    </button>
                    <button class="btn-secondary">
                        <i class="icon-upload"></i> Restore Data
                    </button>
                    <button class="btn-primary" onclick="backupNow()">
                        <i class="icon-refresh"></i> Backup Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function backupNow() {
            // In a real implementation, this would make an AJAX call to trigger a backup
            alert("Backup process started. You'll be notified when complete.");
        }
        
        // Password strength meter
        document.getElementById('new-password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.querySelector('.strength-meter');
            const segments = meter.querySelectorAll('.strength-segment');
            const strengthText = meter.nextElementSibling.querySelector('strong');
            
            // Reset
            segments.forEach(seg => seg.style.backgroundColor = '#ddd');
            
            // Calculate strength (simplified)
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update display
            const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
            strengthText.textContent = strengthLabels[Math.min(strength, 4)];
            
            // Color segments
            const colors = ['#e74c3c', '#f39c12', '#f1c40f', '#2ecc71', '#27ae60'];
            for (let i = 0; i < Math.min(strength, 5); i++) {
                segments[i].style.backgroundColor = colors[i];
            }
        });
    </script>
</body>
</html>