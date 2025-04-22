<?php
require_once 'config.php';
checkAdminAuth();

// Get transactions based on filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';
$filter_text = 'All Transactions';

switch ($filter) {
    case '7days':
        $where = " WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $filter_text = 'Last 7 Days';
        break;
    case '30days':
        $where = " WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $filter_text = 'Last 30 Days';
        break;
    case 'month':
        $where = " WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
        $filter_text = 'This Month';
        break;
}

// Get total count for pagination
$result = $conn->query("SELECT COUNT(*) as total FROM transactions" . $where);
$total_transactions = $result->fetch_assoc()['total'];
$result->close();

// Pagination
$per_page = 10;
$total_pages = ceil($total_transactions / $per_page);
$page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($page - 1) * $per_page;

// Get transactions
$transactions = [];
$query = "SELECT t.transaction_date, t.bottle_count, t.credits_earned, u.username 
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          $where
          ORDER BY t.transaction_date DESC
          LIMIT $per_page OFFSET $offset";

$result = $conn->query($query);
if ($result) {
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="/css/styles.css">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1>Recycling Admin</h1>
                    <span class="logo-short"></span>
                </div>
                
                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class='bx bx-menu'></i>
                </button>
            </div>
            
            <nav>
                <ul>
                    <li class="active">
                        <a href="dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="transactions.php">
                            <i class='bx bx-transfer-alt'></i>
                            <span class="menu-text">Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="monitoring.php">
                            <i class='bx bx-line-chart'></i>
                            <span class="menu-text">System Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class='bx bx-cog'></i>
                            <span class="menu-text">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class='bx bxs-report'></i>
                            <span class="menu-text">Reports</span>
                        </a>
                    </li>
                    <li class="logout">
                        <a href="login.php">
                            <i class='bx bx-log-out'></i>
                            <span class="menu-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h2>Transactions</h2>
                <div class="user-info">
                    <span>Welcome, Admin</span>
                    <img src="/api/placeholder/40/40" alt="Admin Avatar">
                </div>
            </header>

    <div class="card transaction-logs">
        <div class="card-header">
            <h2>Transaction Logs</h2>
            <div class="description">
                <p>List of Bottle Deposits (Chronological list of transactions)</p>
            </div>
        </div>

        <div class="filter-options">
            <label for="date-filter">Filter by:</label>
            <select id="date-filter" onchange="window.location.href='transactions.php?filter='+this.value">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Transactions</option>
                <option value="7days" <?php echo $filter === '7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="30days" <?php echo $filter === '30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>This Month</option>
            </select>
            <button class="export-btn" onclick="exportTransactions()">Export CSV</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Bottles Deposited</th>
                    <th>Credits Earned</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['transaction_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['username'] ?? 'Guest'); ?></td>
                    <td><?php echo htmlspecialchars($transaction['bottle_count']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['credits_earned']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">No transactions found</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="transactions.php?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" class="btn-secondary">Previous</a>
            <?php endif; ?>
            
            <span class="pagination-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
            
            <?php if ($page < $total_pages): ?>
            <a href="transactions.php?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" class="btn-primary">Next</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportTransactions() {
            // In a real implementation, this would make an AJAX call to generate a CSV
            window.location.href = 'export_transactions.php?filter=<?php echo $filter; ?>';
        }
    </script>
     <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>