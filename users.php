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
        /* Modern Statistics Cards for Sessions */
        .session-stats-card {
            border: none;
            border-radius: 16px;
            margin-bottom: 24px;
            background: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .session-stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
        }

        .session-stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-gradient);
        }

        .session-stats-card-active {
            --accent-gradient: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
        }

        .session-stats-card-logs {
            --accent-gradient: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .session-stats-card-users {
            --accent-gradient: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }

        .monitoring-tabs {
            display: flex;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 1.5rem;
            background: white;
            border-radius: 16px 16px 0 0;
            padding: 0 8px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
        }

        .monitoring-tab {
            padding: 16px 24px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 12px 12px 0 0;
            margin: 8px 4px 0;
        }

        .monitoring-tab:hover {
            color: #495057;
            background: rgba(0, 0, 0, 0.03);
            transform: translateY(-2px);
        }

        .monitoring-tab.active {
            color: #495057;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
        }

        .monitoring-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border-radius: 2px;
        }

        .tab-badge {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            min-width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
        }

        .session-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .status-completed {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(108, 117, 125, 0.3);
        }

        .mac-address {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            color: #495057;
            border: 1px solid #dee2e6;
        }

        /* Modern Card */
        .modern-card {
            border: none;
            border-radius: 16px;
            background: white;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            position: relative;
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
            position: relative;
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

        /* Modern Table */
        .modern-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            background: white;
            width: 100%;
            margin: 0;
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
            transition: all 0.2s ease;
        }

        .modern-table tbody tr:hover {
            background: #f8f9fa;
        }

        .modern-table tbody td {
            padding: 16px;
            border: none;
            vertical-align: middle;
        }

        .modern-table tbody tr:last-child {
            border-bottom: none;
        }

        /* Modern Search Input */
        .modern-search-input {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s ease;
            background: white;
            font-size: 14px;
            width: 100%;
            max-width: 300px;
        }

        .modern-search-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
            outline: none;
        }

        .modern-search-input::placeholder {
            color: #6c757d;
        }

        /* Filter Options */
        .filter-options {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Modern Pagination */
        .modern-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 32px;
            padding: 24px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .modern-pagination-btn {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .modern-pagination-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }

        .modern-pagination-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .pagination-info {
            background: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        /* No Records State */
        .no-records {
            text-align: center;
            padding: 64px 24px;
            color: #6c757d;
        }

        .no-records i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .no-records h4 {
            margin-bottom: 8px;
            color: #495057;
        }

        /* Duration Badge */
        .duration-badge {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* User ID Badge */
        .user-id-badge {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Credits Badge */
        .credits-badge {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-weight: 600;
        }

        /* Main Header Enhancement */
        .main-header h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            color: #495057;
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
            <h2><i class="bi bi-wifi"></i>Network Sessions</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="modern-card">
            <div class="modern-card-header">
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
                    <input type="text" id="searchInput" placeholder="🔍 Search sessions..." class="modern-search-input">
                </div>
            </div>

            <div class="modern-card-body">
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <?php if ($active_tab === 'user-sessions'): ?>
                                    <th><i class="bi bi-router me-2"></i>IP Address</th>
                                    <th><i class="bi bi-clock me-2"></i>Time Credits</th>
                                    <th><i class="bi bi-graph-up me-2"></i>Total Sessions</th>
                                    <th><i class="bi bi-calendar-plus me-2"></i>First Access</th>
                                    <th><i class="bi bi-calendar-check me-2"></i>Last Access</th>
                                <?php else: ?>
                                    <th><i class="bi bi-ethernet me-2"></i>MAC Address</th>
                                    <th><i class="bi bi-ticket me-2"></i>Voucher Code</th>
                                    <th><i class="bi bi-play-circle me-2"></i>Start Time</th>
                                    <?php if ($active_tab === 'session-logs'): ?>
                                        <th><i class="bi bi-stop-circle me-2"></i>End Time</th>
                                    <?php endif; ?>
                                    <th><i class="bi bi-check-circle me-2"></i>Status</th>
                                    <th><i class="bi bi-stopwatch me-2"></i>Duration</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($records)): ?>
                                <tr>
                                    <td colspan="<?= $active_tab === 'user-sessions' ? 5 : ($active_tab === 'session-logs' ? 6 : 5) ?>" class="no-records">
                                        <i class="bi bi-inbox"></i>
                                        <h4>No Records Found</h4>
                                        <p>There are currently no session records to display.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($records as $record): ?>
                                    <tr>
                                        <?php if ($active_tab === 'user-sessions'): ?>
                                            <td><span class="mac-address"><?= htmlspecialchars($record['ip_address'] ?: 'N/A') ?></span></td>
                                            <td><span class="credits-badge"><?= number_format($record['time_credits']) ?> min</span></td>
                                            <td><strong><?= htmlspecialchars($record['internet_session_count']) ?></strong></td>
                                            <td><?= $record['first_session_access'] ? date('M j, Y h:i A', strtotime($record['first_session_access'])) : '<span class="text-muted">N/A</span>' ?></td>
                                            <td><?= $record['last_session_access'] ? date('M j, Y h:i A', strtotime($record['last_session_access'])) : '<span class="text-muted">N/A</span>' ?></td>
                                        <?php else: ?>
                                            <td><span class="mac-address"><?= htmlspecialchars($record['ip_address']) ?></span></td>
                                            <td><?= $record['voucher_code'] ? '<span class="user-id-badge">' . htmlspecialchars($record['voucher_code']) . '</span>' : '<span class="text-muted">N/A</span>' ?></td>
                                            <td><?= date('M j, Y h:i A', strtotime($record['start_time'])) ?></td>
                                            <?php if ($active_tab === 'session-logs'): ?>
                                                <td><?= $record['end_time'] ? date('M j, Y h:i A', strtotime($record['end_time'])) : '<span class="text-muted">-</span>' ?></td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="session-status <?= $record['end_time'] ? 'status-completed' : 'status-active' ?>">
                                                    <i class="bi <?= $record['end_time'] ? 'bi-check-circle' : 'bi-activity' ?>"></i>
                                                    <?= $record['end_time'] ? 'Completed' : 'Active' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="duration-badge">
                                                    <?php
                                                    if ($record['end_time']) {
                                                        $duration = strtotime($record['end_time']) - strtotime($record['start_time']);
                                                        echo gmdate("H\h i\m", $duration);
                                                    } else {
                                                        $duration = time() - strtotime($record['start_time']);
                                                        echo gmdate("H\h i\m", $duration) . ' (ongoing)';
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="modern-pagination">
                    <?php if ($page > 1): ?>
                        <a href="?tab=<?= htmlspecialchars($active_tab) ?>&page=<?= $page - 1 ?>" class="modern-pagination-btn">
                            <i class="bi bi-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <button class="modern-pagination-btn" disabled>
                            <i class="bi bi-chevron-left"></i> Previous
                        </button>
                    <?php endif; ?>

                    <span class="pagination-info">
                        <i class="bi bi-file-text me-2"></i>
                        Page <?= $page ?> of <?= $total_pages ?> (<?= number_format($total_records) ?> total)
                    </span>

                    <?php if ($page < $total_pages): ?>
                        <a href="?tab=<?= htmlspecialchars($active_tab) ?>&page=<?= $page + 1 ?>" class="modern-pagination-btn">
                            Next <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="modern-pagination-btn" disabled>
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
            const table = document.querySelector('.modern-table tbody');

            if (table) {
                const rows = table.querySelectorAll('tr');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const isVisible = text.includes(searchTerm);
                    row.style.display = isVisible ? '' : 'none';
                    if (isVisible && !row.classList.contains('no-records')) visibleCount++;
                });

                // Update search feedback
                if (visibleCount === 0 && searchTerm.length > 0) {
                    // Could add a "no results" message here
                }
            }
        });

        // Add smooth loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.modern-card, .session-stats-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>