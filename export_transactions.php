<?php
require_once 'config.php';
checkAdminAuth();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="transactions_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['Timestamp', 'User', 'Bottles Deposited', 'Credits Earned']);

// Get filter from query string
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where = '';

switch ($filter) {
    case '7days':
        $where = " WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case '30days':
        $where = " WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'month':
        $where = " WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) AND YEAR(transaction_date) = YEAR(CURRENT_DATE())";
        break;
}

// Query transactions
$query = "SELECT t.transaction_date, u.username, t.bottle_count, t.credits_earned 
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          $where
          ORDER BY t.transaction_date DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['transaction_date'],
            $row['username'] ?? 'Guest',
            $row['bottle_count'],
            $row['credits_earned']
        ]);
    }
}

fclose($output);
exit();