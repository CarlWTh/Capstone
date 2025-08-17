<?php
session_start();
require_once 'config.php';
checkAdminAuth();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

function getDetailedNetworkStatus() {
    // Enhanced network status with more detailed information
    return [
        'connection' => [
            'status' => 'connected', // connected, disconnected, limited
            'type' => 'Fiber Optic',
            'provider' => 'Globe Telecom',
            'plan' => 'Unli Fiber Plan 1699',
            'connected_since' => '2024-01-15 08:30:00',
            'last_reconnect' => '2024-08-10 14:22:15'
        ],
        'speed' => [
            'download_current' => '150.5',
            'upload_current' => '25.3',
            'download_avg_24h' => '142.8',
            'upload_avg_24h' => '23.7',
            'download_peak' => '180.2',
            'upload_peak' => '28.9',
            'latency' => '12',
            'jitter' => '2.1',
            'packet_loss' => '0.02'
        ],
        'usage' => [
            'download_today' => '1.2',
            'upload_today' => '0.8',
            'download_month' => '45.7',
            'upload_month' => '18.3',
            'peak_hour_usage' => '180',
            'off_peak_usage' => '95'
        ],
        'network' => [
            'public_ip' => '203.175.42.156',
            'local_ip' => '192.168.1.105',
            'gateway' => '192.168.1.1',
            'dns_primary' => '8.8.8.8',
            'dns_secondary' => '8.8.4.4',
            'subnet_mask' => '255.255.255.0',
            'mac_address' => '00:1B:44:11:3A:B7'
        ],
        'performance' => [
            'uptime_percentage' => 99.2,
            'uptime_days' => 28,
            'total_downtime_month' => '5.7', // hours
            'last_downtime' => '2 hours ago',
            'avg_response_time' => '45', // ms
            'connection_stability' => 'Excellent'
        ],
        'security' => [
            'firewall_status' => 'Active',
            'vpn_status' => 'Inactive',
            'intrusion_attempts' => 3,
            'blocked_ips' => 12,
            'last_security_scan' => '2024-08-17 06:00:00'
        ],
        'devices' => [
            'total_connected' => 8,
            'active_now' => 5,
            'bandwidth_heavy' => 2,
            'guest_devices' => 1
        ]
    ];
}

function getHourlyUsageData() {
    // Simulate hourly usage data for the last 24 hours
    $data = [];
    for ($i = 23; $i >= 0; $i--) {
        $hour = date('H:00', strtotime("-{$i} hours"));
        $data[] = [
            'time' => $hour,
            'download' => rand(50, 200),
            'upload' => rand(10, 50)
        ];
    }
    return $data;
}

function getSpeedTestHistory() {
    // Simulate speed test history for the last 7 days
    $history = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('M j', strtotime("-{$i} days"));
        $history[] = [
            'date' => $date,
            'download' => rand(120, 180),
            'upload' => rand(20, 30),
            'latency' => rand(8, 15)
        ];
    }
    return $history;
}

$network_status = getDetailedNetworkStatus();
$hourly_usage = getHourlyUsageData();
$speed_history = getSpeedTestHistory();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Status - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<style>
.network-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 0;
    margin-bottom: 30px;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-connected {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
    border: 2px solid #28a745;
}

.status-disconnected {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
    border: 2px solid #dc3545;
}

.metric-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    border: none;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
}

.metric-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #dee2e6;
    padding: 20px;
    font-weight: 600;
    color: #495057;
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 10px 0;
    background: linear-gradient(135deg, #667eea, #764ba2);
    background-clip: text;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.metric-unit {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 500;
}

.progress-custom {
    height: 12px;
    border-radius: 10px;
    background: #e9ecef;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-bar-custom {
    height: 100%;
    border-radius: 10px;
    background: linear-gradient(135deg, #28a745, #20c997);
    position: relative;
    transition: width 1s ease;
}

.progress-bar-custom::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.3) 50%, transparent 70%);
    animation: progressShine 2s infinite;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

.info-label {
    font-size: 0.85rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #495057;
    font-family: 'Courier New', monospace;
}

