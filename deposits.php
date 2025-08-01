<?php
require_once 'config.php';
checkAdminAuth(); // This function is defined in config.php

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the base query for 'Transactions' table
$query = "SELECT t.transaction_id AS deposit_id, t.created_at AS timestamp, t.bottle_count,
                 u.mac_address AS anonymous_token, -- Using mac_address as a pseudo-token for display
                 'processed' AS status -- Assuming all transactions are 'processed' for now
          FROM Transactions t
          LEFT JOIN User u ON t.user_id = u.user_id";

// Add status filter if it's set
// Note: The 'Transactions' table in the new schema does not have a 'status' column.
// If you need a status for transactions, you'll need to add it to the 'Transactions' table.
// For now, I'm hardcoding 'processed' status for display.
$where_clause = '';
// if (!empty($status_filter)) {
//     $where_clause = " WHERE t.status = '$status_filter'"; // This would require 'status' column in 'Transactions'
// }

// Order and limit clauses
$order_limit_clause = " ORDER BY t.created_at DESC LIMIT $per_page OFFSET $offset"; // Use created_at

// Full query for data
$full_query = $query . $where_clause . $order_limit_clause;

// Query for total count (without limit)
$count_query = "SELECT COUNT(*) FROM Transactions t" . $where_clause; // Count from 'Transactions'

// Fetch data
$deposits = $conn->query($full_query)->fetch_all(MYSQLI_ASSOC);

// Fetch total count
$total_deposits = $conn->query($count_query)->fetch_row()[0];
$total_pages = ceil($total_deposits / $per_page);

// Log activity
logAdminActivity('Deposits Access', 'Viewed deposits list');
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
                <li class="active"> <!-- This page is about deposits, so it should be active -->
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
                <li>
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
    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-recycle"></i> Bottle Deposits</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    
                    <span><?= htmlspecialchars($_SESSION['admin_username']) ?></span> <!-- Changed to admin_username -->
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    
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
                    <!-- Status filter removed as 'Transactions' table doesn't have a 'status' column -->
                    <!-- If you add a 'status' column to 'Transactions', you can re-enable this. -->
                    <!-- <select id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processed" <?php echo $status_filter == 'processed' ? 'selected' : ''; ?>>Processed</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select> -->
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
                                <th>User MAC</th> <!-- Changed from Session to User MAC -->
                                <th>Bottle Count</th> <!-- Changed from Count to Bottle Count -->
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($deposits)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle"></i> No deposits found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deposits as $deposit): ?>
                                    <tr>
                                        <td><?php echo $deposit['deposit_id']; ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($deposit['timestamp'])); ?></td>
                                        <td>
                                            <span class="mac-address"><?php echo htmlspecialchars($deposit['anonymous_token']); ?></span>
                                        </td>
                                        <td><?php echo $deposit['bottle_count']; ?></td>
                                        <td>
                                            <span class="status green">Processed</span> <!-- Hardcoded 'Processed' -->
                                        </td>
                                        <td>
                                            <a href="vouchers.php?transaction_id=<?php echo $deposit['deposit_id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-ticket-perforated"></i> View Vouchers
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" class="btn btn-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?><?= !empty($status_filter) ? '&status=' . $status_filter : '' ?>" class="btn btn-secondary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
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

        // Status filter (commented out in HTML, but kept JS for reference if re-enabled)
        // document.getElementById('status-filter').addEventListener('change', function() {
        //     const status = this.value;
        //     window.location.href = `deposits.php?status=${status}`;
        // });
    </script>
</body>

</html>
