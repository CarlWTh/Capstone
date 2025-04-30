<?php
require_once 'config.php';
checkAdminAuth();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get sessions
$total_sessions = $conn->query("SELECT COUNT(*) FROM InternetSession")->fetch_row()[0];
$total_pages = ceil($total_sessions / $per_page);

$sessions = $conn->query("
    SELECT i.*, v.code as voucher_code, s.device_mac_address
    FROM InternetSession i
    LEFT JOIN Voucher v ON i.voucher_id = v.voucher_id
    JOIN StudentSession s ON i.anonymous_token = s.anonymous_token
    ORDER BY i.start_time DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

logAdminActivity('Sessions Access', 'Viewed internet sessions');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internet Sessions - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h1><?= SITE_NAME ?></h1>
                <span class="logo-short"></span>
            </div>
            <button class="sidebar-toggle">
                <i class="bi bi-list"></i>
            </button>
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
                <li><a href="deposits.php"><i class="bi bi-recycle"></i><span>Deposits</span></a></li>
                <li><a href="vouchers.php"><i class="bi bi-ticket-perforated"></i><span>Vouchers</span></a></li>
                <li><a href="bins.php"><i class="bi bi-trash"></i><span>Trash Bins</span></a></li>
                <li>
                    <a href="student_sessions.php">
                        <i class="bi bi-phone"></i>
                        <span class="menu-text">Student Sessions</span>
                    </a>
                </li>
                <li class="active"><a href="sessions.php"><i class="bi bi-wifi"></i><span>Internet Sessions</span></a></li>
                <li><a href="bottles.php"><i class="bi bi-cup-straw"></i><span>Bottles</span></a></li>
                <li><a href="users.php"><i class="bi bi-people"></i><span>Users</span></a></li>
                <li><a href="activity_logs.php"><i class="bi bi-clock-history"></i><span>Activity Logs</span></a></li>
                <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-wifi"></i> Internet Sessions</h2>
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
                <h3>Active Sessions</h3>
                <div class="filter-options">
                    <select id="sessionFilter">
                        <option value="all">All Sessions</option>
                        <option value="active">Active Only</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            
            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Device MAC</th>
                                <th>Voucher</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sessions as $session): ?>
                            <tr>
                                <td><?= $session['internet_session_id'] ?></td>
                                <td><?= $session['device_mac_address'] ?></td>
                                <td><?= $session['voucher_code'] ?? 'N/A' ?></td>
                                <td><?= date('M j, H:i', strtotime($session['start_time'])) ?></td>
                                <td><?= $session['end_time'] ? date('M j, H:i', strtotime($session['end_time'])) : 'Active' ?></td>
                                <td>
                                    <span class="status <?= $session['end_time'] ? 'green' : 'orange' ?>">
                                        <?= $session['end_time'] ? 'Completed' : 'Active' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn btn-secondary" <?= $page <= 1 ? 'disabled' : '' ?>>
                        <i class="bi bi-chevron-left"></i> Prev
                    </button>
                    <span>Page <?= $page ?> of <?= $total_pages ?></span>
                    <button class="btn btn-secondary" <?= $page >= $total_pages ? 'disabled' : '' ?>>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
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

        // Session filter
        document.getElementById('sessionFilter').addEventListener('change', function() {
            const filter = this.value;
            window.location.href = `sessions.php?filter=${filter}`;
        });
    </script>
</body>
</html>