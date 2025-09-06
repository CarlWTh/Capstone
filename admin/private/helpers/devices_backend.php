<?php
require_once __DIR__ . '/../config/config.php';
checkAdminAuth();

$active_tab = $_GET['tab'] ?? 'active-sessions';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;
$total_records = 0;
$records = [];

switch ($active_tab) {
    case 'active-sessions':
        $stmt = $conn->prepare("SELECT COUNT(*) FROM UserSessions WHERE end_time IS NULL");
        $stmt->execute();
        $stmt->bind_result($total_records);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT us.session_id, us.ip_address, us.start_time, us.end_time, us.duration_minutes,
                   u.mac_address, v.voucher_code
            FROM UserSessions us
            JOIN User u ON us.user_id = u.user_id
            LEFT JOIN Voucher v ON us.voucher_id = v.voucher_id
            WHERE us.end_time IS NULL
            ORDER BY us.start_time DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;

    case 'session-logs':
        $stmt = $conn->prepare("SELECT COUNT(*) FROM UserSessions");
        $stmt->execute();
        $stmt->bind_result($total_records);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT us.session_id, us.ip_address, us.start_time, us.end_time, us.duration_minutes,
                   u.mac_address, v.voucher_code
            FROM UserSessions us
            JOIN User u ON us.user_id = u.user_id
            LEFT JOIN Voucher v ON us.voucher_id = v.voucher_id
            ORDER BY us.start_time DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;

    case 'user-sessions':
        $stmt = $conn->prepare("SELECT COUNT(*) FROM User");
        $stmt->execute();
        $stmt->bind_result($total_records);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("
            SELECT u.user_id, us.ip_address, u.time_credits, u.last_active, u.created_at,
                   COUNT(us.session_id) as internet_session_count,
                   MIN(us.start_time) as first_session_access,
                   MAX(us.end_time) as last_session_access
            FROM User u
            LEFT JOIN UserSessions us ON u.user_id = us.user_id
            GROUP BY u.user_id
            ORDER BY u.user_id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $records = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;
}

$total_pages = $per_page > 0 ? ceil($total_records / $per_page) : 1;

?>