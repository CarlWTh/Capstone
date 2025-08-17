<?php
require_once 'config.php';
checkAdminAuth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Only handle admin logs now
$table = 'SystemLog';
$join_condition = 'JOIN Admin a ON l.admin_id = a.admin_id';
$select_columns = 'l.*, a.username';
$log_count_query = "SELECT COUNT(*) FROM SystemLog";
$log_id_column = 'log_id';

$total_logs_result = $conn->query($log_count_query);
$total_logs = $total_logs_result ? $total_logs_result->fetch_row()[0] : 0;
$total_pages = ceil($total_logs / $per_page);

$logs_query = "
    SELECT {$select_columns}
    FROM {$table} l
    {$join_condition}
    ORDER BY l.timestamp DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$logs_result = $conn->query($logs_query);
$logs = $logs_result ? $logs_result->fetch_all(MYSQLI_ASSOC) : [];


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="./css/styles.css">
</head>

<body class="dashboard-container">
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
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Sessions</span>
                    </a>
                </li>
                <li class="active">
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>
                <li>
                        <a href="profile.php">
                            <i class="bi bi-person-circle"></i>
                            <span>My Account</span>
                        </a>
                    </li>
                <li>
                    <a href="settings.php">
                        <i class="bi bi-gear"></i> 
                        <span>Settings</span>
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

    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-clock-history"></i> Activity Logs</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-person-gear"></i> System Logs</h3>
                <div class="filter-options">
                    <input type="text" id="searchLogs" placeholder="Search logs..." class="form-control">
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['username']) ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['details']) ?></td>
                                        <td><?= date('M j, Y h:i A', strtotime($log['timestamp'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-info-circle"></i> No logs found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination mt-4">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="bi bi-chevron-left"></i> Prev
                        </button>
                    <?php endif; ?>

                    <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            Next <i class="bi bi-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        window.addEventListener('click', function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                const dropdown = document.querySelector('.dropdown-content');
                if (dropdown.classList.contains('show-dropdown')) {
                    dropdown.classList.remove('show-dropdown');
                }
            }
        });

        document.getElementById('searchLogs').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableBody = document.querySelector('.transaction-logs tbody');

            if (tableBody) { 
                tableBody.querySelectorAll('tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    </script>
</body>

</html>