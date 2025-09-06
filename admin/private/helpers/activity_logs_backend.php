<?php
require_once '../config.php';
checkAdminAuth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Only handle admin logs now
$table = 'SystemLog';
$join_condition = 'JOIN Admin a ON l.admin_id = a.admin_id';
$select_columns = 'l.*, a.username';
$log_count_query = "SELECT COUNT(*) FROM SystemLog";
$log_id_column = 'log_id';

$total_logs_result = $conn->query($log_count_query);
$total_logs = $total_logs_result ? $total_logs_result->fetch_row()[0] : 0;
$total_pages = ceil($total_logs / $per_page);

$logs_query = "
    SELECT {$select_columns}
    FROM {$table} l
    {$join_condition}
    ORDER BY l.timestamp DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$logs_result = $conn->query($logs_query);
$logs = $logs_result ? $logs_result->fetch_all(MYSQLI_ASSOC) : [];

?>