<?php
require_once 'config.php';
checkAdminAuth();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total_sessions = $conn->query("SELECT COUNT(*) FROM StudentSession")->fetch_row()[0];
$total_pages = ceil($total_sessions / $per_page);

$sessions = $conn->query("
    SELECT s.*, 
           COUNT(i.internet_session_id) as session_count,
           MAX(i.end_time) as last_activity
    FROM StudentSession s
    LEFT JOIN InternetSession i ON s.anonymous_token = i.anonymous_token
    GROUP BY s.session_id
    ORDER BY s.last_access_time DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

logAdminActivity('Sessions Access', 'Viewed student sessions');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Sessions - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-container">
<div class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h1><?php echo SITE_NAME; ?></h1>
                <span class="logo-short">BRS</span>
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
                <li class="active">
                        <a href="student_sessions.php">
                            <i class="bi bi-phone"></i>
                            <span class="menu-text">Student Sessions</span>
                        </a>
                    </li>
                <li><a href="sessions.php"><i class="bi bi-wifi"></i><span>Internet Sessions</span></a></li>
                <li><a href="bottles.php"><i class="bi bi-cup-straw"></i><span>Bottles</span></a></li>
                <li><a href="users.php"><i class="bi bi-people"></i><span>Users</span></a></li>
                <li><a href="activity_logs.php"><i class="bi bi-clock-history"></i><span>Activity Logs</span></a></li>
                <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </nav>
    </div>
    <!-- Sidebar (same as others) -->
    
    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-people"></i> Student Sessions</h2>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Active Sessions</h3>
            </div>
            
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Session ID</th>
                            <th>Anonymous Token</th>
                            <th>Device MAC</th>
                            <th>First Access</th>
                            <th>Last Access</th>
                            <th>Internet Sessions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><?= $s['session_id'] ?></td>
                            <td><code><?= substr($s['anonymous_token'], 0, 8) ?>...</code></td>
                            <td><?= $s['device_mac_address'] ?: 'N/A' ?></td>
                            <td><?= date('M j, H:i', strtotime($s['first_access_time'])) ?></td>
                            <td><?= date('M j, H:i', strtotime($s['last_access_time'])) ?></td>
                            <td><?= $s['session_count'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>" class="btn btn-sm btn-secondary">Previous</a>
                    <?php endif; ?>
                    
                    <span>Page <?= $page ?> of <?= $total_pages ?></span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>" class="btn btn-sm btn-secondary">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>