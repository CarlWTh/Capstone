<?php
require_once 'config.php';
checkAdminAuth();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Determine which logs to show (default to admin logs)
$log_type = $_GET['log_type'] ?? 'admin';
$table = $log_type === 'sms' ? 'SMSLogs' : 'AdminActivityLog';
$join_condition = $log_type === 'sms' ? '' : 'JOIN users u ON l.admin_id = u.id';

// Get logs
$total_logs = $conn->query("SELECT COUNT(*) FROM $table")->fetch_row()[0];
$total_pages = ceil($total_logs / $per_page);

$logs = $conn->query("
    SELECT l.*" . ($log_type === 'admin' ? ', u.username' : '') . "
    FROM $table l
    $join_condition
    ORDER BY l.timestamp DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

logAdminActivity('Activity Logs', 'Viewed ' . ($log_type === 'sms' ? 'SMS' : 'activity') . ' logs');
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

        .status-success {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-failed {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }

        .status-pending {
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
    <!-- Sidebar remains exactly the same... -->
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
                        <span>Users</span>
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
    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-clock-history"></i> System Logs</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="avatar-img">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
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
                <div class="log-tabs">
                    <button class="log-tab active" onclick="switchLogTab('admin')">
                        <i class="bi bi-person-gear"></i> Admin Logs
                        <span class="badge-count"><?= number_format($total_logs) ?></span>
                    </button>
                    <button class="log-tab" onclick="switchLogTab('sms')">
                        <i class="bi bi-chat-dots"></i> SMS Logs
                        <span class="badge-count">24</span>
                    </button>
                </div>

                <div class="filter-options">
                    <input type="text" id="searchLogs" placeholder="Search logs..." class="form-control">
                    <button class="btn btn-secondary">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="card-body">
                <!-- Admin Logs Content -->
                <div id="adminLogs" class="log-content active">
                    <div class="table-responsive">
                        <table class="transaction-logs">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $log['log_id'] ?></td>
                                        <td><?= htmlspecialchars($log['username']) ?></td>
                                        <td><?= htmlspecialchars($log['action']) ?></td>
                                        <td><?= htmlspecialchars($log['details']) ?></td>
                                        <td><?= date('M j, Y H:i', strtotime($log['timestamp'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
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

                <!-- SMS Logs Content (UI Only) -->
                <div id="smsLogs" class="log-content">
                    <div class="table-responsive">
                        <table class="transaction-logs">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Recipients</th>
                                    <th>Message</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample SMS Log Data - Static UI Only -->
                                <tr>
                                    <td>1001</td>
                                    <td class="recipient-cell">09123456789, 09987654321</td>
                                    <td class="message-preview">Bin #1 is 85% full. Please empty soon.</td>
                                    <td><span class="sms-status status-success"><i class="bi bi-check-circle"></i> Delivered</span></td>
                                    <td><?= date('M j, Y H:i', strtotime('-1 hour')) ?></td>
                                </tr>
                                <tr>
                                    <td>1002</td>
                                    <td class="recipient-cell">09111222333</td>
                                    <td class="message-preview">Bin #2 is 92% full. Urgent emptying needed.</td>
                                    <td><span class="sms-status status-failed"><i class="bi bi-exclamation-circle"></i> Failed</span></td>
                                    <td><?= date('M j, Y H:i', strtotime('-3 hours')) ?></td>
                                </tr>
                                <tr>
                                    <td>1003</td>
                                    <td class="recipient-cell">09123456789, 09987654321, 09111222333</td>
                                    <td class="message-preview">System maintenance scheduled for tomorrow at 2AM</td>
                                    <td><span class="sms-status status-success"><i class="bi bi-check-circle"></i> Delivered</span></td>
                                    <td><?= date('M j, Y H:i', strtotime('-5 hours')) ?></td>
                                </tr>
                                <tr>
                                    <td>1004</td>
                                    <td class="recipient-cell">09988776655</td>
                                    <td class="message-preview">Your voucher code: XKJ8H2S9P0</td>
                                    <td><span class="sms-status status-pending"><i class="bi bi-clock"></i> Pending</span></td>
                                    <td><?= date('M j, Y H:i', strtotime('-1 day')) ?></td>
                                </tr>
                                <tr>
                                    <td>1005</td>
                                    <td class="recipient-cell">09123456789</td>
                                    <td class="message-preview">Internet session will expire in 15 minutes</td>
                                    <td><span class="sms-status status-success"><i class="bi bi-check-circle"></i> Delivered</span></td>
                                    <td><?= date('M j, Y H:i', strtotime('-2 days')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination mt-4">
                        <button class="btn btn-secondary" disabled>
                            <i class="bi bi-chevron-left"></i> Prev
                        </button>
                        <span class="pagination-info">Page 1 of 5</span>
                        <a href="#" class="btn btn-secondary">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

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

        // Search functionality
        document.getElementById('searchLogs').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const activeTable = document.querySelector('.log-content.active table tbody');

            activeTable.querySelectorAll('tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Switch between log tabs
        function switchLogTab(tab) {
            // Update active tab
            document.querySelectorAll('.log-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.log-tab[onclick="switchLogTab('${tab}')"]`).classList.add('active');

            // Update active content
            document.querySelectorAll('.log-content').forEach(c => c.classList.remove('active'));
            document.getElementById(`${tab}Logs`).classList.add('active');

            // Reset search
            document.getElementById('searchLogs').value = '';
            document.querySelectorAll('.transaction-logs tbody tr').forEach(row => {
                row.style.display = '';
            });
        }
    </script>
</body>

</html>