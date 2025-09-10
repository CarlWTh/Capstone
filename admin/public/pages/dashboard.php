<?php
session_start();
require_once '../../private/config/config.php';
require_once '../../private/helpers/dashboard_backend.php';
require_once '../../private/helpers/utils_backend.php';
checkAdminAuth();


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <script src="../js/sidebar.js"></script>
</head>
<body>
    <div class="dashboard-container">
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
                    <li class="active">
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
                        <a href="devices.php">
                            <i class="bi bi-phone"></i>
                            <span>Devices</span>
                        </a>
                    </li>
                    <li>
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

        <div class="main-content" id="mainContent">
            <div class="main-header">
                <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
            </div>

            <?php displayFlashMessage(); ?>

            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card stats-card-deposits">
                        <div class="stats-card-body">
                            <div class="stats-icon-container">
                                <i class="bi bi-recycle stats-card-icon"></i>
                            </div>
                            <h5 class="stats-card-title">Total Deposits</h5>
                            <h2 class="stats-card-value"><?php echo number_format($stats['total_deposits']); ?></h2>
                        </div>
                        <div class="stats-card-footer">
                            <span class="stats-trend positive">
                                <i class="bi bi-arrow-up stats-trend-icon"></i>
                                +12% from last month
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-card-bottles">
                        <div class="stats-card-body">
                            <div class="stats-icon-container">
                                <i class="bi bi-cup-straw stats-card-icon"></i>
                            </div>
                            <h5 class="stats-card-title">Total Bottles</h5>
                            <h2 class="stats-card-value"><?php echo number_format($stats['total_bottles']); ?></h2>
                        </div>
                        <div class="stats-card-footer">
                            <span class="stats-trend positive">
                                <i class="bi bi-arrow-up stats-trend-icon"></i>
                                +8% from last month
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-card-vouchers">
                        <div class="stats-card-body">
                            <div class="stats-icon-container">
                                <i class="bi bi-ticket-perforated stats-card-icon"></i>
                            </div>
                            <h5 class="stats-card-title">Vouchers Issued</h5>
                            <h2 class="stats-card-value"><?php echo number_format($stats['total_vouchers']); ?></h2>
                        </div>
                        <div class="stats-card-footer">
                            <span class="stats-trend positive">
                                <i class="bi bi-arrow-up stats-trend-icon"></i>
                                +15% from last month
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card stats-card-sessions">
                        <div class="stats-card-body">
                            <div class="stats-icon-container">
                                <i class="bi bi-wifi stats-card-icon"></i>
                            </div>
                            <h5 class="stats-card-title">Active Sessions</h5>
                            <h2 class="stats-card-value"><?php echo number_format($stats['active_sessions']); ?></h2>
                        </div>
                        <div class="stats-card-footer">
                            <span class="stats-trend positive">
                                <i class="bi bi-arrow-up stats-trend-icon"></i>
                                +23% from last month
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Internet Status Card -->
                <div class="col-md-4">
                    <div class="internet-status-card">
                        <div class="internet-status-header">
                            <i class="bi bi-wifi header-icon"></i>
                            Internet Status
                        </div>
                        <div class="internet-status-body">
                            <div class="internet-status-display">
                                <div class="internet-status-badge status-<?php echo $internet_status['status']; ?>">
                                    <span class="status-pulse"></span>
                                    Internet is <?php echo strtoupper($internet_status['status']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connection Status Card (eth0 WAN) -->
                <div class="col-md-4">
                    <div class="network-card">
                        <div class="network-header">
                            <i class="bi bi-ethernet header-icon"></i>
                            Connection Status
                        </div>
                        <div class="network-body">
                            <div class="network-item">
                                <span class="network-label">
                                    <i class="bi bi-geo-alt metric-icon"></i>
                                    IP Address
                                </span>
                                <span class="network-value"><?php echo $internet_status['local_ip']; ?></span>
                            </div>
                            <div class="network-item">
                                <span class="network-label">
                                    <i class="bi bi-download metric-icon"></i>
                                    Download
                                </span>
                                <span class="network-value"><?php echo $internet_status['download_speed']; ?> bytes/s</span>
                            </div>
                            <div class="network-item">
                                <span class="network-label">
                                    <i class="bi bi-upload metric-icon"></i>
                                    Upload
                                </span>
                                <span class="network-value"><?php echo $internet_status['upload_speed']; ?> KB/s</span>
                            </div>
                            <div class="network-item">
                                <span class="network-label">
                                    <i class="bi bi-arrow-down-circle metric-icon"></i>
                                    Total Download
                                </span>
                                <span class="network-value"><?php echo $internet_status['total_download']; ?> MB</span>
                            </div>
                            <div class="network-item">
                                <span class="network-label">
                                    <i class="bi bi-arrow-up-circle metric-icon"></i>
                                    Total Upload
                                </span>
                                <span class="network-value"><?php echo $internet_status['total_upload']; ?> MB</span>
                            </div>
                            <div class="network-item">
                                <span class="network-label">
                                    <i class="bi bi-globe metric-icon"></i>
                                    Internet
                                </span>
                                <span class="status-indicator status-<?php echo $internet_status['status']; ?>">
                                    <span class="status-icon"></span>
                                    <?php echo ucfirst($internet_status['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Uptime Status Card -->
                <div class="col-md-4">
                    <div class="uptime-card">
                        <div class="uptime-header">
                            <i class="bi bi-clock header-icon"></i>
                            System Status
                        </div>
                        <div class="uptime-body">
                            <div class="uptime-item">
                                <span class="uptime-label">
                                    <i class="bi bi-clock-history metric-icon"></i>
                                    System Uptime
                                </span>
                                <span class="uptime-value"><?php echo $internet_status['uptime_days']; ?>d <?php echo $internet_status['uptime_hours']; ?>h <?php echo $internet_status['uptime_minutes']; ?>m</span>
                            </div>
                            <div class="temperature-display">
                                <i class="bi bi-thermometer-half" style="font-size: 1.5rem; margin-bottom: 8px; opacity: 0.9;"></i>
                                <div class="temperature-value"><?php echo $internet_status['temperature']; ?>°C</div>
                                <div class="temperature-label">System Temperature</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src =".../js/dashboard.js">
        
    </script>
</body>
</html>