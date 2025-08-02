<?php
require_once 'config.php';
<<<<<<< HEAD
checkAdminAuth();

=======
checkAdminAuth(); // This function is defined in config.php

// Pagination
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

<<<<<<< HEAD
$log_type = $_GET['log_type'] ?? 'admin';

=======
// Determine which logs to show (default to admin logs)
$log_type = $_GET['log_type'] ?? 'admin';

// Initialize variables for SQL query
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
$table = '';
$join_condition = '';
$select_columns = 'l.*';
$log_count_query = '';

<<<<<<< HEAD
if ($log_type === 'sms') {
    $table = 'SmsLog';
    $log_count_query = "SELECT COUNT(*) FROM SmsLog";
    $log_id_column = 'sms_log_id'; 
} else { 
    $table = 'SystemLog'; 
    $join_condition = 'JOIN Admin a ON l.admin_id = a.admin_id'; 
    $select_columns .= ', a.username'; 
    $log_count_query = "SELECT COUNT(*) FROM SystemLog";
    $log_id_column = 'log_id'; 
}

=======
// Adjust table and join based on log_type
if ($log_type === 'sms') {
    $table = 'SmsLog';
    $log_count_query = "SELECT COUNT(*) FROM SmsLog";
    $log_id_column = 'sms_log_id'; // For SMS logs
} else { // Default to 'admin'
    $table = 'SystemLog'; // Renamed from AdminActivityLog
    $join_condition = 'JOIN Admin a ON l.admin_id = a.admin_id'; // Join with Admin table
    $select_columns .= ', a.username'; // Select username from Admin table
    $log_count_query = "SELECT COUNT(*) FROM SystemLog";
    $log_id_column = 'log_id'; // For Admin logs
}

// Get total logs for pagination for the CURRENT active tab
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
$total_logs_result = $conn->query($log_count_query);
$total_logs = $total_logs_result ? $total_logs_result->fetch_row()[0] : 0;
$total_pages = ceil($total_logs / $per_page);

<<<<<<< HEAD
=======
// Get logs for the current page
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
$logs_query = "
    SELECT {$select_columns}
    FROM {$table} l
    {$join_condition}
    ORDER BY l.timestamp DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$logs_result = $conn->query($logs_query);
$logs = $logs_result ? $logs_result->fetch_all(MYSQLI_ASSOC) : [];

<<<<<<< HEAD
$total_sms_logs_result = $conn->query("SELECT COUNT(*) FROM SmsLog");
$total_sms_logs = $total_sms_logs_result ? $total_sms_logs_result->fetch_row()[0] : 0;

$total_admin_logs_result = $conn->query("SELECT COUNT(*) FROM SystemLog");
$total_admin_logs = $total_admin_logs_result ? $total_admin_logs_result->fetch_row()[0] : 0;

=======
// Get total SMS logs for the SMS badge count (regardless of active tab)
$total_sms_logs_result = $conn->query("SELECT COUNT(*) FROM SmsLog");
$total_sms_logs = $total_sms_logs_result ? $total_sms_logs_result->fetch_row()[0] : 0;

// Get total Admin logs for the Admin badge count (regardless of active tab)
$total_admin_logs_result = $conn->query("SELECT COUNT(*) FROM SystemLog");
$total_admin_logs = $total_admin_logs_result ? $total_admin_logs_result->fetch_row()[0] : 0;


