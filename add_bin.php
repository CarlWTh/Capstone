php
<?php
require_once 'config.php';
checkAdminAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $capacity = (float)$_POST['capacity'];
    $current_level = (float)$_POST['current_level'];
    $status = $_POST['status'];

    if ($capacity < 0 || $current_level < 0) {
        redirectWithMessage('bins.php', 'error', 'Capacity and current level must be non-negative.');
    }
    if (!in_array($status, ['empty', 'partial', 'full', 'maintenance'])) {
        redirectWithMessage('bins.php', 'error', 'Invalid status value.');
    }
    
    $stmt = $conn->prepare("INSERT INTO TrashBin (capacity, current_level, status) VALUES (?, ?, ?)");
    $stmt->bind_param("dds", $capacity, $current_level, $status);

    if ($stmt->execute()) {
        logAdminActivity('Bin Add', "Added a new bin with capacity $capacity kg, current level $current_level kg, and status $status");
        redirectWithMessage('bins.php', 'success', 'Bin added successfully!');
    } else {
        logAdminActivity('Bin Add Error', "Failed to add a new bin with capacity $capacity kg, current level $current_level kg, and status $status");
        redirectWithMessage('bins.php', 'error', 'Failed to add bin.');
    }
} else {
    redirectWithMessage('bins.php', 'error', 'Invalid request method.');
}
?>