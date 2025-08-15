<?php
require_once 'config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expiry_settings'])) {
    $expiryDays = (int)$_POST['expiry_days'];
    $expiryHours = (int)$_POST['expiry_hours'];
    $expiryMinutes = (int)$_POST['expiry_minutes'];

    if ($expiryDays >= 0 && $expiryHours >= 0 && $expiryMinutes >= 0) {
        $totalMinutes = ($expiryDays * 24 * 60) + ($expiryHours * 60) + $expiryMinutes;

        if (isset($_SESSION['admin_id'])) {
            $admin_id = (int)$_SESSION['admin_id'];
            $stmt = $conn->prepare("
                INSERT INTO Settings (setting_key, setting_value, admin_id)
                VALUES ('voucher_default_duration_minutes', ?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), admin_id = VALUES(admin_id)
            ");
            $stmt->bind_param("di", $totalMinutes, $admin_id);

            if ($stmt->execute()) {
                logAdminActivity('Voucher Settings Updated', "Updated default voucher duration to $expiryDays days, $expiryHours hours, $expiryMinutes minutes");
                redirectWithMessage('vouchers.php', 'success', 'Voucher expiry settings updated successfully!');
            } else {
                redirectWithMessage('vouchers.php', 'error', 'Failed to update voucher expiry settings: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            redirectWithMessage('vouchers.php', 'error', 'Admin ID not set in session for voucher settings update.');
        }
    } else {
        redirectWithMessage('vouchers.php', 'error', 'Invalid expiry time values.');
    }
}

$currentDefaultDurationQuery = $conn->query("SELECT setting_value FROM Settings WHERE setting_key = 'voucher_default_duration_minutes'");
$currentDefaultDurationMinutes = 60; 
if ($currentDefaultDurationQuery && $currentDefaultDurationQuery->num_rows > 0) {
    $row = $currentDefaultDurationQuery->fetch_row();
    $currentDefaultDurationMinutes = (float)$row[0];
}
$expiryDays = 0;
$expiryHours = 1;
$expiryMinutes = 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$statusCondition = '';

if ($statusFilter === 'used') {
    $statusCondition = " AND v.status = 'used'";
} elseif ($statusFilter === 'unused') {
    $statusCondition = " AND v.status = 'unused'";
} elseif ($statusFilter === 'expired') {
    $statusCondition = " AND v.status = 'expired'";
}
$transactionIdFilter = isset($_GET['transaction_id']) ? (int)$_GET['transaction_id'] : 0;
$transactionCondition = $transactionIdFilter > 0 ? " AND v.transaction_id = $transactionIdFilter" : '';
$total_vouchers_query = "SELECT COUNT(*) FROM Voucher v WHERE 1=1 $statusCondition $transactionCondition";
$total_vouchers_result = $conn->query($total_vouchers_query);
$total_vouchers = $total_vouchers_result ? (int)$total_vouchers_result->fetch_row()[0] : 0;
$total_pages = ceil($total_vouchers / $per_page);
$vouchers_query = "
    SELECT v.voucher_id, v.voucher_code AS code, v.expiration, v.status, v.redeemed_at,
           t.created_at AS deposit_time
    FROM Voucher v
    JOIN Transactions t ON v.transaction_id = t.transaction_id
    WHERE 1=1 $statusCondition $transactionCondition
    ORDER BY v.voucher_id DESC
    LIMIT $per_page OFFSET $offset
";
$vouchers = $conn->query($vouchers_query)->fetch_all(MYSQLI_ASSOC);

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
            background: linear-gradient(135deg, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 1) 100%);
            color: black;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
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
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(49, 48, 48, 0.1);
            color: black;
            text-align: center;
            font-size: 1.1em;
            font-weight: bold;
        }

        .time-input-item input:focus {
            outline: none;
            border-color: rgba(42, 41, 41, 0.8);
            background: rgba(255, 255, 255, 0.2);
        }

        .time-input-item input::placeholder {
            color: rgba(36, 35, 35, 0.7);
        }

        .settings-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #03a3a0ff;
        }

        .settings-info i {
            margin-right: 8px;
        }

        .btn-save-settings {
            background: #03a3a0ff;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save-settings:hover {
            background: #03a3a0ff;
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

        .status.green { /* Used */
            background: #d4edda;
            color: #155724;
        }

        .status.red { /* Expired */
            background: #f8d7da;
            color: #721c24;
        }

        .status.orange { /* Unused */
            background: #fff3cd;
            color: #856404;
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
                    <a href="sessions.php">
                        <i class="bi bi-wifi"></i>
                        <span>Network Monitoring</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Admins</span>
                    </a>
                </li>
                <li>
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
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
            <h2><i class="bi bi-ticket-perforated"></i>Vouchers</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <h3>All Vouchers
                    <?php if ($transactionIdFilter > 0): ?>
                        <small class="text-muted">(for Transaction ID: #<?= $transactionIdFilter ?>)</small>
                    <?php endif; ?>
                </h3>
                <div class="filter-options">
                    <h5>Filter</h5>
                    <select id="status-filter">
                        <option value="">All Statuses</option>
                        <option value="used" <?php echo $statusFilter === 'used' ? 'selected' : ''; ?>>Used</option>
                        <option value="unused" <?php echo $statusFilter === 'unused' ? 'selected' : ''; ?>>Unused</option>
                        <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Expiration</th>
                                <th>Status</th>
                                <th>Created at</th>
                                <th>Redeemed At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vouchers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle"></i> No vouchers found for the selected criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($vouchers as $voucher): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($voucher['code']); ?></td>
                                        <td><?php echo date('M j, Y h:i A', strtotime($voucher['expiration'])); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            if ($voucher['status'] === 'used') {
                                                $status_class = 'green';
                                                $status_text = 'Used';
                                            } elseif ($voucher['status'] === 'expired') {
                                                $status_class = 'red';
                                                $status_text = 'Expired';
                                            } else { // 'unused'
                                                $status_class = 'orange';
                                                $status_text = 'Unused';
                                            }
                                            ?>
                                            <span class="status <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                        <td><?php echo date('M j, Y h:i A', strtotime($voucher['deposit_time'])); ?></td>
                                        <td><?= $voucher['redeemed_at'] ? date('M j, Y h:i A', strtotime($voucher['redeemed_at'])) : 'N/A' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $statusFilter; ?><?= $transactionIdFilter > 0 ? '&transaction_id=' . $transactionIdFilter : '' ?>"
                        class="btn btn-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="bi bi-chevron-left"></i> Previous
                    </a>
                    <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&status=<?php echo $statusFilter; ?><?= $transactionIdFilter > 0 ? '&transaction_id=' . $transactionIdFilter : '' ?>"
                        class="btn btn-secondary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="settings-card">
            <h4>
                <i class="bi bi-gear-fill"></i>
                Default Voucher Duration Settings
            </h4>

            <div class="settings-info">
                <i class="bi bi-info-circle"></i>
                <strong>Current Setting:</strong> New vouchers provide
                <?php echo number_format($currentDefaultDurationMinutes, 0); ?> minutes of internet access.
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
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });
        document.getElementById('status-filter').addEventListener('change', function() {
            const status = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('status', status);
            url.searchParams.set('page', 1); 
            window.location.href = url.toString();
        });
        document.querySelector('form').addEventListener('submit', function(e) {
            const days = parseInt(document.getElementById('expiry_days').value) || 0;
            const hours = parseInt(document.getElementById('expiry_hours').value) || 0;
            const minutes = parseInt(document.getElementById('expiry_minutes').value) || 0;
        
            if (days === 0 && hours === 0 && minutes === 0) {
                e.preventDefault();
                const messageBox = document.createElement('div');
                messageBox.className = 'alert alert-danger';
                messageBox.innerHTML = `<p>Please set at least one time value (days, hours, or minutes).</p>`;
                document.querySelector('.main-content').prepend(messageBox);
                setTimeout(() => messageBox.remove(), 5000);
                return false;
            }
            this.querySelector('button[type=submit]').disabled = true;
        });
    </script>
</body>

</html>
