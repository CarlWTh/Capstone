<?php
require_once 'config.php';
checkAdminAuth();

// Handle new deposit addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_deposit']))
{
    $bottleCount = (int) $_POST['bottle_count'];

    if ($bottleCount > 0)
    {
        $stmt = $conn->prepare("INSERT INTO BottleDeposit (bottle_count) VALUES (?)");
        $stmt->bind_param("i", $bottleCount); 
        if ($stmt->execute()) 
        {   
            logAdminActivity('Deposit Added', "Added a new bottle deposit of $bottleCount bottles");
            $depositId = $conn->insert_id;
            for ($i = 0; $i < $bottleCount; $i++)
            {
                $voucherCode = generateUniqueVoucherCode($conn);
                $voucherStmt = $conn->prepare("INSERT INTO Voucher (code, deposit_id) VALUES (?, ?)"); 
                $voucherStmt->bind_param("si", $voucherCode, $depositId); // Bind parameters
                $voucherStmt->execute();
            }
            redirectWithMessage('bottle_deposits.php', 'success', 'Deposit added and vouchers created successfully!'); 
        }
        else
        {
            redirectWithMessage('bottle_deposits.php', 'error', 'Failed to add deposit.');
        }
    }
}

// Get filter parameters
$timeFilter = $_GET['time_filter'] ?? 'week';
$customStartDate = $_GET['custom_start_date'] ?? '';
$customEndDate = $_GET['custom_end_date'] ?? '';

// Build the date filter condition
$dateCondition = '';
$params = array();
$paramTypes = '';

if ($timeFilter === 'day') {
    $dateCondition = "AND DATE(timestamp) >= CURDATE() - INTERVAL 7 DAY";
} elseif ($timeFilter === 'week') {
    $dateCondition = "AND timestamp >= DATE_SUB(NOW(), INTERVAL 4 WEEK)";
} elseif ($timeFilter === 'month') {
    $dateCondition = "AND timestamp >= DATE_SUB(NOW(), INTERVAL 6 MONTH)";
} elseif ($timeFilter === 'custom' && $customStartDate && $customEndDate) {
    $dateCondition = "AND DATE(timestamp) BETWEEN ? AND ?";
    $params = array($customStartDate, $customEndDate);
    $paramTypes = 'ss';
}

// Get deposit statistics based on filter
$groupBy = $timeFilter === 'day' ? 'DATE(timestamp)' : 
           ($timeFilter === 'week' ? 'YEARWEEK(timestamp)' : 'DATE_FORMAT(timestamp, "%Y-%m")');

$dateFormat = $timeFilter === 'day' ? 'DATE(timestamp) as period' : 
              ($timeFilter === 'week' ? 'CONCAT(YEAR(timestamp), "-W", LPAD(WEEK(timestamp), 2, "0")) as period' : 
               'DATE_FORMAT(timestamp, "%Y-%m") as period');

$statsQuery = "
    SELECT 
        $dateFormat,
        COUNT(*) as deposit_count,
        SUM(bottle_count) as total_bottles,
        AVG(bottle_count) as avg_bottles_per_deposit
    FROM BottleDeposit 
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

// Get overall statistics
$overallStatsQuery = "
    SELECT 
        COUNT(*) as total_deposits,
        SUM(bottle_count) as total_bottles,
        AVG(bottle_count) as avg_bottles_per_deposit,
        MIN(timestamp) as first_deposit_date,
        MAX(timestamp) as last_deposit_date
    FROM BottleDeposit
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
    SELECT deposit_id, bottle_count, timestamp  
    FROM BottleDeposit
    ORDER BY timestamp DESC  
")->fetch_all(MYSQLI_ASSOC);

logAdminActivity('Bottle Deposits Access', 'Viewed bottle deposits list');

function generateUniqueVoucherCode($conn)
{
    do {
        $voucherCode = substr(md5(uniqid(rand(), true)), 0, 10);
        $stmt = $conn->prepare("SELECT 1 FROM Voucher WHERE code = ?");
        $stmt->bind_param("s", $voucherCode);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);
    return $voucherCode; 
}
?>
<?php
require_once 'config.php';
checkAdminAuth();

