<?php
require_once 'config.php';
checkAdminAuth();

// Get monthly summary data
$monthly_summary = [
    'total_bottles' => 4752,
    'total_credits' => 23760,
    'active_recyclers' => 342
];

// Get recycling trends data
$recycling_trends = [
    'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    'bottles' => [850, 1200, 1500, 1100, 1350, 1600],
    'credits' => [4250, 6000, 7500, 5500, 6750, 8000]
];

// Get available reports
$available_reports = [];
$result = $conn->query("SELECT report_type, period, generated_on, file_path FROM reports ORDER BY generated_on DESC LIMIT 5");
if ($result) {
    $available_reports = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Bottle Recycling Admin</title>
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
                    <li class="active">
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
                <h2>Reports</h2>
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
                <div class="card overview-section">
                    <h2>Monthly Summary</h2>
                    <div class="stat-grid">
                        <div class="stat-item">
                            <h3>Total Bottles</h3>
                            <p><?php echo number_format($monthly_summary['total_bottles']); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3>Total Credits</h3>
                            <p><?php echo number_format($monthly_summary['total_credits']); ?></p>
                        </div>
                        <div class="stat-item">
                            <h3>Active Recyclers</h3>
                            <p><?php echo number_format($monthly_summary['active_recyclers']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="card analytics-chart">
                    <h2>Recycling Trends</h2>
                    <canvas id="recyclingTrendsChart"></canvas>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('recyclingTrendsChart').getContext('2d');
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels: <?php echo json_encode($recycling_trends['labels']); ?>,
                                    datasets: [{
                                        label: 'Bottles Recycled',
                                        data: <?php echo json_encode($recycling_trends['bottles']); ?>,
                                        backgroundColor: '#3498db',
                                        borderColor: '#2980b9',
                                        borderWidth: 1
                                    }, {
                                        label: 'Credits Issued',
                                        data: <?php echo json_encode($recycling_trends['credits']); ?>,
                                        backgroundColor: '#2ecc71',
                                        borderColor: '#27ae60',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    scales: {
                                        y: {
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });
                        });
                    </script>
                </div>

                <div class="card transaction-logs">
                    <div class="card-header">
                        <h2>Detailed Reports</h2>
                        <div class="filter-options">
                            <select id="report-type">
                                <option value="monthly">Monthly Report</option>
                                <option value="quarterly">Quarterly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                            <select id="report-period">
                                <option value="last_month">Last Month</option>
                                <option value="last_quarter">Last Quarter</option>
                                <option value="last_year">Last Year</option>
                            </select>
                            <button id="generate-pdf" class="export-btn">Generate PDF</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Report Type</th>
                                <th>Period</th>
                                <th>Generated On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_reports as $report): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($report['report_type']); ?></td>
                                    <td><?php echo htmlspecialchars($report['period']); ?></td>
                                    <td><?php echo htmlspecialchars($report['generated_on']); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" class="btn-secondary" download>Download</a>
                                        <a href="<?php echo htmlspecialchars($report['file_path']); ?>" class="btn-secondary" target="_blank">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($available_reports)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No reports available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const dashboardContainer = document.querySelector('.dashboard-container');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    dashboardContainer.classList.toggle('sidebar-collapsed');
                });
            }
            
            // PDF generation functionality
            const generatePdfBtn = document.getElementById('generate-pdf');
            const reportTypeSelect = document.getElementById('report-type');
            const reportPeriodSelect = document.getElementById('report-period');
            
            if (generatePdfBtn) {
                generatePdfBtn.addEventListener('click', function() {
                    const reportType = reportTypeSelect.value;
                    const reportPeriod = reportPeriodSelect.value;
                    
                    // Redirect to the PDF generation script with parameters
                    window.location.href = `generate_pdf.php?type=${reportType}&period=${reportPeriod}`;
                });
            }
        });
    </script>
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