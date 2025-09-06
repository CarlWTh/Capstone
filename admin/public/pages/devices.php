<?php
require_once '../config.php';
checkAdminAuth();

require_once '../helpers/devices_backend.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Monitoring - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/session.css">
    
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
                <li class="active">
                    <a href="devices.php">
                        <i class="bi bi-phone"></i>
                            <span>Devices</span>
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
                        <i class="bi bi-phone"></i> Device Sessions
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