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

// Pagination for recent deposits
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$depositsPerPage = isset($_GET['per_page']) ? max(5, min(50, intval($_GET['per_page']))) : 10;
$offset = ($page - 1) * $depositsPerPage;

// Get total count of deposits for pagination
$totalDepositsResult = $conn->query("SELECT COUNT(*) as total FROM Transactions");
$totalDeposits = $totalDepositsResult->fetch_assoc()['total'];
$totalPages = ceil($totalDeposits / $depositsPerPage);

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

// Fetch paginated deposits
$depositsQuery = "
    SELECT transaction_id AS deposit_id, bottle_count, created_at AS timestamp
    FROM Transactions
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($depositsQuery);
$stmt->bind_param("ii", $depositsPerPage, $offset);
$stmt->execute();
$deposits = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

// Helper function to generate pagination URL
function generatePaginationUrl($page, $perPage = null, $preserveParams = true) {
    $params = [];
    
    if ($preserveParams) {
        // Preserve existing GET parameters
        foreach ($_GET as $key => $value) {
            if ($key !== 'page' && $key !== 'per_page') {
                $params[$key] = $value;
            }
        }
    }
    
    $params['page'] = $page;
    if ($perPage !== null) {
        $params['per_page'] = $perPage;
    } elseif (isset($_GET['per_page'])) {
        $params['per_page'] = $_GET['per_page'];
    }
    
    return 'bottle_deposits.php?' . http_build_query($params);
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
    <link rel="stylesheet" href="/css/bottle-deposit.css">
    <style>
        /* Modern Pagination - Activity Logs Style */
        .modern-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 16px;
            margin-top: 32px;
            padding: 24px 0;
            border-top: 1px solid #e9ecef;
        }

        .modern-pagination .btn {
            border: 2px solid #e9ecef;
            background: white;
            color: #495057;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .modern-pagination .btn:hover {
            border-color: #007bff;
            color: #007bff;
            background: #f8f9ff;
        }

        .modern-pagination .btn.disabled,
        .modern-pagination .btn:disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .pagination-info {
            font-size: 0.9em;
            color: #6c757d;
            background: #f8f9fa;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 500;
        }

        .per-page-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #6c757d;
            position: absolute;
            right: 0;
            background: #f8f9fa;
            padding: 8px 16px;
            border-radius: 12px;
        }

        .per-page-select {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 14px;
            color: #495057;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        .per-page-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .pagination-wrapper {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        @media (max-width: 768px) {
            .modern-pagination {
                flex-direction: column;
                gap: 12px;
            }
            
            .per-page-selector {
                position: static;
                margin-bottom: 16px;
            }
            
            .pagination-wrapper {
                flex-direction: column;
                gap: 16px;
            }
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
            <h2><i class="bi bi-recycle"></i> Bottle Deposits</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="deposit-stats-card deposit-stats-card-deposits">
                    <div class="deposit-stats-header">
                        <i class="bi bi-collection header-icon" style="color: #007bff;"></i>
                        Total Deposits
                    </div>
                    <div class="deposit-stats-body">
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-infinity metric-icon"></i>
                                All Time
                            </span>
                            <span class="stat-value"><?php echo number_format($overallStats['total_deposits'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-calendar-range metric-icon"></i>
                                This Period
                            </span>
                            <span class="stat-value">
                                <?php echo number_format(array_sum(array_column($depositStats, 'deposit_count'))); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="deposit-stats-card deposit-stats-card-bottles">
                    <div class="deposit-stats-header">
                        <i class="bi bi-recycle header-icon" style="color: #28a745;"></i>
                        Total Bottles
                    </div>
                    <div class="deposit-stats-body">
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-infinity metric-icon"></i>
                                All Time
                            </span>
                            <span class="stat-value"><?php echo number_format($overallStats['total_bottles'] ?? 0); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-calendar-range metric-icon"></i>
                                This Period
                            </span>
                            <span class="stat-value">
                                <?php echo number_format(array_sum(array_column($depositStats, 'total_bottles'))); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="deposit-stats-card deposit-stats-card-average">
                    <div class="deposit-stats-header">
                        <i class="bi bi-graph-up header-icon" style="color: #17a2b8;"></i>
                        Averages
                    </div>
                    <div class="deposit-stats-body">
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-calculator metric-icon"></i>
                                Bottles/Deposit
                            </span>
                            <span class="stat-value"><?php echo number_format($overallStats['avg_bottles_per_deposit'] ?? 0, 1); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-clock metric-icon"></i>
                                Time Credits
                            </span>
                            <span class="stat-value">
                                <?php
                                $overallAvgTimeCredits = ($overallStats['avg_bottles_per_deposit'] ?? 0) * getMinutesPerBottle();
                                echo number_format($overallAvgTimeCredits, 1) . ' min';
                                ?>
                            </span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">
                                <i class="bi bi-calendar-week metric-icon"></i>
                                Period Avg
                            </span>
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
            </div>

            <div class="col-md-3">
                <div class="modern-filter-section">
                    <h5 class="filter-title">
                        <i class="bi bi-funnel header-icon" style="color: #6f42c1;"></i>
                        Filter Period
                    </h5>
                    <form method="GET" action="bottle_deposits.php">
                        <input type="hidden" name="page" value="1">
                        <?php if (isset($_GET['per_page'])): ?>
                            <input type="hidden" name="per_page" value="<?php echo htmlspecialchars($_GET['per_page']); ?>">
                        <?php endif; ?>
                        <div class="d-flex flex-column gap-3 mb-3">
                            <label class="modern-filter-btn <?php echo $timeFilter === 'day' ? 'active' : ''; ?>">
                                <input type="radio" name="time_filter" value="day" <?php echo $timeFilter === 'day' ? 'checked' : ''; ?> style="display: none;">
                                Last 7 Days
                            </label>

                            <label class="modern-filter-btn <?php echo $timeFilter === 'week' ? 'active' : ''; ?>">
                                <input type="radio" name="time_filter" value="week" <?php echo $timeFilter === 'week' ? 'checked' : ''; ?> style="display: none;">
                                Last 4 Weeks
                            </label>

                            <label class="modern-filter-btn <?php echo $timeFilter === 'month' ? 'active' : ''; ?>">
                                <input type="radio" name="time_filter" value="month" <?php echo $timeFilter === 'month' ? 'checked' : ''; ?> style="display: none;">
                                Last 6 Months
                            </label>

                            <label class="modern-filter-btn <?php echo $timeFilter === 'custom' ? 'active' : ''; ?>">
                                <input type="radio" name="time_filter" value="custom" <?php echo $timeFilter === 'custom' ? 'checked' : ''; ?> style="display: none;">
                                Custom Range
                            </label>
                        </div>

                        <div id="custom_date_group" style="display: <?php echo $timeFilter === 'custom' ? 'block' : 'none'; ?>;">
                            <div class="mb-3">
                                <label class="form-label" style="color: #495057; font-weight: 600;">Start Date</label>
                                <input type="date" name="custom_start_date" class="form-control modern-date-input" value="<?php echo htmlspecialchars($customStartDate); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" style="color: #495057; font-weight: 600;">End Date</label>
                                <input type="date" name="custom_end_date" class="form-control modern-date-input" value="<?php echo htmlspecialchars($customEndDate); ?>">
                            </div>
                        </div>

                        <button type="submit" class="apply-filter-btn">
                            <i class="bi bi-filter"></i> Apply Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="modern-chart-container">
            <div class="chart-header">
                <h4 class="chart-title">
                    <i class="bi bi-graph-up"></i>
                    Deposit Trends
                </h4>
                <div>
                    <span class="period-badge">
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
                <table class="table modern-table">
                    <thead>
                        <tr>
                            <th><i class="bi bi-calendar3 me-2"></i>Period</th>
                            <th><i class="bi bi-collection me-2"></i>Deposits</th>
                            <th><i class="bi bi-recycle me-2"></i>Bottles</th>
                            <th><i class="bi bi-calculator me-2"></i>Avg. Bottles</th>
                            <th><i class="bi bi-clock me-2"></i>Avg. Time Credits</th>
                            <th><i class="bi bi-graph-up me-2"></i>Trend</th>
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
                                    $trendIcon = '<i class="bi bi-arrow-up trend-up"></i> +' . $percentageChange . '%';
                                } elseif ($stat['deposit_count'] < $prevCount) {
                                    $percentageChange = $prevCount > 0 ? round(($prevCount - $stat['deposit_count']) / $prevCount * 100) : 100;
                                    $trendIcon = '<i class="bi bi-arrow-down trend-down"></i> -' . $percentageChange . '%';
                                } else {
                                    $trendIcon = '<span class="text-muted"><i class="bi bi-dash"></i> No change</span>';
                                }
                            }
                            $prevCount = $stat['deposit_count'];
                        ?>
                            <tr>
                                <td>
                                    <strong style="color: #495057;">
                                        <?php echo htmlspecialchars($stat['period']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 6px 12px; border-radius: 12px;">
                                        <?php echo number_format($stat['deposit_count']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; padding: 6px 12px; border-radius: 12px;">
                                        <?php echo number_format($stat['total_bottles']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #6c757d; font-family: monospace; font-weight: 600;">
                                        <?php echo number_format($stat['avg_bottles_per_deposit'], 1); ?>
                                    </span>
                                </td>
                                <td>
                                    <span style="color: #6c757d; font-family: monospace; font-weight: 600;">
                                        <?php
                                        $avgTimeCreditsPerPeriodDeposit = $stat['avg_bottles_per_deposit'] * getMinutesPerBottle();
                                        echo number_format($avgTimeCreditsPerPeriodDeposit, 1) . ' min';
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $trendIcon; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($depositStats)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle me-2"></i>
                                    No deposit data available for the selected period.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modern-card">
            <div class="modern-card-header">
                <h3 class="modern-card-title">
                    <i class="bi bi-clock-history"></i>
                    Recent Deposits
                </h3>
                <button class="modern-btn-primary" data-bs-toggle="modal" data-bs-target="#addDepositModal">
                    <i class="bi bi-plus"></i>
                    Add Deposit
                </button>
            </div>
            <div class="modern-card-body">
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-recycle me-2"></i>Bottles</th>
                                <th><i class="bi bi-clock me-2"></i>Time Credits</th>
                                <th><i class="bi bi-calendar3 me-2"></i>Timestamp</th>
                                <th><i class="bi bi-ticket-perforated me-2"></i>Vouchers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deposits)): ?>
                                <?php foreach ($deposits as $deposit): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <i class="bi bi-recycle bottle-icon"></i>
                                                <span style="font-weight: 600; color: #495057;">
                                                    <?php echo htmlspecialchars($deposit['bottle_count']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 8px 16px; border-radius: 12px; font-family: monospace;">
                                                <?php
                                                $timeCredits = $deposit['bottle_count'] * getMinutesPerBottle();
                                                echo number_format($timeCredits) . ' min';
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: #6c757d; font-size: 14px;">
                                                <?php echo htmlspecialchars(date('M j, Y h:i A', strtotime($deposit['timestamp']))); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="vouchers.php?transaction_id=<?php echo htmlspecialchars($deposit['deposit_id']); ?>" 
                                               class="btn btn-outline-primary btn-sm" 
                                               style="border-radius: 12px; font-weight: 500; padding: 8px 16px;">
                                                <i class="bi bi-eye me-1"></i>
                                                View Vouchers
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <div style="color: #6c757d;">
                                            <i class="bi bi-info-circle" style="font-size: 2rem; margin-bottom: 16px; display: block;"></i>
                                            <h5>No recent deposits found</h5>
                                            <p class="mb-0">Start by adding your first bottle deposit!</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Modern Pagination - Activity Logs Style -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="per-page-selector">
                            <span>Show:</span>
                            <select class="per-page-select" onchange="changePerPage(this.value)">
                                <option value="5" <?php echo $depositsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?php echo $depositsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                                <option value="20" <?php echo $depositsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                                <option value="50" <?php echo $depositsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                            </select>
                        </div>

                        <div class="modern-pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo generatePaginationUrl($page - 1); ?>" class="btn">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </a>
                            <?php else: ?>
                                <button class="btn disabled">
                                    <i class="bi bi-chevron-left"></i> Previous
                                </button>
                            <?php endif; ?>

                            <div class="pagination-info">
                                Showing <?php echo number_format(($page - 1) * $depositsPerPage + 1); ?>-<?php echo number_format(min($page * $depositsPerPage, $totalDeposits)); ?> of <?php echo number_format($totalDeposits); ?> deposits (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
                            </div>

                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo generatePaginationUrl($page + 1); ?>" class="btn">
                                    Next <i class="bi bi-chevron-right"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn disabled">
                                    Next <i class="bi bi-chevron-right"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modern Modal -->
        <div class="modal fade modern-modal" id="addDepositModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle"></i>
                            Add New Deposit
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="bottle_deposits.php">
                        <input type="hidden" name="add_deposit" value="1">
                        <div class="modal-body">
                            <div class="mb-4">
                                <label for="bottle_count" class="form-label" style="color: #495057; font-weight: 600; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                                    <i class="bi bi-recycle" style="color: #28a745;"></i>
                                    Number of Bottles
                                </label>
                                <input type="number" 
                                       class="form-control modern-form-control" 
                                       id="bottle_count"
                                       name="bottle_count" 
                                       min="1" 
                                       required
                                       placeholder="Enter number of bottles deposited"
                                       style="font-size: 16px;">
                                <div class="form-text" style="color: #6c757d; margin-top: 8px; font-size: 14px;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Each bottle will generate a unique voucher code for internet access.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 10px; padding: 10px 18px; font-weight: 700; color: #0d0e0fff;">
                                <i class="bi bi-x" style="font-size: 20px;"></i> Cancel
                            </button>
                            <button type="submit" class="modern-btn-primary">
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

        // Modern filter button interactions
        document.querySelectorAll('.modern-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.modern-filter-btn').forEach(b => b.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                }
                
                // Show/hide custom date range
                const customDateGroup = document.getElementById('custom_date_group');
                if (radio && radio.value === 'custom') {
                    customDateGroup.style.display = 'block';
                } else {
                    customDateGroup.style.display = 'none';
                }
            });
        });

        // Initialize filter state on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeRadio = document.querySelector('input[name="time_filter"]:checked');
            if (activeRadio) {
                const customDateGroup = document.getElementById('custom_date_group');
                if (activeRadio.value === 'custom') {
                    customDateGroup.style.display = 'block';
                } else {
                    customDateGroup.style.display = 'none';
                }
            }
        });

        // Pagination: Change items per page
        function changePerPage(perPage) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', perPage);
            urlParams.set('page', '1'); // Reset to first page
            window.location.search = urlParams.toString();
        }

        // Add smooth scrolling to pagination links
        document.querySelectorAll('.modern-pagination .btn').forEach(link => {
            if (link.href && !link.classList.contains('disabled')) {
                link.addEventListener('click', function(e) {
                    // Add loading state
                    this.style.opacity = '0.7';
                    this.style.pointerEvents = 'none';
                    
                    // Add a subtle loading indicator
                    const originalContent = this.innerHTML;
                    const isNext = this.innerHTML.includes('Next');
                    const isPrev = this.innerHTML.includes('Previous');
                    
                    if (isNext) {
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
                    } else if (isPrev) {
                        this.innerHTML = '<i class="bi bi-hourglass-split"></i> Loading...';
                    }
                    
                    // Let the navigation proceed naturally after a brief delay
                    setTimeout(() => {
                        this.innerHTML = originalContent;
                        this.style.opacity = '1';
                        this.style.pointerEvents = 'auto';
                    }, 300);
                });
            }
        });
    </script>
</body>

</html>