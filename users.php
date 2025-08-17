<?php
require_once 'config.php';
checkAdminAuth();

$active_tab = $_GET['tab'] ?? 'active-sessions';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;
$total_records = 0;
$records = [];

switch ($active_tab) {
    case 'active-sessions':
        $stmt = $conn->prepare("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL");
        $stmt->execute();
        $stmt->bind_result($total_records);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT us.session_id, us.ip_address, us.start_time, us.end_time, us.duration_minutes,
                   u.mac_address, v.voucher_code
            FROM UserSessions us
            JOIN User u ON us.user_id = u.user_id
            LEFT JOIN Voucher v ON us.voucher_id = v.voucher_id
            WHERE us.end_time IS NULL
            ORDER BY us.start_time DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;

    case 'session-logs':
        $stmt = $conn->prepare("SELECT COUNT(*) FROM UserSessions");
        $stmt->execute();
        $stmt->bind_result($total_records);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT us.session_id, us.ip_address, us.start_time, us.end_time, us.duration_minutes,
                   u.mac_address, v.voucher_code
            FROM UserSessions us
            JOIN User u ON us.user_id = u.user_id
            LEFT JOIN Voucher v ON us.voucher_id = v.voucher_id
            ORDER BY us.start_time DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;

    case 'user-sessions':
        $stmt = $conn->prepare("SELECT COUNT(*) FROM User");
        $stmt->execute();
        $stmt->bind_result($total_records);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT u.user_id, us.ip_address, u.time_credits, u.last_active, u.created_at,
                   COUNT(us.session_id) as internet_session_count,
                   MIN(us.start_time) as first_session_access,
                   MAX(us.end_time) as last_session_access
            FROM User u
            LEFT JOIN UserSessions us ON u.user_id = us.user_id
            GROUP BY u.user_id
            ORDER BY u.user_id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;
}

$total_pages = $per_page > 0 ? ceil($total_records / $per_page) : 1;

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
                <li>
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
            <h2><i class="bi bi-people"></i>Sessions</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <div class="monitoring-tabs">
                    <button class="monitoring-tab <?= $active_tab === 'active-sessions' ? 'active' : '' ?>"
                        onclick="window.location='?tab=active-sessions'">
                        <i class="bi bi-activity"></i> Active Sessions
                        <span class="tab-badge">
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL");
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $stmt->close();
                            echo htmlspecialchars($count);
                            ?>
                        </span>
                    </button>
                    <button class="monitoring-tab <?= $active_tab === 'session-logs' ? 'active' : '' ?>"
                        onclick="window.location='?tab=session-logs'">
                        <i class="bi bi-list-check"></i> Session Logs
                        <span class="tab-badge">
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM UserSessions");
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $stmt->close();
                            echo htmlspecialchars($count);
                            ?>
                        </span>
                    </button>
                    <button class="monitoring-tab <?= $active_tab === 'user-sessions' ? 'active' : '' ?>"
                        onclick="window.location='?tab=user-sessions'">
                        <i class="bi bi-phone"></i> User Sessions
                        <span class="tab-badge">
                            <?php
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM User");
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $stmt->close();
                            echo htmlspecialchars($count);
                            ?>
                        </span>
                    </button>
                </div>

                <div class="filter-options">
                    <input type="text" id="searchInput" placeholder="Search..." class="form-control">
                </div>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <?php if ($active_tab === 'user-sessions'): ?>
                                    <th>User ID</th>
                                    <th>IP Address</th>
                                    <th>Time Credits</th>
                                    <th>Total Sessions</th>
                                    <th>First Access</th>
                                    <th>Last Access</th>
                                <?php else: ?>
                                    <th>Session ID</th>
                                    <th>MAC Address</th>
                                    <th>Voucher Code</th>
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
                                    <td colspan="<?= $active_tab === 'user-sessions' ? 6 : ($active_tab === 'session-logs' ? 7 : 6) ?>" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle"></i> No records found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <?php if ($active_tab === 'user-sessions'): ?>
                                            <td><?= htmlspecialchars($record['user_id']) ?></td>
                                            <td><span class="mac-address"><?= htmlspecialchars($record['ip_address'] ?: 'N/A') ?></span></td>
                                            <td><?= number_format($record['time_credits']) . ' min' ?></td>
                                            <td><?= htmlspecialchars($record['internet_session_count']) ?></td>
                                            <td><?= $record['first_session_access'] ? date('M j, Y h:i A', strtotime($record['first_session_access'])) : 'N/A' ?></td>
                                            <td><?= $record['last_session_access'] ? date('M j, Y h:i A', strtotime($record['last_session_access'])) : 'N/A' ?></td>
                                        <?php else: ?>
                                            <td><?= htmlspecialchars($record['session_id']) ?></td>
                                            <td><span class="mac-address"><?= htmlspecialchars($record['ip_address']) ?></span></td>
                                            <td><?= htmlspecialchars($record['voucher_code'] ?? 'N/A') ?></td>
                                            <td><?= date('M j, Y h:i A', strtotime($record['start_time'])) ?></td>
                                            <?php if ($active_tab === 'session-logs'): ?>
                                                <td><?= $record['end_time'] ? date('M j, Y h:i A', strtotime($record['end_time'])) : '-' ?></td>
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
                        <a href="?tab=<?= htmlspecialchars($active_tab) ?>&page=<?= $page - 1 ?>" class="btn btn-secondary">
                            <i class="bi bi-chevron-left"></i> Prev
                        </a>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled>
                            <i class="bi bi-chevron-left"></i> Prev
                        </button>
                    <?php endif; ?>

                    <span class="pagination-info">Page <?= $page ?> of <?= $total_pages ?></span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?tab=<?= htmlspecialchars($active_tab) ?>&page=<?= $page + 1 ?>" class="btn btn-secondary">
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