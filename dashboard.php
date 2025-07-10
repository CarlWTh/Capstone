
<?php
require_once 'config.php';
checkAdminAuth();

// Get dashboard statistics
$stats = [];
$stats['total_deposits'] = $conn->query("SELECT COUNT(*) FROM BottleDeposit")->fetch_row()[0];
$stats['total_bottles'] = $conn->query("SELECT SUM(bottle_count) FROM BottleDeposit")->fetch_row()[0];
$stats['total_vouchers'] = $conn->query("SELECT COUNT(*) FROM Voucher")->fetch_row()[0];
$stats['active_sessions'] = $conn->query("SELECT COUNT(*) FROM InternetSession WHERE end_time IS NULL")->fetch_row()[0];

// Get recent deposits
$recent_deposits = $conn->query("
    SELECT d.deposit_id, d.timestamp, d.bottle_count, d.status, t.bin_id
    FROM BottleDeposit d
    JOIN TrashBin t ON d.bin_id = t.bin_id
    ORDER BY d.timestamp DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get bin status
$bin_status = $conn->query("
    SELECT bin_id, capacity, current_level, status 
    FROM TrashBin 
    ORDER BY status DESC, current_level DESC
")->fetch_all(MYSQLI_ASSOC);

// Log dashboard access
logAdminActivity('Dashboard Access', 'Accessed admin dashboard');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/styles.css">
</head>
<body>
    <div class="dashboard-container">
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

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <div class="main-header">
                <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>

                <div class="profile-dropdown">
                    <div class="dropdown-header" id="profileDropdown">
                        <img src="./img/avatar.jpg" alt="Profile" class="avatar-img">
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="dropdown-content" id="profileDropdownContent">
                        <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
                        <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-recycle card-icon"></i>
                            <h5 class="card-title">Total Deposits</h5>
                            <h2><?php echo $stats['total_deposits']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body text-center">
                            <i class="bi bi-cup-straw card-icon"></i>
                            <h5 class="card-title">Total Bottles</h5>
                            <h2><?php echo $stats['total_bottles']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body text-center">
                            <i class="bi bi-ticket-perforated card-icon"></i>
                            <h5 class="card-title">Vouchers Issued</h5>
                            <h2><?php echo $stats['total_vouchers']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body text-center">
                            <i class="bi bi-wifi card-icon"></i>
                            <h5 class="card-title">Active Sessions</h5>
                            <h2><?php echo $stats['active_sessions']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Deposits and Bin Status -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Bottle Deposits</h3>
                            <div class="filter-options">
                                <input type="text" id="searchDeposits" placeholder="Search deposits...">
                                <button class="btn btn-sm btn-outline-primary">Filter</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Time</th>
                                            <th>Type</th>
                                            <th>Count</th>
                                            
                                            <th>Status</th>
                                            <th>Bin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_deposits as $deposit): ?>
                                        <tr>
                                            <td><?php echo $deposit['deposit_id']; ?></td>
                                            <td><?php echo date('M j, H:i', strtotime($deposit['timestamp'])); ?></td>
                                            <td><?php echo htmlspecialchars($deposit['bottle_type_id']); ?></td>
                                            <td><?php echo $deposit['bottle_count']; ?></td>
                                            
                                            <td>
                                                <span class="status <?php 
                                                        echo $deposit['status'] == 'processed' ? 'green' : 
                                                            ($deposit['status'] == 'rejected' ? 'red' : 'orange'); 
                                                    ?>">
                                                    <?php echo ucfirst($deposit['status']); ?>
                                                </span>
                                            </td>
                                            <td>Bin #<?php echo $deposit['bin_id']; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="deposits.php" class="btn btn-outline-primary">View All Deposits</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3>Bin Status</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($bin_status as $bin): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Bin #<?php echo $bin['bin_id']; ?></span>
                                    <span><?php echo ($bin['capacity'] != 0) ? round(($bin['current_level'] / $bin['capacity']) * 100) : 0; ?>%</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar bg-<?php 
                                        echo $bin['status'] == 'full' ? 'danger' : 
                                             ($bin['status'] == 'partial' ? 'warning' : 'success'); 
                                    ?>" 
                                    role="progressbar" 
                                    style="width: <?php echo round(($bin['current_level'] / $bin['capacity']) * 100); ?>%" 
                                    aria-valuenow="<?php echo $bin['current_level']; ?>" 
                                    aria-valuemin="0" 
                                    aria-valuemax="<?php echo $bin['capacity']; ?>">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Status: <?php echo ucfirst($bin['status']); ?> - 
                                    <?php echo $bin['current_level']; ?> / <?php echo $bin['capacity']; ?> kg
                                </small>
                            </div>
                            <?php endforeach; ?>
                            <a href="bins.php" class="btn btn-outline-primary mt-2">Manage Bins</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        // Toggle profile dropdown
        document.getElementById('profileDropdown').addEventListener('click', function() {
            document.getElementById('profileDropdownContent').classList.toggle('show-dropdown');
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                const dropdown = document.getElementById('profileDropdownContent');
                if (dropdown.classList.contains('show-dropdown')) {
                    dropdown.classList.remove('show-dropdown');
                }
            }
        });
    </script>
</body>
</html>
