<?php
require_once __DIR__ . '/../config/config.php';
checkAdminAuth();

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

?>