.chart-container {
    position: relative;
    height: 300px;
    background: white;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.speed-test-btn {
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
    border-radius: 50px;
    padding: 12px 30px;
    color: white;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.speed-test-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
    color: white;
}

.device-list {
    max-height: 300px;
    overflow-y: auto;
}

.device-item {
    display: flex;
    align-items: center;
    justify-content: between;
    padding: 12px 15px;
    border-bottom: 1px solid #e9ecef;
    transition: background 0.2s ease;
}

.device-item:hover {
    background: #f8f9fa;
}

.device-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-right: 15px;
}

.alert-custom {
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 5px solid #ffc107;
}

.table-custom {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

.table-custom thead {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}
</style>

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
                    <li class="active">
                        <a href="network_status.php">
                            <i class="bi bi-wifi"></i>
                            <span>Network Status</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php">
                            <i class="bi bi-people"></i>
                            <span>Admins</span>
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
            <!-- Network Status Header -->
            <div class="network-header text-center">
                <div class="container">
                    <h1><i class="bi bi-router"></i> Network Status Dashboard</h1>
                    <p class="lead mb-3">Real-time monitoring of internet connectivity and performance</p>
                    <div class="status-indicator status-<?php echo $network_status['connection']['status']; ?>">
                        <i class="bi bi-wifi"></i>
                        <?php echo ucfirst($network_status['connection']['status']); ?>
                    </div>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Connection Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <i class="bi bi-download text-success" style="font-size: 2rem;"></i>
                            <div class="metric-value"><?php echo $network_status['speed']['download_current']; ?></div>
                            <div class="metric-unit">Mbps Download</div>
                            <small class="text-muted">24h avg: <?php echo $network_status['speed']['download_avg_24h']; ?> Mbps</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <i class="bi bi-upload text-info" style="font-size: 2rem;"></i>
                            <div class="metric-value"><?php echo $network_status['speed']['upload_current']; ?></div>
                            <div class="metric-unit">Mbps Upload</div>
                            <small class="text-muted">24h avg: <?php echo $network_status['speed']['upload_avg_24h']; ?> Mbps</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <i class="bi bi-stopwatch text-warning" style="font-size: 2rem;"></i>
                            <div class="metric-value"><?php echo $network_status['speed']['latency']; ?></div>
                            <div class="metric-unit">ms Latency</div>
                            <small class="text-muted">Jitter: <?php echo $network_status['speed']['jitter']; ?>ms</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card metric-card">
                        <div class="card-body text-center">
                            <i class="bi bi-shield-check text-success" style="font-size: 2rem;"></i>
                            <div class="metric-value"><?php echo $network_status['performance']['uptime_percentage']; ?>%</div>
                            <div class="metric-unit">Uptime</div>
                            <small class="text-muted"><?php echo $network_status['performance']['uptime_days']; ?> days stable</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection Details and Speed Test -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-info-circle"></i> Connection Details</h4>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Provider</div>
                                    <div class="info-value"><?php echo $network_status['connection']['provider']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Connection Type</div>
                                    <div class="info-value"><?php echo $network_status['connection']['type']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Plan</div>
                                    <div class="info-value"><?php echo $network_status['connection']['plan']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Public IP</div>
                                    <div class="info-value"><?php echo $network_status['network']['public_ip']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Local IP</div>
                                    <div class="info-value"><?php echo $network_status['network']['local_ip']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Gateway</div>
                                    <div class="info-value"><?php echo $network_status['network']['gateway']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">DNS Primary</div>
                                    <div class="info-value"><?php echo $network_status['network']['dns_primary']; ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">MAC Address</div>
                                    <div class="info-value"><?php echo $network_status['network']['mac_address']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-speedometer2"></i> Speed Test</h4>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <h5>Peak Speeds (24h)</h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-success">
                                            <i class="bi bi-download"></i>
                                            <strong><?php echo $network_status['speed']['download_peak']; ?></strong> Mbps
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-info">
                                            <i class="bi bi-upload"></i>
                                            <strong><?php echo $network_status['speed']['upload_peak']; ?></strong> Mbps
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button class="btn speed-test-btn" onclick="runSpeedTest()">
                                <i class="bi bi-play-fill"></i> Run Speed Test
                            </button>
                            <div class="mt-3">
                                <small class="text-muted">Packet Loss: <?php echo $network_status['speed']['packet_loss']; ?>%</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-graph-up"></i> 24-Hour Usage</h4>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="usageChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-bar-chart"></i> Monthly Usage</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-download text-success"></i> Downloaded</span>
                                    <strong><?php echo $network_status['usage']['download_month']; ?> GB</strong>
                                </div>
                                <div class="progress-custom">
                                    <div class="progress-bar-custom" style="width: <?php echo ($network_status['usage']['download_month'] / 100) * 100; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="bi bi-upload text-info"></i> Uploaded</span>
                                    <strong><?php echo $network_status['usage']['upload_month']; ?> GB</strong>
                                </div>
                                <div class="progress-custom">
                                    <div class="progress-bar-custom" style="width: <?php echo ($network_status['usage']['upload_month'] / 50) * 100; ?>%"></div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <h6>Peak vs Off-Peak Usage</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-warning">
                                            <strong><?php echo $network_status['usage']['peak_hour_usage']; ?></strong><br>
                                            <small>Peak Hours (MB/h)</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-primary">
                                            <strong><?php echo $network_status['usage']['off_peak_usage']; ?></strong><br>
                                            <small>Off-Peak (MB/h)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Speed History and Security -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-clock-history"></i> 7-Day Speed History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Download</th>
                                            <th>Upload</th>
                                            <th>Latency</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($speed_history as $test): ?>
                                        <tr>
                                            <td><?php echo $test['date']; ?></td>
                                            <td><span class="text-success"><?php echo $test['download']; ?> Mbps</span></td>
                                            <td><span class="text-info"><?php echo $test['upload']; ?> Mbps</span></td>
                                            <td><span class="text-warning"><?php echo $test['latency']; ?> ms</span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-shield-check"></i> Security Status</h4>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-label">Firewall</div>
                                    <div class="info-value text-success">
                                        <i class="bi bi-check-circle"></i> <?php echo $network_status['security']['firewall_status']; ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">VPN Status</div>
                                    <div class="info-value text-secondary">
                                        <i class="bi bi-x-circle"></i> <?php echo $network_status['security']['vpn_status']; ?>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Intrusion Attempts</div>
                                    <div class="info-value text-warning"><?php echo $network_status['security']['intrusion_attempts']; ?> today</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Blocked IPs</div>
                                    <div class="info-value text-danger"><?php echo $network_status['security']['blocked_ips']; ?> total</div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">Last Security Scan: <?php echo date('M j, Y h:i A', strtotime($network_status['security']['last_security_scan'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connected Devices -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card metric-card">
                        <div class="card-header metric-header d-flex justify-content-between align-items-center">
                            <h4><i class="bi bi-devices"></i> Connected Devices</h4>
                            <div>
                                <span class="badge bg-success"><?php echo $network_status['devices']['active_now']; ?> Active</span>
                                <span class="badge bg-secondary"><?php echo $network_status['devices']['total_connected']; ?> Total</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($network_status['devices']['bandwidth_heavy'] > 0): ?>
                            <div class="alert alert-custom">
                                <i class="bi bi-exclamation-triangle"></i>
                                <strong><?php echo $network_status['devices']['bandwidth_heavy']; ?> device(s)</strong> are using high bandwidth
                            </div>
                            <?php endif; ?>
                            <div class="device-list">
                                <div class="device-item">
                                    <div class="device-icon">
                                        <i class="bi bi-tablet"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong>iPad Pro</strong><br>
                                        <small class="text-muted">192.168.1.115 • Low Usage</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success">0.8 MB/s</span>
                                    </div>
                                </div>
                                <div class="device-item">
                                    <div class="device-icon">
                                        <i class="bi bi-router"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong>Guest Device</strong><br>
                                        <small class="text-muted">192.168.1.120 • Guest Network</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary">1.2 MB/s</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Network Diagnostics -->
            <div class="row">
                <div class="col-12">
                    <div class="card metric-card">
                        <div class="card-header metric-header">
                            <h4><i class="bi bi-tools"></i> Network Diagnostics</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <button class="btn btn-outline-primary w-100 mb-2" onclick="pingTest()">
                                        <i class="bi bi-arrow-repeat"></i> Ping Test
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-info w-100 mb-2" onclick="dnsTest()">
                                        <i class="bi bi-search"></i> DNS Lookup Test
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-outline-warning w-100 mb-2" onclick="traceRoute()">
                                        <i class="bi bi-diagram-3"></i> Trace Route
                                    </button>
                                </div>
                            </div>
                            <div id="diagnosticResults" class="mt-3" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <h6>Diagnostic Results:</h6>
                                        <pre id="resultOutput" style="background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 0.9rem;"></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        // Usage Chart
        const ctx = document.getElementById('usageChart').getContext('2d');
        const usageChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($hourly_usage, 'time')); ?>,
                datasets: [{
                    label: 'Download (MB)',
                    data: <?php echo json_encode(array_column($hourly_usage, 'download')); ?>,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Upload (MB)',
                    data: <?php echo json_encode(array_column($hourly_usage, 'upload')); ?>,
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Usage (MB)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time (24h)'
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 3,
                        hoverRadius: 6
                    }
                }
            }
        });

        // Speed Test Function
        function runSpeedTest() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Testing...';
            btn.disabled = true;
            
            // Simulate speed test
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Test Complete';
                btn.classList.remove('speed-test-btn');
                btn.classList.add('btn', 'btn-success');
                
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.classList.remove('btn', 'btn-success');
                    btn.classList.add('speed-test-btn');
                }, 2000);
            }, 3000);
        }

        // Diagnostic Functions
        function pingTest() {
            showDiagnosticResult('Ping Test', `PING google.com (172.217.163.14): 56 data bytes
64 bytes from 172.217.163.14: icmp_seq=0 ttl=115 time=12.456 ms
64 bytes from 172.217.163.14: icmp_seq=1 ttl=115 time=11.789 ms
64 bytes from 172.217.163.14: icmp_seq=2 ttl=115 time=12.123 ms
64 bytes from 172.217.163.14: icmp_seq=3 ttl=115 time=11.934 ms

--- google.com ping statistics ---
4 packets transmitted, 4 packets received, 0.0% packet loss
round-trip min/avg/max/stddev = 11.789/12.076/12.456/0.252 ms`);
        }

        function dnsTest() {
            showDiagnosticResult('DNS Lookup Test', `DNS Resolution Test:
google.com -> 172.217.163.14 (12ms)
facebook.com -> 157.240.3.35 (8ms)
cloudflare.com -> 104.16.124.96 (5ms)
github.com -> 140.82.114.4 (15ms)

Average DNS resolution time: 10ms
DNS Server: 8.8.8.8 (Google DNS)
Status: All lookups successful`);
        }

        function traceRoute() {
            showDiagnosticResult('Trace Route', `traceroute to google.com (172.217.163.14), 30 hops max:
1  192.168.1.1 (192.168.1.1)  1.234 ms  1.123 ms  1.456 ms
2  10.0.0.1 (10.0.0.1)  5.678 ms  5.432 ms  5.789 ms
3  203.175.42.1 (203.175.42.1)  12.345 ms  12.123 ms  12.567 ms
4  203.175.40.1 (203.175.40.1)  15.678 ms  15.432 ms  15.789 ms
5  72.14.194.18 (72.14.194.18)  18.234 ms  18.123 ms  18.345 ms
6  172.217.163.14 (172.217.163.14)  20.456 ms  20.234 ms  20.567 ms

Trace complete in 6 hops`);
        }

        function showDiagnosticResult(testType, result) {
            const resultsDiv = document.getElementById('diagnosticResults');
            const outputPre = document.getElementById('resultOutput');
            
            outputPre.textContent = `${testType} Results:\n\n${result}`;
            resultsDiv.style.display = 'block';
            
            // Scroll to results
            resultsDiv.scrollIntoView({ behavior: 'smooth' });
        }

        // Auto-refresh network status every 30 seconds
        setInterval(() => {
            // In a real implementation, you would make an AJAX call to refresh the data
            console.log('Refreshing network status...');
        }, 30000);

        // Add spin animation for loading states
        const style = document.createElement('style');
        style.textContent = `
            .spin {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

