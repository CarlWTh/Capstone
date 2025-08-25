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
    <style>
        /* Modern Statistics Cards for Activity Logs */
        .activity-stats-card {
            border: none;
            border-radius: 16px;
            margin-bottom: 24px;
            background: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
        }

        .activity-stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-gradient);
        }

        .activity-stats-card-logs {
            --accent-gradient: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        /* Modern Card */
        .modern-card {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
            margin-bottom: 24px;
        }

        .modern-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .modern-card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 1px solid #e9ecef;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modern-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .modern-card-title {
            font-size: 20px;
            font-weight: 600;
            color: #495057;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modern-card-body {
            padding: 32px;
        }

        /* Modern Search Input */
        .modern-search-input {
            border: 2px solid #e9ecef;
            background: white;
            color: #495057;
            border-radius: 12px;
            padding: 12px 16px 12px 44px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 250px;
        }

        .search-wrapper {
            display: inline-block;
        }

        .search-wrapper::before {
            content: '\F52A';
            font-family: 'Bootstrap Icons';
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 2;
        }

        .modern-search-input:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
            outline: none;
        }

        /* Modern Table */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            background: white;
            margin: 0;
            width: 100%;
        }

        .modern-table thead th {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            color: white;
            font-weight: 600;
            padding: 16px;
            border: none;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table tbody tr {
            border-bottom: 1px solid #f1f3f4;
        }

        .modern-table tbody td {
            padding: 16px;
            border: none;
            vertical-align: middle;
            
        }

        /* Admin Badge */
        .admin-badge {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Action Type Styling */
        .action-type {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.85em;
            background: #f8f9fa;
            color: #495057;
            border-left: 3px solid #e74c3c;
        }

        /* Details Text */
        .details-text {
            font-size: 0.9em;
            color: #6c757d;
            line-height: 1.4;
            max-width: 300px;
            text-align: center;
        }

        /* Timestamp Styling */
        .timestamp {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-size: 0.85em;
            color: #495057;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 6px;
        }

        /* Modern Pagination */
        .modern-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-top: 32px;
            padding: 24px 0;
        }

        .modern-pagination .btn {
            border: 2px solid #e9ecef;
            background: white;
            color: #495057;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modern-pagination .btn:hover {
            border-color: #e74c3c;
            color: #e74c3c;
        }

        .modern-pagination .btn.disabled,
        .modern-pagination .btn:disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .pagination-info {
            font-size: 0.9em;
            color: #6c757d;
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
        }

        .empty-state p {
            margin: 0;
            font-size: 0.9em;
        }

        /* Filter Section Styles */
        .filter-options {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-options h5 {
            margin: 0;
            font-weight: 600;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .modern-card-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .modern-pagination {
                flex-direction: column;
                gap: 12px;
            }

            .modern-card-body {
                padding: 24px 20px;
            }

            .modern-card-header {
                padding: 20px;
            }

            .modern-search-input {
                min-width: 200px;
                width: 100%;
            }

            .details-text {
                max-width: 200px;
            }
        }

        /* Activity Type Colors */
        .action-login { border-left-color: #28a745; }
        .action-logout { border-left-color: #ffc107; }
        .action-create { border-left-color: #007bff; }
        .action-update { border-left-color: #17a2b8; }
        .action-delete { border-left-color: #dc3545; }
        .action-settings { border-left-color: #6f42c1; }
    </style>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar toggle functionality
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
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