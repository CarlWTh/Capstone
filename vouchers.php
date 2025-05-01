<?php
require_once 'config.php';
checkAdminAuth();


// Get all vouchers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total_vouchers = $conn->query("SELECT COUNT(*) FROM Voucher")->fetch_row()[0];
$total_pages = ceil($total_vouchers / $per_page);

$vouchers = $conn->query("
    SELECT v.voucher_id, v.code, v.internet_minutes, v.expiry_time, v.is_used,
           d.timestamp as deposit_time
    FROM Voucher v
    JOIN deposits d ON v.deposit_id = d.id
    ORDER BY v.expiry_time DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Log activity
logAdminActivity('Vouchers Access', 'Viewed vouchers list');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vouchers - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
                <span class="logo-short"></span>
            </div>
            <button class="sidebar-toggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <nav>
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class="bi bi-speedometer2"></i>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="deposits.php">
                        <i class="bi bi-recycle"></i>
                        <span class="menu-text">Bottle Deposits</span>
                    </a>
                </li>
                <li>
                    <a href="bins.php">
                        <i class="bi bi-trash"></i>
                        <span class="menu-text">Trash Bins</span>
                    </a>
                </li>
                <li>
                        <a href="student_sessions.php">
                            <i class="bi bi-phone"></i>
                            <span class="menu-text">Student Sessions</span>
                        </a>
                    </li>
                <li>
                    <a href="sessions.php">
                        <i class="bi bi-wifi"></i>
                        <span class="menu-text">Internet Sessions</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span class="menu-text">Users</span>
                    </a>
                </li>
                <li>
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span class="menu-text">Activity Logs</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="menu-text">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h2>Vouchers</h2>
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
                <h3>All Vouchers</h3>
                <div class="filter-options">
                    <select id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="used">Used</option>
                        <option value="unused">Unused</option>
                        <option value="expired">Expired</option>
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
                                <th>Code</th>
                                <th>Minutes</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Deposit Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td><?php echo $voucher['code']; ?></td>
                                <td><?php echo $voucher['internet_minutes']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($voucher['expiry_time'])); ?></td>
                                <td>
                                    <?php if ($voucher['is_used']): ?>
                                        <span class="status green">Used</span>
                                    <?php elseif (strtotime($voucher['expiry_time']) < time()): ?>
                                        <span class="status red">Expired</span>
                                    <?php else: ?>
                                        <span class="status orange">Unused</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($voucher['deposit_time'])); ?></td>
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
            window.location.href = `vouchers.php?status=${status}`;
        });
    </script>
</body>
</html>