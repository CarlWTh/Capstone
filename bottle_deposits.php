<?php
require_once 'config.php';
checkAdminAuth(); 


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deposit'])) {
    $bottleCount = (int) $_POST['bottle_count'];
    $voucherDuration = getMinutesPerBottle(); 

    if ($bottleCount > 0) {
        $conn->begin_transaction();
        try {
            $placeholder_user_id = 1; 
            $time_credits_earned = $bottleCount * $voucherDuration;
            $stmt = $conn->prepare("INSERT INTO Transactions (user_id, bottle_count, time_credits_earned) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $placeholder_user_id, $bottleCount, $time_credits_earned);

            if ($stmt->execute()) {
                $transactionId = $conn->insert_id;
                logAdminActivity('Deposit Added', "Added a new bottle deposit of $bottleCount bottles (Transaction ID: $transactionId)");
                $currentTimestamp = new DateTime();
                $expirationDateTime = $currentTimestamp->modify("+$time_credits_earned minutes")->format('Y-m-d H:i:s');
                $voucherCode = generateUniqueVoucherCode($conn);
                $voucherStmt = $conn->prepare("INSERT INTO Voucher (transaction_id, voucher_code, Expiration, status, time_credits_value) VALUES (?, ?, ?, 'unused', ?)");
                $voucherStmt->bind_param("issd", $transactionId, $voucherCode, $expirationDateTime, $time_credits_earned);
                $voucherStmt->execute();

                if ($voucherStmt->error) {
                    throw new Exception("Voucher creation failed: " . $voucherStmt->error);
                }

                $conn->commit(); 
                redirectWithMessage('bottle_deposits.php', 'success', 'Deposit added and voucher created successfully!');
            } else {
                throw new Exception("Failed to add deposit: " . $stmt->error);
            }
        } catch (Exception $e) {
            $conn->rollback(); 
            error_log("Deposit/Voucher creation error: " . $e->getMessage());
            redirectWithMessage('bottle_deposits.php', 'error', 'Failed to add deposit. ' . $e->getMessage());
        }
    } else {
        redirectWithMessage('bottle_deposits.php', 'error', 'Bottle count must be greater than 0.');
    }
}
$timeFilter = $_GET['time_filter'] ?? 'week';
$customStartDate = $_GET['custom_start_date'] ?? '';
$customEndDate = $_GET['custom_end_date'] ?? '';
$dateCondition = '';
$params = array();
$paramTypes = '';

if ($timeFilter === 'day') {
    $dateCondition = "AND DATE(created_at) >= CURDATE() - INTERVAL 7 DAY";
} elseif ($timeFilter === 'week') {
    $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 4 WEEK)";
} elseif ($timeFilter === 'month') {
    $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($timeFilter === 'custom' && $customStartDate && $customEndDate) {
    $dateCondition = "AND DATE(created_at) BETWEEN ? AND ?";
    $params = array($customStartDate, $customEndDate);
    $paramTypes = 'ss';
}
$groupBy = '';
$dateFormat = '';

switch ($timeFilter) {
    case 'day':
        $groupBy = 'DATE(created_at)';
        $dateFormat = 'DATE(created_at) as period';
        break;
    case 'week':
        $groupBy = 'YEARWEEK(created_at)';
        $dateFormat = 'CONCAT(YEAR(created_at), "-Week ", LPAD(WEEK(created_at, 1), 2, "0")) as period'; 
        break;
    case 'month':
        $groupBy = 'DATE_FORMAT(created_at, "%Y-%m")';
        $dateFormat = 'DATE_FORMAT(created_at, "%Y-%m") as period';
        break;
    case 'custom':
        $groupBy = 'DATE(created_at)';
        $dateFormat = 'DATE(created_at) as period';
        break;
    default:
        $groupBy = 'YEARWEEK(created_at)';
        $dateFormat = 'CONCAT(YEAR(created_at), "-W", LPAD(WEEK(created_at, 1), 2, "0")) as period';
        break;
}

$statsQuery = "
    SELECT
        $dateFormat,
        COUNT(*) as deposit_count,
        SUM(bottle_count) as total_bottles,
        AVG(bottle_count) as avg_bottles_per_deposit
        
    FROM Transactions
    WHERE 1=1 $dateCondition
    GROUP BY $groupBy
    ORDER BY period DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($statsQuery);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $statsResult = $stmt->get_result();
    $depositStats = $statsResult->fetch_all(MYSQLI_ASSOC);
} else {
    $depositStats = $conn->query($statsQuery)->fetch_all(MYSQLI_ASSOC);
}
$overallStatsQuery = "
    SELECT
        COUNT(*) as total_deposits,
        SUM(bottle_count) as total_bottles,
        AVG(bottle_count) as avg_bottles_per_deposit,
        MIN(created_at) as first_deposit_date,
        MAX(created_at) as last_deposit_date
    FROM Transactions
    WHERE 1=1 $dateCondition
