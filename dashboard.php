<?php
session_start();
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Access denied. Admin privileges required.");
}

// Get total bottles and credits
$total_bottles = 0;
$total_credits = 0;

$result = $conn->query("SELECT SUM(bottle_count) as total_bottles, SUM(credits_earned) as total_credits FROM transactions");
if ($result && $row = $result->fetch_assoc()) {
    $total_bottles = $row['total_bottles'] ?? 0;
    $total_credits = $row['total_credits'] ?? 0;
}

// Get recent transactions
$recent_transactions = [];
$result = $conn->query("SELECT transaction_date, bottle_count, credits_earned FROM transactions ORDER BY transaction_date DESC LIMIT 5");
if ($result) {
    $recent_transactions = $result->fetch_all(MYSQLI_ASSOC);
}

// Get system status
$system_status = [];
$result = $conn->query("SELECT device_name, status FROM system_status");
if ($result) {
    $system_status = $result->fetch_all(MYSQLI_ASSOC);
}

// Get bottle collection data for chart
$chart_data = [];
$result = $conn->query("
    SELECT DATE(transaction_date) as date, SUM(bottle_count) as bottles 
    FROM transactions 
    WHERE transaction_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(transaction_date)
    ORDER BY date
");
if ($result) {
    $chart_data = $result->fetch_all(MYSQLI_ASSOC);
}

// Prepare chart labels and data
$chart_labels = [];
$chart_values = [];
foreach ($chart_data as $row) {
    $chart_labels[] = $row['date'];
    $chart_values[] = $row['bottles'];
}

// Determine bin status (simplified for demo)
$bin_status = 'Half-Full'; // In a real system, this would come from IoT device data
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottle Recycling Admin Dashboard</title>
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
                        <a href="/dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="/transactions.php">
                            <i class='bx bx-transfer-alt'></i>
                            <span class="menu-text">Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="/monitoring.php">
                            <i class='bx bx-line-chart'></i>
                            <span class="menu-text">System Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="/settings.php">
                            <i class='bx bx-cog'></i>
                            <span class="menu-text">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="/reports.php">
                            <i class='bx bxs-report'></i>
                            <span class="menu-text">Reports</span>
                        </a>
                    </li>
                    <li class="logout">
                        <a href="logout.php">
                            <i class='bx bx-log-out'></i>
                            <span class="menu-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h2>Dashboard Overview</h2>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <img src="/api/placeholder/40/40" alt="Admin Avatar">
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="card overview-section">
                    <h2>Overview</h2>
                    <div class="stat-grid">
                        <div class="stat-item">
                            <h3>Total Bottles</h3>
                            <p><?php echo number_format($total_bottles); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3>Total Credits</h3>
                            <p><?php echo number_format($total_credits); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3>Bin Status</h3>
                            <p class="status half-full"><?php echo htmlspecialchars($bin_status); ?></p>
                        </div>
                    </div>
                </div>

                <div class="card system-health">
                    <h2>System Health</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($system_status as $device): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                <td class="status green"><?php echo htmlspecialchars($device['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card transaction-logs">
                    <div class="card-header">
                        <h2>Recent Transactions</h2>
                        <div class="filter-options">
                            <select>
                                <option>Last 7 Days</option>
                                <option>Last 30 Days</option>
                                <option>This Month</option>
                            </select>
                            <button class="export-btn">Export CSV</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Bottles</th>
                                <th>Credits</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['bottle_count']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['credits_earned']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card analytics-chart">
                    <h2>Bottles Collected Over Time</h2>
                    <canvas id="bottlesChart"></canvas>
                    <script>
                        const ctx = document.getElementById('bottlesChart').getContext('2d');
                        const bottlesChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($chart_labels); ?>,
                                datasets: [{
                                    label: 'Bottles Collected',
                                    data: <?php echo json_encode($chart_values); ?>,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1,
                                    tension: 0.1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </main>
    </div>

    <script src="/js/dashboard.js"></script>
</body>
</html>