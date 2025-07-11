<?php
require_once 'config.php';
checkAdminAuth();

// Determine active tab
$active_tab = $_GET['tab'] ?? 'active-sessions';

// Pagination for each tab
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get data based on active tab
switch ($active_tab) {
    case 'active-sessions':
        // Active internet sessions
        $total_records = $conn->query("SELECT COUNT(*) FROM InternetSession WHERE end_time IS NULL")->fetch_row()[0];
        $records = $conn->query("
            SELECT i.*, v.code as voucher_code, s.device_mac_address
            FROM InternetSession i
            LEFT JOIN Voucher v ON i.voucher_id = v.voucher_id
            JOIN StudentSession s ON i.anonymous_token = s.anonymous_token
            WHERE i.end_time IS NULL
            ORDER BY i.start_time DESC
            LIMIT $per_page OFFSET $offset
        ")->fetch_all(MYSQLI_ASSOC);
        break;

    case 'session-logs':
        // All internet sessions
        $total_records = $conn->query("SELECT COUNT(*) FROM InternetSession")->fetch_row()[0];
        $records = $conn->query("
            SELECT i.*, v.code as voucher_code, s.device_mac_address
            FROM InternetSession i
            LEFT JOIN Voucher v ON i.voucher_id = v.voucher_id
            JOIN StudentSession s ON i.anonymous_token = s.anonymous_token
            ORDER BY i.start_time DESC
            LIMIT $per_page OFFSET $offset
        ")->fetch_all(MYSQLI_ASSOC);
        break;

    case 'student-sessions':
        // Student sessions
        $total_records = $conn->query("SELECT COUNT(*) FROM StudentSession")->fetch_row()[0];
        $records = $conn->query("
            SELECT s.*, 
                   COUNT(i.internet_session_id) as internet_session_count,
                   MIN(i.start_time) as first_session_access,
                   MAX(i.end_time) as last_session_access
            FROM StudentSession s
            LEFT JOIN InternetSession i ON s.anonymous_token = i.anonymous_token
            GROUP BY s.session_id
            ORDER BY s.session_id DESC
            LIMIT $per_page OFFSET $offset
        ")->fetch_all(MYSQLI_ASSOC);
        break;

    case 'bandwidth-usage':
        // Bandwidth usage (placeholder)
        $total_records = 0;
        $records = [];
        break;
}

$total_pages = ceil($total_records / $per_page);

logAdminActivity('Network Monitoring', "Viewed $active_tab");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Monitoring - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .monitoring-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
        }

        .monitoring-tab {
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

        .monitoring-tab:hover {
            color: var(--primary-color);
        }

        .monitoring-tab.active {
            color: var(--primary-color);
        }

        .monitoring-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
        }

        .tab-badge {
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
            margin-left: 0.3rem;
        }

        .session-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .status-completed {
            background-color: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .bandwidth-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        .bandwidth-meter {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            margin: 1rem 0;
            overflow: hidden;
        }

        .bandwidth-progress {
            height: 100%;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }

        .usage-stats {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .usage-stat {
            text-align: center;
            padding: 0.5rem;
            flex: 1;
        }

        .usage-stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .usage-stat-label {
            font-size: 0.75rem;
            color: var(--light-text);
        }

        .mac-address {
            font-family: monospace;
            background: rgba(0, 0, 0, 0.05);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
        }

        .token-preview {
            max-width: 120px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
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
            <h2><i class="bi bi-wifi"></i> Network Monitoring</h2>
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
                <div class="monitoring-tabs">
                    <button class="monitoring-tab <?= $active_tab === 'active-sessions' ? 'active' : '' ?>"
                        onclick="window.location='?tab=active-sessions'">
                        <i class="bi bi-activity"></i> Active Sessions
                        <span class="tab-badge"><?= $conn->query("SELECT COUNT(*) FROM InternetSession WHERE end_time IS NULL")->fetch_row()[0] ?></span>
                    </button>
                    <button class="monitoring-tab <?= $active_tab === 'session-logs' ? 'active' : '' ?>"
                        onclick="window.location='?tab=session-logs'">
                        <i class="bi bi-list-check"></i> Session Logs
                        <span class="tab-badge"><?= $conn->query("SELECT COUNT(*) FROM InternetSession")->fetch_row()[0] ?></span>
                    </button>
                    <button class="monitoring-tab <?= $active_tab === 'student-sessions' ? 'active' : '' ?>"
                        onclick="window.location='?tab=student-sessions'">
                        <i class="bi bi-phone"></i> Student Sessions
                        <span class="tab-badge"><?= $conn->query("SELECT COUNT(*) FROM StudentSession")->fetch_row()[0] ?></span>
                    </button>
                    <button class="monitoring-tab <?= $active_tab === 'bandwidth-usage' ? 'active' : '' ?>"
                        onclick="window.location='?tab=bandwidth-usage'">
                        <i class="bi bi-speedometer2"></i> Bandwidth Usage
                    </button>
                </div>

                <div class="filter-options">
                    <input type="text" id="searchInput" placeholder="Search..." class="form-control">
                    <button class="btn btn-secondary">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>

            <div class="card-body">
                <?php if ($active_tab === 'bandwidth-usage'): ?>
                    <!-- Bandwidth Usage Tab -->
                    <div class="bandwidth-card">
                        <h4><i class="bi bi-pie-chart"></i> Current Bandwidth Usage</h4>
                        <div class="bandwidth-meter">
                            <div class="bandwidth-progress" style="width: 65%"></div>
                        </div>
                        <div class="usage-stats">
                            <div class="usage-stat">
                                <div class="usage-stat-value">65%</div>
                                <div class="usage-stat-label">Total Usage</div>
                            </div>
                            <div class="usage-stat">
                                <div class="usage-stat-value">42 Mbps</div>
                                <div class="usage-stat-label">Download</div>
                            </div>
                            <div class="usage-stat">
                                <div class="usage-stat-value">18 Mbps</div>
                                <div class="usage-stat-label">Upload</div>
                            </div>
                            <div class="usage-stat">
                                <div class="usage-stat-value">24</div>
                                <div class="usage-stat-label">Active Devices</div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="transaction-logs">
                            <thead>
                                <tr>
                                    <th>Device</th>
                                    <th>MAC Address</th>
                                    <th>Download</th>
                                    <th>Upload</th>
                                    <th>Total</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample data - would be dynamic in real implementation -->
                                <tr>
                                    <td>Student Device 1</td>
                                    <td><span class="mac-address">a4:5e:60:c7:32:11</span></td>
                                    <td>5.2 Mbps</td>
                                    <td>1.8 Mbps</td>
                                    <td>7.0 Mbps</td>
                                    <td>1h 24m</td>
                                </tr>
                                <tr>
                                    <td>Student Device 2</td>
                                    <td><span class="mac-address">b8:27:eb:12:34:56</span></td>
                                    <td>3.7 Mbps</td>
                                    <td>0.9 Mbps</td>
                                    <td>4.6 Mbps</td>
                                    <td>42m</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                <?php else: ?>
                    <!-- Active Sessions / Session Logs / Student Sessions Tabs -->
                    <div class="table-responsive">
                        <table class="transaction-logs">
                            <thead>
                                <tr>
                                    <?php if ($active_tab === 'student-sessions'): ?>
                                        <th>Session ID</th>
                                        <th>Anonymous Token</th>
                                        <th>Device MAC</th>
                                        <th>Internet Sessions</th>
                                        <th>First Access</th>
                                        <th>Last Access</th>
                                    <?php else: ?>
                                        <th>Session ID</th>
                                        <th>Device MAC</th>
                                        <th>Voucher</th>
                                        <th>Start Time</th>
                                        <?php if ($active_tab === 'session-logs'): ?>
                                            <th>End Time</th>
                                        <?php endif; ?>
                                        <th>Status</th>
                                        <th>Duration</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($records)): ?>
                                    <tr>
                                        <td colspan="<?= $active_tab === 'student-sessions' ? 6 : ($active_tab === 'session-logs' ? 7 : 6) ?>" class="text-center py-4 text-muted">
                                            <i class="bi bi-info-circle"></i> No records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($records as $record): ?>
                                        <tr>
                                            <?php if ($active_tab === 'student-sessions'): ?>
                                                <td><?= $record['session_id'] ?></td>
                                                <td>
                                                    <span class="token-preview" title="<?= htmlspecialchars($record['anonymous_token']) ?>">
                                                        <?= substr($record['anonymous_token'], 0, 8) ?>...
                                                    </span>
                                                </td>
                                                <td><span class="mac-address"><?= $record['device_mac_address'] ?: 'N/A' ?></span></td>
                                                <td><?= $record['internet_session_count'] ?></td>
                                                <td><?= $record['first_session_access'] ? date('M j, H:i', strtotime($record['first_session_access'])) : 'N/A' ?></td>
                                                <td><?= $record['last_session_access'] ? date('M j, H:i', strtotime($record['last_session_access'])) : 'N/A' ?></td>
                                            <?php else: ?>
                                                <td><?= $record['internet_session_id'] ?></td>
                                                <td><span class="mac-address"><?= $record['device_mac_address'] ?></span></td>
                                                <td><?= $record['voucher_code'] ?? 'N/A' ?></td>
                                                <td><?= date('M j, H:i', strtotime($record['start_time'])) ?></td>
                                                <?php if ($active_tab === 'session-logs'): ?>
                                                    <td><?= $record['end_time'] ? date('M j, H:i', strtotime($record['end_time'])) : '-' ?></td>
                                                <?php endif; ?>
                                                <td>
                                                    <span class="session-status <?= $record['end_time'] ? 'status-completed' : 'status-active' ?>">
                                                        <i class="bi <?= $record['end_time'] ? 'bi-check-circle' : 'bi-activity' ?>"></i>
                                                        <?= $record['end_time'] ? 'Completed' : 'Active' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ($record['end_time']) {
                                                        $duration = strtotime($record['end_time']) - strtotime($record['start_time']);
                                                        echo gmdate("H\h i\m", $duration);
                                                    } else {
                                                        $duration = time() - strtotime($record['start_time']);
                                                        echo gmdate("H\h i\m", $duration) . ' (ongoing)';
                                                    }
                                                    ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination mt-4">
                        <?php if ($page > 1): ?>
                            <a href="?tab=<?= $active_tab ?>&page=<?= $page - 1 ?>" class="btn btn-secondary">
                                <i class="bi bi-chevron-left"></i> Prev
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="bi bi-chevron-left"></i> Prev
                            </button>
                        <?php endif; ?>

                        <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?tab=<?= $active_tab ?>&page=<?= $page + 1 ?>" class="btn btn-secondary">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.querySelector('.transaction-logs tbody');

            if (table) {
                table.querySelectorAll('tr').forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    </script>
</body>

</html>