";

if (!empty($params)) {
    $stmt = $conn->prepare($overallStatsQuery);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $overallResult = $stmt->get_result();
    $overallStats = $overallResult->fetch_assoc();
} else {
    $overallStats = $conn->query($overallStatsQuery)->fetch_assoc();
}

$deposits = $conn->query("
    SELECT transaction_id AS deposit_id, bottle_count, created_at AS timestamp
    FROM Transactions
    ORDER BY created_at DESC
    LIMIT 20
")->fetch_all(MYSQLI_ASSOC);

logAdminActivity('Bottle Deposits Access', 'Viewed bottle deposits list');

function generateUniqueVoucherCode($conn)
{
    do {
        $voucherCode = substr(md5(uniqid(rand(), true)), 0, 10);
        $stmt = $conn->prepare("SELECT 1 FROM Voucher WHERE voucher_code = ?");
        $stmt->bind_param("s", $voucherCode);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    return $voucherCode;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Bottle Deposits - <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            height: 100%;
        }

        .stats-card h4 {
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .stat-value {
            font-size: 1.1rem;
            font-weight: bold;
        }

        .filter-section {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
            height: 100%;
        }

        .trend-up {
            color: #4caf50;
        }

        .trend-down {
            color: #ff5252;
        }

        .chart-container {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
        }

        .deposit-table th {
            background-color: var(--primary-color);
            color: white;
        }

        .bottle-icon {
            color: var(--primary-color);
            font-size: 1.25rem;
            margin-right: 0.5rem;
        }

        .time-filter-btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .time-filter-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
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
                <li class="active">
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
            <h2><i class="bi bi-recycle"></i> Bottle Deposits</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h4><i class="bi bi-collection"></i> Total Deposits</h4>
                    <div class="stat-item">
                        <span>All Time:</span>
                        <span class="stat-value"><?php echo number_format($overallStats['total_deposits'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>This Period:</span>
                        <span class="stat-value">
                            <?php echo number_format(array_sum(array_column($depositStats, 'deposit_count'))); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card">
                    <h4><i class="bi bi-recycle"></i> Total Bottles</h4>
                    <div class="stat-item">
                        <span>All Time:</span>
                        <span class="stat-value"><?php echo number_format($overallStats['total_bottles'] ?? 0); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>This Period:</span>
                        <span class="stat-value">
                            <?php echo number_format(array_sum(array_column($depositStats, 'total_bottles'))); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card">
                    <h4><i class="bi bi-graph-up"></i> Average</h4>
                    <div class="stat-item">
                        <span>Bottles/Deposit:</span>
                        <span class="stat-value"><?php echo number_format($overallStats['avg_bottles_per_deposit'] ?? 0, 1); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>Time Credits (Overall):</span>
                        <span class="stat-value">
                            <?php
                            $overallAvgTimeCredits = ($overallStats['avg_bottles_per_deposit'] ?? 0) * getMinutesPerBottle();
                            echo number_format($overallAvgTimeCredits, 1) . ' min';
                            ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span>Bottles/Deposit (Period):</span>
                        <span class="stat-value">
                            <?php
                            $total_bottles_period = array_sum(array_column($depositStats, 'total_bottles'));
                            $total_deposits_period = array_sum(array_column($depositStats, 'deposit_count'));
                            echo $total_deposits_period > 0 ? number_format($total_bottles_period / $total_deposits_period, 1) : '0.0';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="filter-section">
                    <h5><i class="bi bi-funnel"></i> Filter Period</h5>
                    <form method="GET" action="bottle_deposits.php">
                        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3" role="group">
                            <input type="radio" class="btn-check" name="time_filter" id="day_filter" value="day" <?php echo $timeFilter === 'day' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn" for="day_filter">Last 7 Days</label>

                            <input type="radio" class="btn-check" name="time_filter" id="week_filter" value="week" <?php echo $timeFilter === 'week' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn" for="week_filter">Last 4 Weeks</label>

                            <input type="radio" class="btn-check" name="time_filter" id="month_filter" value="month" <?php echo $timeFilter === 'month' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn" for="month_filter">Last 6 Months</label>
                        </div>

                        <div class="mb-3">
                            <input type="radio" class="btn-check" name="time_filter" id="custom_filter" value="custom" <?php echo $timeFilter === 'custom' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn w-100" for="custom_filter">Custom Range</label>
                        </div>

                        <div id="custom_date_group" style="display: <?php echo $timeFilter === 'custom' ? 'block' : 'none'; ?>;">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="custom_start_date" class="form-control" value="<?php echo htmlspecialchars($customStartDate); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="custom_end_date" class="form-control" value="<?php echo htmlspecialchars($customEndDate); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Apply Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-graph-up"></i> Deposit Trends</h4>
                <div>
                    <span class="badge bg-primary">
                        <?php
                        $filterText = '';
                        if ($timeFilter === 'day') $filterText = 'Last 7 Days (Daily)';
                        else if ($timeFilter === 'week') $filterText = 'Last 4 Weeks (Weekly)';
                        else if ($timeFilter === 'month') $filterText = 'Last 6 Months (Monthly)';
                        else if ($timeFilter === 'custom') $filterText = 'Custom Range';
                        echo htmlspecialchars($filterText);
                        ?>
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Deposits</th>
                            <th>Bottles</th>
                            <th>Avg. Bottles</th>
                            <th>Avg. Time Credits</th>
                            <th>Trend (vs. prev. period)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $prevCount = null;
                        foreach ($depositStats as $index => $stat):
                            $trendIcon = '';
                            if ($prevCount !== null) {
                                if ($stat['deposit_count'] > $prevCount) {
                                    $percentageChange = $prevCount > 0 ? round(($stat['deposit_count'] - $prevCount) / $prevCount * 100) : 100;
                                    $trendIcon = '<i class="bi bi-arrow-up trend-up"></i> ' . $percentageChange . '%';
                                } elseif ($stat['deposit_count'] < $prevCount) {
                                    $percentageChange = $prevCount > 0 ? round(($prevCount - $stat['deposit_count']) / $prevCount * 100) : 100;
                                    $trendIcon = '<i class="bi bi-arrow-down trend-down"></i> ' . $percentageChange . '%';
                                } else {
                                    $trendIcon = '<span class="text-muted">No change</span>';
                                }
                            }
                            $prevCount = $stat['deposit_count'];
                        ?>
                            <tr>
                                <td>
                                    <span>
                                        <?php echo htmlspecialchars($stat['period']); ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($stat['deposit_count']); ?></td>
                                <td><?php echo number_format($stat['total_bottles']); ?></td>
                                <td><?php echo number_format($stat['avg_bottles_per_deposit'], 1); ?></td>
                                <td>
                                    <?php
                                    $avgTimeCreditsPerPeriodDeposit = $stat['avg_bottles_per_deposit'] * getMinutesPerBottle();
                                    echo number_format($avgTimeCreditsPerPeriodDeposit, 1) . ' min';
                                    ?>
                                </td>
                                <td><?php echo $trendIcon; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($depositStats)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle"></i> No deposit data available for the selected period.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><i class="bi bi-clock-history"></i> Recent Deposits</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepositModal">
                    <i class="bi bi-plus"></i> Add Deposit
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover deposit-table">
                        <thead>
                            <tr>
                                <th>Bottles</th>
                                <th>Time Credits</th>
                                <th>Timestamp</th>
                                <th>Vouchers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deposits)): ?>
                                <?php foreach ($deposits as $deposit): ?>
                                    <tr>
                                        <td>
                                            <i class="bi bi-recycle bottle-icon"></i>
                                            <?php echo htmlspecialchars($deposit['bottle_count']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $timeCredits = $deposit['bottle_count'] * getMinutesPerBottle();
                                            echo number_format($timeCredits) . ' min';
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('M j, Y h:i A', strtotime($deposit['timestamp']))); ?></td>
                                        <td>
                                            <a href="vouchers.php?transaction_id=<?php echo htmlspecialchars($deposit['deposit_id']); ?>" class="btn btn-sm btn-outline-primary">
                                                View Vouchers
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle"></i> No recent deposits found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addDepositModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i> Add New Deposit
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="bottle_deposits.php">
                        <input type="hidden" name="add_deposit" value="1">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="bottle_count" class="form-label">
                                    <i class="bi bi-recycle"></i> Number of Bottles
                                </label>
                                <input type="number" class="form-control" id="bottle_count"
                                    name="bottle_count" min="1" required
                                    placeholder="Enter number of bottles deposited">
                                <div class="form-text">
                                    Each bottle will generate a unique voucher code for internet access.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Add Deposit
                            </button>
                        </div>
                    </form>
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
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });
        document.querySelectorAll('input[name="time_filter"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const customDateGroup = document.getElementById('custom_date_group');
                if (this.value === 'custom') {
                    customDateGroup.style.display = 'block';
                } else {
                    customDateGroup.style.display = 'none';
                }
            });
        });
        window.addEventListener('click', function(e) {
            const profileDropdown = document.querySelector('.profile-dropdown');
            if (!profileDropdown.contains(e.target)) {
                document.querySelector('.dropdown-content').classList.remove('show-dropdown');
            }
        });
    </script>
</body>

</html>