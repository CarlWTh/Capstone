<<<<<<< HEAD
<?php
require_once 'config.php';
checkAdminAuth();

$stats = [];
$stats['total_deposits'] = $conn->query("SELECT COUNT(*) FROM Transactions")->fetch_row()[0];
$stats['total_bottles'] = $conn->query("SELECT SUM(bottle_count) FROM Transactions")->fetch_row()[0];
$stats['total_vouchers'] = $conn->query("SELECT COUNT(*) FROM Voucher")->fetch_row()[0];
$stats['active_sessions'] = $conn->query("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL")->fetch_row()[0];

$recent_deposits = $conn->query("
    SELECT
        t.transaction_id,
        t.bottle_count,
        v.voucher_code,
        t.created_at AS timestamp
    FROM Transactions t
    JOIN User u ON t.user_id = u.user_id
    LEFT JOIN Voucher v ON v.transaction_id = t.transaction_id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$bin_status = $conn->query("
    SELECT trashbin_id AS bin_id,
           -- Assuming 'capacity' and 'current_level' are columns in Trashbin
           -- If not, you'll need to adjust or add them to the Trashbin table.
           -- For this example, I'll use placeholder values if not present in your schema.
           -- If they are, replace 100 with 'capacity' and 50 with 'current_level'.
           100 AS capacity, -- Placeholder: Replace with actual capacity column from Trashbin
           fill_level_percent AS current_level, -- Using fill_level_percent for current_level
           status
    FROM Trashbin
    ORDER BY status DESC, fill_level_percent DESC
")->fetch_all(MYSQLI_ASSOC);

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

                <div class="profile-dropdown">
                    <div class="dropdown-header" id="profileDropdown">

                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="dropdown-content" id="profileDropdownContent">

                        <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </div>
                </div>
            </div>

            <?php displayFlashMessage(); ?>

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

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Bottle Deposits</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Bottle Count</th>
                                            <th>Time Credits</th>
                                            <th>Voucher</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_deposits as $deposit): ?>
                                            <tr>
                                                <td><?php echo $deposit['transaction_id']; ?></td>
                                                <td><?php echo $deposit['bottle_count']; ?></td>
                                                <td><?php echo ($deposit['bottle_count'] * 5) . ' min'; ?></td>
                                                <td><?php echo htmlspecialchars($deposit['voucher_code'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y h:i A', strtotime($deposit['timestamp'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="bottle_deposits.php" class="btn btn-outline-primary">View All Deposits</a>
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
                                                                      echo $bin['status'] == 'full' ? 'danger' : ($bin['status'] == 'partial' ? 'warning' : 'success');
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
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        document.getElementById('profileDropdown').addEventListener('click', function() {
            document.getElementById('profileDropdownContent').classList.toggle('show-dropdown');
        });

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
=======
<?php
require_once 'config.php';
checkAdminAuth(); // This function is defined in config.php

// Get dashboard statistics
$stats = [];
// Use 'Transactions' table for total deposits and bottles
$stats['total_deposits'] = $conn->query("SELECT COUNT(*) FROM Transactions")->fetch_row()[0];
$stats['total_bottles'] = $conn->query("SELECT SUM(bottle_count) FROM Transactions")->fetch_row()[0];
// Use 'Voucher' table for total vouchers
$stats['total_vouchers'] = $conn->query("SELECT COUNT(*) FROM Voucher")->fetch_row()[0];
// Use 'UserSessions' table for active sessions
$stats['active_sessions'] = $conn->query("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL")->fetch_row()[0];

// Get recent deposits (now from 'Transactions' table)
$recent_deposits = $conn->query("
    SELECT 
        t.transaction_id, 
        t.bottle_count, 
        v.voucher_code, 
        t.created_at AS timestamp
    FROM Transactions t
    JOIN User u ON t.user_id = u.user_id
    LEFT JOIN Voucher v ON v.transaction_id = t.transaction_id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Get bin status from 'Trashbin' table
$bin_status = $conn->query("
    SELECT trashbin_id AS bin_id,
           -- Assuming 'capacity' and 'current_level' are columns in Trashbin
           -- If not, you'll need to adjust or add them to the Trashbin table.
           -- For this example, I'll use placeholder values if not present in your schema.
           -- If they are, replace 100 with 'capacity' and 50 with 'current_level'.
           100 AS capacity, -- Placeholder: Replace with actual capacity column from Trashbin
           fill_level_percent AS current_level, -- Using fill_level_percent for current_level
           status
    FROM Trashbin
    ORDER BY status DESC, fill_level_percent DESC
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
                                            <th>Bottle Count</th>
                                            <th>Time Credits</th>
                                            <th>Voucher</th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_deposits as $deposit): ?>
                                            <tr>
                                                <td><?php echo $deposit['transaction_id']; ?></td>
                                                <td><?php echo $deposit['bottle_count']; ?></td>
                                                <td><?php echo ($deposit['bottle_count'] * 5) . ' min'; ?></td> <!-- Example time credit logic -->
                                                <td><?php echo htmlspecialchars($deposit['voucher_code'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y h:i A', strtotime($deposit['timestamp'])); ?></td>
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
                                                                    echo $bin['status'] == 'full' ? 'danger' : ($bin['status'] == 'partial' ? 'warning' : 'success');
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
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
