<?php
require_once 'config.php';
checkAdminAuth();

$user_avatar = getUserAvatar($_SESSION['user_id'], $conn);

// Handle log filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : '24hours';
$where = '';

switch ($filter) {
    case '7days':
        $where = " WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30days':
        $where = " WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case '24hours':
    default:
        $where = " WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        break;
}

// Get device status
$device_status = [];
$result = $conn->query("SELECT device_name, status, last_checked FROM device_status ORDER BY device_name");
if ($result) {
    $device_status = $result->fetch_all(MYSQLI_ASSOC);
}

// Get system logs with filter
$system_logs = [];
$result = $conn->query("SELECT timestamp, event, severity FROM system_logs $where ORDER BY timestamp DESC LIMIT 10");
if ($result) {
    $system_logs = $result->fetch_all(MYSQLI_ASSOC);
}

// Get performance data for chart (now based on actual data)
$performance_data = [
    'labels' => [],
    'cpu' => [],
    'memory' => []
];

// Get hourly performance data for the last 24 hours
$result = $conn->query("
    SELECT 
        DATE_FORMAT(timestamp, '%H:00') as hour,
        AVG(cpu_usage) as avg_cpu,
        AVG(memory_usage) as avg_memory
    FROM performance_metrics
    WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY DATE_FORMAT(timestamp, '%H:00')
    ORDER BY hour
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $performance_data['labels'][] = $row['hour'];
        $performance_data['cpu'][] = $row['avg_cpu'];
        $performance_data['memory'][] = $row['avg_memory'];
    }
}

// Handle export logs
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="system_logs_'.date('Y-m-d').'.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Timestamp', 'Event', 'Severity']);
    
    $export_query = "SELECT timestamp, event, severity FROM system_logs $where ORDER BY timestamp DESC";
    $export_result = $conn->query($export_query);
    
    if ($export_result) {
        while ($row = $export_result->fetch_assoc()) {
            fputcsv($output, [
                $row['timestamp'],
                $row['event'],
                $row['severity']
            ]);
        }
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring - Bottle Recycling Admin</title>
    <link rel="stylesheet" href="/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li>
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
                    <li class="active">
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
                <h2>System Monitoring</h2>
                <div class="user-info">
                    <div class="profile-dropdown">
                        <div class="dropdown-header" id="profileDropdownBtn">
                            <span>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="Admin Avatar" class="avatar-img">
                            <i class='bx bx-chevron-down'></i>
                        </div>
                        <div class="dropdown-content" id="profileDropdown">
                            <a href="edit_profile.php"><i class='bx bx-user'></i> Edit Profile</a>
                            <a href="change_avatar.php"><i class='bx bx-image'></i> Change Avatar</a>
                            <a href="logout.php"><i class='bx bx-log-out'></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="card system-health">
                    <h2>Detailed System Health</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Status</th>
                                <th>Last Checked</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($device_status as $device): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                    <td class="status <?php echo strtolower($device['status']) === 'operational' ? 'green' : 'red'; ?>">
                                        <?php echo htmlspecialchars($device['status']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($device['last_checked']); ?></td>
                                    <td><button class="btn-secondary">Details</button></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card analytics-chart">
                    <h2>System Performance</h2>
                    <canvas id="performanceChart"></canvas>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('performanceChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'line',
                                data: {
                                    labels: <?php echo json_encode($performance_data['labels']); ?>,
                                    datasets: [{
                                        label: 'CPU Usage (%)',
                                        data: <?php echo json_encode($performance_data['cpu']); ?>,
                                        borderColor: '#3498db',
                                        backgroundColor: 'rgba(52, 152, 219, 0.2)',
                                        borderWidth: 3,
                                        tension: 0.4
                                    }, {
                                        label: 'Memory Usage (%)',
                                        data: <?php echo json_encode($performance_data['memory']); ?>,
                                        borderColor: '#2ecc71',
                                        backgroundColor: 'rgba(46, 204, 113, 0.2)',
                                        borderWidth: 3,
                                        tension: 0.4
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            display: true,
                                            position: 'top'
                                        }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            max: 100
                                        }
                                    }
                                }
                            });
                        });
                    </script>
                </div>

                <div class="card transaction-logs">
                    <div class="card-header">
                        <h2>Recent System Logs</h2>
                        <div class="filter-options">
                            <select id="log-filter" onchange="window.location.href='monitoring.php?filter='+this.value">
                                <option value="24hours" <?php echo $filter === '24hours' ? 'selected' : ''; ?>>Last 24 Hours</option>
                                <option value="7days" <?php echo $filter === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30days" <?php echo $filter === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                            </select>
                            <button class="export-btn" onclick="exportLogs()">Export Logs</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Event</th>
                                <th>Severity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($system_logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['timestamp']); ?></td>
                                    <td><?php echo htmlspecialchars($log['event']); ?></td>
                                    <td class="status <?php echo strtolower($log['severity']) === 'info' ? 'green' : (strtolower($log['severity']) === 'warning' ? 'yellow' : 'red'); ?>">
                                        <?php echo htmlspecialchars($log['severity']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <script>
                function exportLogs() {
                    const filter = document.getElementById('log-filter').value;
                    window.location.href = 'monitoring.php?export=1&filter=' + filter;
                }
                
                document.addEventListener('DOMContentLoaded', function() {
                    // Profile dropdown functionality
                    const profileDropdownBtn = document.getElementById('profileDropdownBtn');
                    const profileDropdown = document.getElementById('profileDropdown');
                    
                    if (profileDropdownBtn && profileDropdown) {
                        profileDropdownBtn.addEventListener('click', function() {
                            profileDropdown.classList.toggle('show-dropdown');
                        });
                        
                        // Close the dropdown if clicked outside
                        window.addEventListener('click', function(event) {
                            if (!event.target.closest('.profile-dropdown')) {
                                profileDropdown.classList.remove('show-dropdown');
                            }
                        });
                    }
                    
                    // Existing sidebar toggle functionality
                    const sidebarToggle = document.getElementById('sidebar-toggle');
                    const dashboardContainer = document.querySelector('.dashboard-container');
                    
                    if (sidebarToggle) {
                        sidebarToggle.addEventListener('click', function() {
                            dashboardContainer.classList.toggle('sidebar-collapsed');
                        });
                    }
                });
            </script>
        </main>
    </div>
</body>
</html>