<?php
require_once '../config.php';
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/activity_logs.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/sidebar.js"></script>
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
                        <i class="bi bi-phone"></i>
                            <span>Devices</span>
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

        <!-- Activity Logs Table Card -->
        <div class="modern-card activity-stats-card-logs">
            <div class="modern-card-header">
                <h3 class="modern-card-title">
                    <i class="bi bi-person-gear"></i>
                    System Activity Logs
                </h3>
                <div class="filter-options">
                    <div class="search-wrapper">
                        <input type="text" id="searchLogs" placeholder="🔍 Search logs..." class="modern-search-input">
                    </div>
                </div>
            </div>
            <div class="modern-card-body">
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person-check"></i> Admin</th>
                                <th><i class="bi bi-lightning"></i> Action</th>
                                <th><i class="bi bi-info-circle"></i> Details</th>
                                <th><i class="bi bi-clock"></i> Timestamp</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="log-row">
                                        <td>
                                            <span class="admin-badge">
                                                <i class="bi bi-person-gear"></i>
                                                <?= htmlspecialchars($log['username']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $actionClass = '';
                                            $actionLower = strtolower($log['action']);
                                            if (strpos($actionLower, 'login') !== false) {
                                                $actionClass = 'action-login';
                                            } elseif (strpos($actionLower, 'logout') !== false) {
                                                $actionClass = 'action-logout';
                                            } elseif (strpos($actionLower, 'create') !== false) {
                                                $actionClass = 'action-create';
                                            } elseif (strpos($actionLower, 'update') !== false) {
                                                $actionClass = 'action-update';
                                            } elseif (strpos($actionLower, 'delete') !== false) {
                                                $actionClass = 'action-delete';
                                            } elseif (strpos($actionLower, 'settings') !== false) {
                                                $actionClass = 'action-settings';
                                            }
                                            ?>
                                            <span class="action-type <?= $actionClass ?>">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="details-text">
                                                <?= htmlspecialchars($log['details']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="timestamp">
                                                <?= date('M j, Y h:i A', strtotime($log['timestamp'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox"></i>
                                            <h4>No Activity Logs Found</h4>
                                            <p>No system activity logs are currently available.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modern Pagination -->
                <div class="modern-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn">
                            <i class="bi bi-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <button class="btn disabled">
                            <i class="bi bi-chevron-left"></i> Previous
                        </button>
                    <?php endif; ?>

                    <div class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></div>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn disabled">
                            Next <i class="bi bi-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality without highlighting
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchLogs');
            const tableBody = document.getElementById('logsTableBody');
            
            if (searchInput && tableBody) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const allRows = tableBody.querySelectorAll('tr.log-row');
                    let visibleCount = 0;
                    
                    // Remove existing "no results" message
                    const existingMessage = tableBody.querySelector('.no-results-row');
                    if (existingMessage) {
                        existingMessage.remove();
                    }
                    
                    // Filter rows
                    allRows.forEach(function(row) {
                        const rowText = row.textContent.toLowerCase();
                        const shouldShow = searchTerm === '' || rowText.includes(searchTerm);
                        
                        if (shouldShow) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });
                    
                    // Show "no results" message if needed
                    if (visibleCount === 0 && searchTerm.length > 0) {
                        const noResultsRow = document.createElement('tr');
                        noResultsRow.className = 'no-results-row';
                        noResultsRow.innerHTML = `
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="bi bi-search"></i>
                                    <h4>No Results Found</h4>
                                    <p>No logs match your search term: <strong>"${searchTerm}"</strong></p>
                                </div>
                            </td>
                        `;
                        tableBody.appendChild(noResultsRow);
                    }
                });
            }
        });
    </script>
</body>

</html>