// [Previous PHP code remains exactly the same...]
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
            border-bottom: 1px solid rgba(255,255,255,0.15);
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
        
        .period-badge {
            background: rgba(255,255,255,0.15);
            color: white;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
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
            <h2><i class="bi bi-recycle"></i> Bottle Deposits</h2>
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

        <?php displayFlashMessage();?>

        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <h4><i class="bi bi-collection"></i> Total Deposits</h4>
                    <div class="stat-item">
                        <span>All Time:</span>
                        <span class="stat-value"><?php echo number_format($overallStats['total_deposits']); ?></span>
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
                        <span class="stat-value"><?php echo number_format($overallStats['total_bottles']); ?></span>
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
                        <span class="stat-value"><?php echo number_format($overallStats['avg_bottles_per_deposit'], 1); ?></span>
                    </div>
                    <div class="stat-item">
                        <span>This Period:</span>
                        <span class="stat-value">
                            <?php 
                            $total = array_sum(array_column($depositStats, 'total_bottles'));
                            $count = array_sum(array_column($depositStats, 'deposit_count'));
                            echo $count > 0 ? number_format($total/$count, 1) : '0.0';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="filter-section">
                    <h5><i class="bi bi-funnel"></i> Filter Period</h5>
                    <form method="GET" action="bottle_deposits.php">
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="time_filter" id="day_filter" value="day" <?php echo $timeFilter === 'day' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn" for="day_filter">Daily</label>
                            
                            <input type="radio" class="btn-check" name="time_filter" id="week_filter" value="week" <?php echo $timeFilter === 'week' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn" for="week_filter">Weekly</label>
                            
                            <input type="radio" class="btn-check" name="time_filter" id="month_filter" value="month" <?php echo $timeFilter === 'month' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary time-filter-btn" for="month_filter">Monthly</label>
                        </div>
                        
                        <div id="custom_date_group" style="display: <?php echo $timeFilter === 'custom' ? 'block' : 'none'; ?>;">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="custom_start_date" class="form-control" value="<?php echo $customStartDate; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="custom_end_date" class="form-control" value="<?php echo $customEndDate; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i> Apply Filter
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Deposit Trends -->
        <div class="chart-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-graph-up"></i> Deposit Trends</h4>
                <div>
                    <span class="badge bg-primary">
                        <?php echo ucfirst($timeFilter); ?> View
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
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prevCount = null;
                        foreach ($depositStats as $index => $stat): 
                            $trendIcon = '';
                            if ($prevCount !== null) {
                                if ($stat['deposit_count'] > $prevCount) {
                                    $trendIcon = '<i class="bi bi-arrow-up trend-up"></i> ' . round(($stat['deposit_count'] - $prevCount)/$prevCount * 100) . '%';
                                } elseif ($stat['deposit_count'] < $prevCount) {
                                    $trendIcon = '<i class="bi bi-arrow-down trend-down"></i> ' . round(($prevCount - $stat['deposit_count'])/$prevCount * 100) . '%';
                                } else {
                                    $trendIcon = '<span class="text-muted">No change</span>';
                                }
                            }
                            $prevCount = $stat['deposit_count'];
                        ?>
                            <tr>
                                <td>
                                    <span class="period-badge">
                                        <?php echo htmlspecialchars($stat['period']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo number_format($stat['deposit_count']); ?></strong></td>
                                <td><?php echo number_format($stat['total_bottles']); ?></td>
                                <td><?php echo number_format($stat['avg_bottles_per_deposit'], 1); ?></td>
                                <td><?php echo $trendIcon; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($depositStats)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-info-circle"></i> No deposit data available for the selected period
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Deposits -->
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
                                <th>ID</th>
                                <th>Bottles</th>
                                <th>Date</th>
                                <th>Vouchers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deposits as $deposit):?>
                                <tr>
                                    <td>#<?php echo $deposit['deposit_id']; ?></td>
                                    <td>
                                        <i class="bi bi-recycle bottle-icon"></i>
                                        <?php echo $deposit['bottle_count']; ?>
                                    </td>
                                    <td><?php echo date('M j, Y H:i', strtotime($deposit['timestamp'])); ?></td>
                                    <td>
                                        <a href="vouchers.php?deposit_id=<?php echo $deposit['deposit_id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View Vouchers
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Deposit Modal -->
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
        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Profile dropdown
        document.querySelector('.dropdown-header').addEventListener('click', function () {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        // Toggle custom date fields when custom radio is selected
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
    </script>
</body>
</html>