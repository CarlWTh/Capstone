<?php
require_once 'config.php';
checkAdminAuth();

// Get all deposits with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$total_deposits = $conn->query("SELECT COUNT(*) FROM BottleDeposit")->fetch_row()[0];
$total_pages = ceil($total_deposits / $per_page);

$deposits = $conn->query("
    SELECT d.deposit_id, d.timestamp, d.bottle_count, d.status, t.bin_id, s.anonymous_token
    FROM BottleDeposit d
    JOIN TrashBin t ON d.bin_id = t.bin_id
    JOIN StudentSession s ON d.session_id = s.session_id
    ORDER BY d.timestamp DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Log activity
logAdminActivity('Deposits Access', 'Viewed bottle deposits list');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottle Deposits - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar"> <div class="sidebar-header"> <div class="logo"> <h1><?php echo SITE_NAME; ?></h1> <span class="logo-short"></span> </div> <button class="sidebar-toggle" id="sidebarToggle"> <i class="bi bi-list"></i> </button> </div> <nav> <ul> <li class=""> <a href="dashboard.php"> <i class="bi bi-speedometer2"></i> <span>Dashboard</span> </a> </li> <li class="active"> <a href="deposits.php"> <i class="bi bi-recycle"></i> <span>Bottle Deposits</span> </a> </li> <li> <a href="vouchers.php"> <i class="bi bi-ticket-perforated"></i> <span>Vouchers</span> </a> </li> <li> <a href="bins.php"> <i class="bi bi-trash"></i> <span>Trash Bins</span> </a> </li> <li> <a href="student_sessions.php"> <i class="bi bi-phone"></i> <span class="menu-text">Student Sessions</span> </a> </li> <li> <a href="sessions.php"> <i class="bi bi-wifi"></i> <span>Internet Sessions</span> </a> </li> <li> <a href="users.php"> <i class="bi bi-people"></i> <span>Users</span> </a> </li> <li> <a href="activity_logs.php"> <i class="bi bi-clock-history"></i> <span>Activity Logs</span> </a> </li> <li> <a href="logout.php"> <i class="bi bi-box-arrow-right"></i> <span>Logout</span> </a> </li> </ul> </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h2>Bottle Deposits</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="avatar-img">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    <a href="#"><i class="bi bi-person"></i> Profile</a>
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <h3>All Deposits</h3>
                <div class="filter-options">
                    <select id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="processed">Processed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <button class="export-btn">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Timestamp</th>
                                <th>Session</th>
                                
                                <th>Count</th>
                              
                                <th>Bin</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $deposit): ?>
                            <tr>
                                <td><?php echo $deposit['deposit_id']; ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($deposit['timestamp'])); ?></td>
                                <td><?php echo substr($deposit['anonymous_token'], 0, 8) . '...'; ?></td>
                                
                                <td><?php echo $deposit['bottle_count']; ?></td>
                              
                                <td>Bin #<?php echo $deposit['bin_id']; ?></td>
                                <td>
                                <span class="status <?php 
                                        echo $deposit['status'] == 'processed' ? 'green' : 
                                            ($deposit['status'] == 'rejected' ? 'red' : 'orange'); 
                                    ?>">
                                        <?php echo ucfirst($deposit['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn btn-secondary" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                        <i class="bi bi-chevron-left"></i> Previous
                    </button>
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <button class="btn btn-secondary" <?php echo $page >= $total_pages ? 'disabled' : ''; ?>>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
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

        // Status filter
        document.getElementById('status-filter').addEventListener('change', function() {
            const status = this.value;
            window.location.href = `deposits.php?status=${status}`;
        });
    </script>
</body>
</html>