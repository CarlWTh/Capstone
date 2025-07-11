<?php
require_once 'config.php';
checkAdminAuth();

// Pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the base query
$query = "SELECT d.deposit_id, d.timestamp, d.bottle_count, d.status, s.anonymous_token
          FROM BottleDeposit d
          LEFT JOIN StudentSession s ON d.session_id = s.session_id";

// Add status filter if it's set
$where_clause = '';
if (!empty($status_filter)) {
    $where_clause = " WHERE d.status = '$status_filter'";
}

// Order and limit clauses
$order_limit_clause = " ORDER BY d.timestamp DESC LIMIT $per_page OFFSET $offset";

// Full query for data
$full_query = $query . $where_clause . $order_limit_clause;

// Query for total count (without limit)
$count_query = "SELECT COUNT(*) FROM BottleDeposit d" . $where_clause;

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
            <h2>Bottle Deposits</h2>
        </div>
        <div class="card">
            <div class="card-header">
                <h3>All Deposits</h3>
                <div class="filter-options">
                    <select id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processed" <?php echo $status_filter == 'processed' ? 'selected' : ''; ?>>Processed</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                                    <td>
                                        <span class="status <?php
                                                            echo $deposit['status'] == 'processed' ? 'green' : ($deposit['status'] == 'rejected' ? 'red' : 'orange');
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
                    <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>" class="btn btn-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>" class="btn btn-secondary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
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

            </div>
</body>

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