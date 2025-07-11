<?php
require_once 'config.php';
checkAdminAuth();

// Handle expiry settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expiry_settings'])) {
    $expiryDays = (int)$_POST['expiry_days'];
    $expiryHours = (int)$_POST['expiry_hours'];
    $expiryMinutes = (int)$_POST['expiry_minutes'];
    
    if ($expiryDays >= 0 && $expiryHours >= 0 && $expiryMinutes >= 0) {
        // Convert to total hours for storage
        $totalHours = ($expiryDays * 24) + $expiryHours + ($expiryMinutes / 60);
        
        // Update or insert the setting
        $stmt = $conn->prepare("
            INSERT INTO Settings (setting_key, setting_value) 
            VALUES ('voucher_expiry_hours', ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        $stmt->bind_param("dd", $totalHours, $totalHours);
        
        if ($stmt->execute()) {
            logAdminActivity('Voucher Settings Updated', "Updated voucher expiry to $expiryDays days, $expiryHours hours, $expiryMinutes minutes");
            redirectWithMessage('vouchers.php', 'success', 'Voucher expiry settings updated successfully!');
        } else {
            redirectWithMessage('vouchers.php', 'error', 'Failed to update voucher expiry settings.');
        }
    } else {
        redirectWithMessage('vouchers.php', 'error', 'Invalid expiry time values.');
    }
}

// Get current expiry settings
$currentExpiryQuery = $conn->query("SELECT setting_value FROM Settings WHERE setting_key = 'voucher_expiry_hours'");
$currentExpiryHours = $currentExpiryQuery->num_rows > 0 ? (float)$currentExpiryQuery->fetch_row()[0] : 24; // Default 24 hours

// Convert hours back to days, hours, minutes
$expiryDays = floor($currentExpiryHours / 24);
$remainingHours = $currentExpiryHours - ($expiryDays * 24);
$expiryHours = floor($remainingHours);
$expiryMinutes = round(($remainingHours - $expiryHours) * 60);

// Get all vouchers with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Handle status filter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$statusCondition = '';

if ($statusFilter === 'used') {
    $statusCondition = ' AND v.is_used = 1';
} elseif ($statusFilter === 'unused') {
    $statusCondition = ' AND v.is_used = 0 AND v.expiry_time > NOW()';
} elseif ($statusFilter === 'expired') {
    $statusCondition = ' AND v.is_used = 0 AND v.expiry_time < NOW()';
}

$total_vouchers = $conn->query("SELECT COUNT(*) FROM Voucher v WHERE 1=1 $statusCondition")->fetch_row()[0];
$total_pages = ceil($total_vouchers / $per_page);

$vouchers = $conn->query("
    SELECT v.voucher_id, v.code, v.expiry_time, v.is_used,
           d.timestamp as deposit_time 
    FROM Voucher v
    JOIN BottleDeposit d ON v.deposit_id = d.deposit_id
    WHERE 1=1 $statusCondition
    ORDER BY v.expiry_time DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Log activity
logAdminActivity('Vouchers Access', 'Viewed vouchers list');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vouchers - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .settings-card {
            background: linear-gradient(135deg,rgb(2, 16, 2) 0%,rgb(30, 37, 35) 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .settings-card h4 {
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .time-input-group {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }
        .time-input-item {
            text-align: center;
        }
        .time-input-item label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9em;
            opacity: 0.9;
        }
        .time-input-item input {
            width: 80px;
            padding: 8px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 8px;
            background: rgba(255,255,255,0.1);
            color: white;
            text-align: center;
            font-size: 1.1em;
            font-weight: bold;
        }
        .time-input-item input:focus {
            outline: none;
            border-color: rgba(255,255,255,0.8);
            background: rgba(255,255,255,0.2);
        }
        .time-input-item input::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .settings-info {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #4CAF50;
        }
        .settings-info i {
            margin-right: 8px;
        }
        .btn-save-settings {
            background: #4CAF50;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-save-settings:hover {
            background: #45a049;
            transform: translateY(-1px);
        }
        .filter-options {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .filter-options select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        .export-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .export-btn:hover {
            background: #0056b3;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        .pagination-info {
            font-size: 0.9em;
            color: #666;
        }
        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }
        .status.green {
            background: #d4edda;
            color: #155724;
        }
        .status.red {
            background: #f8d7da;
            color: #721c24;
        }
        .status.orange {
            background: #fff3cd;
            color: #856404;
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
                <li class="active">
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
                    <a href="network_monitoring.php">
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
            <h2>Vouchers</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="avatar-img">
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
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
                <h3>All Vouchers</h3>
                <div class="filter-options">
                    <select id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="used" <?php echo $statusFilter === 'used' ? 'selected' : ''; ?>>Used</option>
                        <option value="unused" <?php echo $statusFilter === 'unused' ? 'selected' : ''; ?>>Unused</option>
                        <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                    <button class="export-btn">
                        <i class="bi bi-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Expiry</th>
                                <th>Status</th>
                                <th>Deposit Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td><?php echo $voucher['code']; ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($voucher['expiry_time'])); ?></td>
                                <td>
                                    <?php if ($voucher['is_used']): ?>
                                        <span class="status green">Used</span>
                                    <?php elseif (strtotime($voucher['expiry_time']) < time()): ?>
                                        <span class="status red">Expired</span>
                                    <?php else: ?>
                                        <span class="status orange">Unused</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($voucher['deposit_time'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $statusFilter; ?>" 
                       class="btn btn-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&status=<?php echo $statusFilter; ?>" 
                       class="btn btn-secondary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Voucher Expiry Settings -->
        <div class="settings-card">
            <h4>
                <i class="bi bi-gear-fill"></i>
                Voucher Expiry Settings
            </h4>
            
            <div class="settings-info">
                <i class="bi bi-info-circle"></i>
                <strong>Current Setting:</strong> New vouchers expire after 
                <?php echo $expiryDays; ?> day(s), <?php echo $expiryHours; ?> hour(s), and <?php echo $expiryMinutes; ?> minute(s)
            </div>

            <form method="POST" action="vouchers.php">
                <input type="hidden" name="update_expiry_settings" value="1">
                
                <div class="time-input-group">
                    <div class="time-input-item">
                        <label for="expiry_days">Days</label>
                        <input type="number" id="expiry_days" name="expiry_days" value="<?php echo $expiryDays; ?>" min="0" max="365" placeholder="0">
                    </div>
                    
                    <div class="time-input-item">
                        <label for="expiry_hours">Hours</label>
                        <input type="number" id="expiry_hours" name="expiry_hours" value="<?php echo $expiryHours; ?>" min="0" max="23" placeholder="0">
                    </div>
                    
                    <div class="time-input-item">
                        <label for="expiry_minutes">Minutes</label>
                        <input type="number" id="expiry_minutes" name="expiry_minutes" value="<?php echo $expiryMinutes; ?>" min="0" max="59" placeholder="0">
                    </div>
                </div>

                <button type="submit" class="btn-save-settings">
                    <i class="bi bi-check-circle"></i> Save Settings
                </button>
            </form>
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

        // Status filter
        document.getElementById('status-filter').addEventListener('change', function() {
            const status = this.value;
            window.location.href = `vouchers.php?status=${status}`;
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const days = parseInt(document.getElementById('expiry_days').value) || 0;
            const hours = parseInt(document.getElementById('expiry_hours').value) || 0;
            const minutes = parseInt(document.getElementById('expiry_minutes').value) || 0;
            
            if (days === 0 && hours === 0 && minutes === 0) {
                e.preventDefault();
                alert('Please set at least one time value (days, hours, or minutes).');
                return false;
            }
        });
    </script>
</body>
</html>