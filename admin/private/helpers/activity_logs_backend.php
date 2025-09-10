<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/utils_backend.php';
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

function logAdminActivity($action, $details = '') {
    global $conn;

    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];

        $check_sql = "SELECT admin_id FROM Admin WHERE admin_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $admin_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();

            $stmt = $conn->prepare("INSERT INTO SystemLog (admin_id, action, details) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $admin_id, $action, $details);
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Admin ID not found in Admin table.");
        }
    } else {
        error_log("Admin ID not set in session.");
    }
}
?>
