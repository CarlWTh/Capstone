<?php
require_once 'config.php';
checkAdminAuth();

// Get device status
$device_status = [];
$result = $conn->query("SELECT device_name, status, last_checked FROM device_status ORDER BY device_name");
if ($result) {
    $device_status = $result->fetch_all(MYSQLI_ASSOC);
}

// Get system logs
$system_logs = [];
$result = $conn->query("SELECT timestamp, event, severity FROM system_logs ORDER BY timestamp DESC LIMIT 10");
if ($result) {
    $system_logs = $result->fetch_all(MYSQLI_ASSOC);
}

// Get performance data for chart (simulated)
$performance_data = [
    'labels' => ['00:00', '04:00', '08:00', '12:00', '16:00', '20:00'],
    'cpu' => [30, 45, 50, 65, 40, 35],
    'memory' => [40, 55, 60, 70, 50, 45]
];
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
                <h2>System Monitoring</h2>
                <div class="user-info">
                    <div class="profile-dropdown">
                        <div class="dropdown-header" id="profileDropdownBtn">
                            <span>Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?></span>
                            <img src="/api/placeholder/40/40" alt="Admin Avatar" class="avatar-img">
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
                            <select id="log-filter">
                                <option>Last 24 Hours</option>
                                <option>Last 7 Days</option>
                                <option>Last 30 Days</option>
                            </select>
                            <button class="export-btn">Export Logs</button>
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
</body>

</html>