// Log activity ONLY when the page is loaded, not when JS changes tabs.
// The `logAdminActivity` function should be robust enough to prevent
// duplicate entries for the same page load.
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
logAdminActivity('Activity Logs', 'Viewed ' . ($log_type === 'sms' ? 'SMS' : 'admin') . ' logs');
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
        .log-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }

        .log-tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 500;
            color: var(--light-text);
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .log-tab:hover {
            color: var(--primary-color);
        }

        .log-tab.active {
            color: var(--primary-color);
        }

        .log-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
        }

        .log-content {
            display: none;
        }

        .log-content.active {
            display: block;
        }

        .sms-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-sent {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-failed {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .status-queued {
            background-color: rgba(241, 196, 15, 0.1);
            color: #f39c12;
        }

        .recipient-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge-count {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            margin-left: 0.3rem;
        }
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
<<<<<<< HEAD
                        <span>Admins</span>
=======
                        <span>Users</span>
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
                    </a>
                </li>
                <li class="active">
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

    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-clock-history"></i> Activity Logs</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
<<<<<<< HEAD
                    
=======
                    <img src="./img/avatar.jpg" alt="Profile" class="avatar-img">
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
<<<<<<< HEAD
                    
=======
                    <a href="profile.php"><i class="bi bi-person"></i> Profile</a>
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <div class="log-tabs">
                    <button class="log-tab <?= ($log_type === 'admin') ? 'active' : '' ?>" onclick="navigateToLogTab('admin')">
                        <i class="bi bi-person-gear"></i> System Logs
                        <span class="badge-count"><?= number_format($total_admin_logs) ?></span>
                    </button>
                    <button class="log-tab <?= ($log_type === 'sms') ? 'active' : '' ?>" onclick="navigateToLogTab('sms')">
                        <i class="bi bi-chat-dots"></i> SMS Logs
                        <span class="badge-count"><?= number_format($total_sms_logs) ?></span>
                    </button>
                </div>

                <div class="filter-options">
                    <input type="text" id="searchLogs" placeholder="Search logs..." class="form-control">
                </div>
            </div>

            <div class="card-body">
                <div id="adminLogs" class="log-content <?= ($log_type === 'admin') ? 'active' : '' ?>">
                    <div class="table-responsive">
                        <table class="transaction-logs">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($log_type === 'admin' && !empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= $log['log_id'] ?></td>
                                            <td><?= htmlspecialchars($log['action']) ?></td>
                                            <td><?= htmlspecialchars($log['details']) ?></td>
                                            <td><?= date('M j, Y h:i A', strtotime($log['timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-info-circle"></i> No Admin logs found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination mt-4">
                        <?php if ($page > 1): ?>
                            <a href="?log_type=admin&page=<?= $page - 1 ?>" class="btn btn-secondary">
                                <i class="bi bi-chevron-left"></i> Prev
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="bi bi-chevron-left"></i> Prev
                            </button>
                        <?php endif; ?>

                        <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?log_type=admin&page=<?= $page + 1 ?>" class="btn btn-secondary">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="smsLogs" class="log-content <?= ($log_type === 'sms') ? 'active' : '' ?>">
                    <div class="table-responsive">
                        <table class="transaction-logs">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Recipient Number</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($log_type === 'sms' && !empty($logs)): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= $log['sms_log_id'] ?></td>
                                            <td class="recipient-cell"><?= htmlspecialchars($log['recipient_number']) ?></td>
                                            <td class="message-preview"><?= htmlspecialchars($log['message']) ?></td>
                                            <td>
                                                <span class="sms-status status-<?= htmlspecialchars($log['status']) ?>">
                                                    <?php
                                                        if ($log['status'] === 'sent') {
                                                            echo '<i class="bi bi-check-circle"></i> Delivered';
                                                        } elseif ($log['status'] === 'failed') {
                                                            echo '<i class="bi bi-exclamation-circle"></i> Failed';
                                                        } elseif ($log['status'] === 'queued') {
                                                            echo '<i class="bi bi-clock"></i> Queued';
                                                        }
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y h:i A', strtotime($log['timestamp'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-info-circle"></i> No SMS logs found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination mt-4">
                        <?php if ($page > 1): ?>
                            <a href="?log_type=sms&page=<?= $page - 1 ?>" class="btn btn-secondary">
                                <i class="bi bi-chevron-left"></i> Prev
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="bi bi-chevron-left"></i> Prev
                            </button>
                        <?php endif; ?>

                        <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?log_type=sms&page=<?= $page + 1 ?>" class="btn btn-secondary">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
<<<<<<< HEAD
=======
        // Toggle sidebar
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

<<<<<<< HEAD
=======
        // Profile dropdown
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

<<<<<<< HEAD
=======
        // Close dropdown when clicking outside
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
        window.addEventListener('click', function(event) {
            if (!event.target.closest('.profile-dropdown')) {
                const dropdown = document.querySelector('.dropdown-content');
                if (dropdown.classList.contains('show-dropdown')) {
                    dropdown.classList.remove('show-dropdown');
                }
            }
        });

<<<<<<< HEAD
=======
        // Search functionality
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
        document.getElementById('searchLogs').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const activeTable = document.querySelector('.log-content.active table tbody');

<<<<<<< HEAD
            if (activeTable) { 
=======
            if (activeTable) { // Check if activeTable exists
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
                activeTable.querySelectorAll('tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });

<<<<<<< HEAD
=======
        // Function to navigate to a log tab (triggers a full page reload to fetch new data)
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
        function navigateToLogTab(tabType) {
            window.location.href = `?log_type=${tabType}`;
        }

<<<<<<< HEAD
=======
        // Set active tab on page load based on URL parameter
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const initialLogType = urlParams.get('log_type') || 'admin';

<<<<<<< HEAD
=======
            // Visually set the active tab (no reload here)
>>>>>>> a3d9f77d153268535a66a38a42913a3249f7211a
            document.querySelectorAll('.log-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.log-tab[onclick="navigateToLogTab('${initialLogType}')"]`).classList.add('active');

            document.querySelectorAll('.log-content').forEach(c => c.classList.remove('active'));
            document.getElementById(`${initialLogType}Logs`).classList.add('active');
        });
    </script>
</body>

</html>