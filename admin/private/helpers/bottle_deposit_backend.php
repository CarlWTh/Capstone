<?php
require_once '../config.php';
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