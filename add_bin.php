<?php
require_once 'config.php';
checkAdminAuth();

$bins = $conn->query("
    SELECT trashbin_id AS bin_id, capacity, fill_level_percent AS current_level, status, janitor_id, last_emptied_at
    FROM Trashbin
    ORDER BY status DESC, fill_level_percent DESC
")->fetch_all(MYSQLI_ASSOC);

$janitors = [];
$janitor_query = $conn->query("SELECT janitor_id, contact_number FROM Janitor"); 
if ($janitor_query) {
    while ($row = $janitor_query->fetch_assoc()) {
        $janitors[$row['janitor_id']] = $row['contact_number'];
    }
}

$emptying_logs = [];
$log_query = $conn->query("
    SELECT tb.trashbin_id AS bin_id, tb.fill_level_percent AS previous_level, j.contact_number, tb.last_emptied_at
    FROM Trashbin tb
    JOIN Janitor j ON tb.janitor_id = j.janitor_id
    WHERE tb.last_emptied_at IS NOT NULL
    ORDER BY tb.last_emptied_at DESC
");
if ($log_query) {
    while ($row = $log_query->fetch_assoc()) {
        $emptying_logs[] = $row;
    }
}

function janitorExists($conn, $janitor_id) {
    $count = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Janitor WHERE janitor_id = ?");
    $stmt->bind_param("i", $janitor_id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bin'])) {
    $bin_id = (int)$_POST['bin_id'];
    $capacity = (float)$_POST['capacity'];
    $current_level = (float)$_POST['current_level']; 
    $status = $_POST['status'];
    $janitor_id = (int)$_POST['janitor_id'];

    if ($capacity <= 0) {
        redirectWithMessage('bins.php', 'error', 'Capacity must be a positive value.');
    } elseif ($current_level < 0 || $current_level > 100) { 
        redirectWithMessage('bins.php', 'error', 'Fill level percentage must be between 0 and 100.');
    } elseif (!in_array($status, ['empty', 'partial', 'full'])) {
        redirectWithMessage('bins.php', 'error', 'Invalid bin status.');
    } elseif (!janitorExists($conn, $janitor_id)) {
        redirectWithMessage('bins.php', 'error', 'Assigned Janitor does not exist.');
    }
    else {
        $stmt = $conn->prepare("UPDATE Trashbin SET capacity = ?, fill_level_percent = ?, status = ?, janitor_id = ? WHERE trashbin_id = ?");
        $stmt->bind_param("ddsii", $capacity, $current_level, $status, $janitor_id, $bin_id);
        if ($stmt->execute()) {
            logAdminActivity('Bin Update', "Updated bin #$bin_id (Capacity: $capacity, Level: $current_level%, Status: $status, Janitor: $janitor_id)");
            redirectWithMessage('bins.php', 'success', 'Bin updated successfully!');
        } else {
            redirectWithMessage('bins.php', 'error', 'Failed to update bin: ' . $stmt->error);
        }
        $stmt->close();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bin'])) {
    $janitor_id = (int)$_POST['janitor_id'];

    if ($janitor_id <= 0) {
        redirectWithMessage('bins.php', 'error', 'Invalid janitor ID.');
    } elseif (!janitorExists($conn, $janitor_id)) {
        redirectWithMessage('bins.php', 'error', 'Assigned Janitor does not exist.');
    }
    else {
        $default_capacity = 100.00;
        $default_fill_level = 0.00;
        $default_status = 'empty';

        $stmt = $conn->prepare("INSERT INTO Trashbin (janitor_id, capacity, fill_level_percent, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $janitor_id, $default_capacity, $default_fill_level, $default_status);

        if ($stmt->execute()) {
            $new_bin_id = $conn->insert_id;
            logAdminActivity('Bin Added', "Added new bin #$new_bin_id (Janitor: $janitor_id)");
            redirectWithMessage('bins.php', 'success', 'New bin added successfully!');
        } else {
            redirectWithMessage('bins.php', 'error', 'Failed to add new bin: ' . $stmt->error);
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_janitor'])) {
    $contact = trim($_POST['janitor_contact']);
    if ($contact === '') {
        redirectWithMessage('bins.php', 'error', 'Contact number is required.');
    } else {
        $stmt_check = $conn->prepare("SELECT COUNT(*) FROM Janitor WHERE contact_number = ?");
        $stmt_check->bind_param("s", $contact);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            redirectWithMessage('bins.php', 'error', 'Janitor with this contact number already exists.');
        } else {
            $stmt = $conn->prepare("INSERT INTO Janitor (contact_number) VALUES (?)");
            $stmt->bind_param("s", $contact);
            if ($stmt->execute()) {
                logAdminActivity('Janitor Added', "Added janitor with contact: $contact");
                redirectWithMessage('bins.php', 'success', 'New janitor added successfully!');
            } else {
                redirectWithMessage('bins.php', 'error', 'Failed to add janitor: ' . $stmt->error);
            }
            $stmt->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empty_bin'])) {
    $bin_id = (int)$_POST['bin_id'];
    $previous_level = (float)$_POST['previous_level']; 

    if (isset($_SESSION['admin_id'])) {
        $admin_id = $_SESSION['admin_id'];
        $stmt = $conn->prepare("UPDATE Trashbin SET fill_level_percent = 0, status = 'empty', last_emptied_at = NOW() WHERE trashbin_id = ?");
        $stmt->bind_param("i", $bin_id);

        if ($stmt->execute()) {
            logAdminActivity('Bin Emptied', "Bin #$bin_id emptied. Previous level: $previous_level%.");
            redirectWithMessage('bins.php', 'success', "Bin #$bin_id emptied successfully!");
        } else {
            redirectWithMessage('bins.php', 'error', "Failed to empty bin #$bin_id: " . $stmt->error);
        }
        $stmt->close();
    } else {
        redirectWithMessage('bins.php', 'error', 'Admin not authenticated to empty bin.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bin'])) {
    $bin_id = (int)$_POST['bin_id'];
    if ($bin_id > 0) {
        $stmt = $conn->prepare("DELETE FROM Trashbin WHERE trashbin_id = ?");
        $stmt->bind_param("i", $bin_id);
        if ($stmt->execute()) {
            logAdminActivity('Bin Deleted', "Deleted bin #$bin_id");
            redirectWithMessage('bins.php', 'success', "Bin #$bin_id deleted successfully!");
        } else {
            redirectWithMessage('bins.php', 'error', "Failed to delete bin #$bin_id: " . $stmt->error);
        }
        $stmt->close();
    } else {
        redirectWithMessage('bins.php', 'error', 'Invalid bin ID for deletion.');
    }
}   

logAdminActivity('Bins Access', 'Viewed trash bins list');

function getFillLevelColor($percentage)
{
    if ($percentage >= 90) return 'danger';
    if ($percentage >= 60) return 'warning';
    if ($percentage >= 30) return 'info';
    return 'success';
}

function timeAgo($datetime)
{
    if (!$datetime) return 'Never';
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' days ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    return date('M d, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trash Bins - <?php echo SITE_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .fill-level-indicator {
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 8px 0;
        }

        .fill-level-bar {
            height: 100%;
            transition: width 0.3s ease;
        }

        .fill-level-bar.success {
            background: #28a745;
        }

        .fill-level-bar.info {
            background: #17a2b8;
        }

        .fill-level-bar.warning {
            background: #ffc107;
        }

        .fill-level-bar.danger {
            background: #dc3545;
        }

        .emptying-log {
            margin-top: 30px;
        }

        .log-table {
            background: var(--card-bg);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow);

        }

        .log-table table {
            width: 100%;
            margin: 0;
        }

        .log-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .log-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .log-table tr:last-child td {
            border-bottom: none;
        }

        .bin-actions {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .btn-empty {
            background: #28a745;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .btn-empty:hover {
            background: #218838;
        }

        .last-emptied {
            font-size: 12px;
            color: var(--light-text);
            margin-top: 5px;
        }

        .level-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .level-percentage {
            font-size: 14px;
            font-weight: 600;
        }

        .level-weight {
            font-size: 12px;
            color: var(--light-text);
        }
        .modal-dialog-centered {
            display: flex;
            align-items: center;
            min-height: calc(100vh - 1rem);
            justify-content: center;
        }
        .modal-content {
            margin: auto;
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
                <li class="active">
                    <a href="bins.php">
                        <i class="bi bi-trash"></i>
                        <span>Trash Bins</span>
                    </a>
                </li>
                <li class="">
                    <a href="sessions.php">
                        <i class="bi bi-wifi"></i>
                        <span>Network Monitoring</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Admins</span>
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

    <div class="main-content">
        <div class="main-header">
            <h2><i class="bi bi-trash"></i> Trash Bins</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                   
                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span> <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-content">
                    
                    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </div>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="card">
            <div class="card-header">
                <h3>Bin Status Overview</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBinModal">
                    <i class="bi bi-plus"></i> Add New Bin
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (empty($bins)): ?>
                    <div class="col-12 text-center py-4 text-muted">
                        <i class="bi bi-info-circle"></i> No trash bins configured yet.
                    </div>
                    <?php else: ?>
                    <?php foreach ($bins as $bin): ?>
                    <?php
                                $percentage = $bin['current_level']; 
                                $fillLevelColor = getFillLevelColor($percentage);
                                ?>
                    <div class="col-md-4 mb-4">
                        <div class="health-card">
                            <div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4>Bin #<?php echo $bin['bin_id']; ?></h4>
                                    <span class="status <?php
                                                                echo $bin['status'] == 'full' ? 'red' : ($bin['status'] == 'partial' ? 'orange' : 'green');
                                                                ?>">
                                        <?php echo ucfirst($bin['status']); ?>
                                    </span>
                                </div>

                                <div class="fill-level-indicator">
                                    <div class="fill-level-bar <?php echo $fillLevelColor; ?>"
                                        style="width: <?php echo $percentage; ?>%"></div>
                                </div>

                                <div class="level-details">
                                    <div class="level-percentage">
                                        Fill Level: <?php echo $percentage; ?>%
                                    </div>
                                    <div class="level-weight">
                                        <?php echo number_format($bin['capacity'] * ($bin['current_level'] / 100), 2); ?> / <?php echo $bin['capacity']; ?> kg
                                    </div>
                                </div>

                                <div class="last-emptied">
                                    <i class="bi bi-clock"></i> Last emptied:
                                    <?php echo timeAgo($bin['last_emptied_at'] ?? null); ?>
                                </div>
                                <div class="janitor-assigned">
                                    <i class="bi bi-person"></i> Assigned Janitor:
                                    <strong><?= htmlspecialchars($janitors[$bin['janitor_id']] ?? 'N/A') ?></strong>
                                </div>
                            </div>
                            <div class="bin-actions">
                                <button class="btn btn-sm btn-success btn-empty" data-bs-toggle="modal"
                                    data-bs-target="#emptyBinModal" data-bin-id="<?php echo $bin['bin_id']; ?>"
                                    data-current-level="<?php echo $bin['current_level']; ?>">
                                    <i class="bi bi-trash"></i> Empty
                                </button>
                                <button class="btn btn-sm btn-primary edit-bin"
                                    data-bin-id="<?php echo $bin['bin_id']; ?>"
                                    data-capacity="<?php echo $bin['capacity']; ?>"
                                    data-current-level="<?php echo $bin['current_level']; ?>"
                                    data-status="<?php echo $bin['status']; ?>"
                                    data-janitor-id="<?php echo $bin['janitor_id']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-danger delete-bin" data-bs-toggle="modal"
                                    data-bs-target="#deleteBinModal"
                                    data-bin-id="<?php echo $bin['bin_id']; ?>">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="emptying-log">
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-clock-history"></i> Last Emptied Log</h3>
                </div>
                <div class="card-body">
                    <div class="log-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bin ID</th>
                                    <th>Previous Level</th>
                                    <th>Emptied By</th>
                                    <th>Date & Time</th>
                                    <th>Time Ago</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($emptying_logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No emptying records found</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($emptying_logs as $log): ?>
                                <tr>
                                    <td>
                                        <strong>Bin #<?php echo htmlspecialchars($log['bin_id']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($log['previous_level']); ?>%</span>
                                    </td>
                                    <td>
                                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($log['contact_number']); ?>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y h:i A', strtotime($log['last_emptied_at'])); ?>
                                    </td>
                                    <td class="text-muted">
                                        <?php echo timeAgo($log['last_emptied_at']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addBinModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Bin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="bins.php">
                <input type="hidden" name="add_bin" value="1">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="janitor_id" class="form-label">Assign Janitor</label>
                        <div class="input-group">
                            <select class="form-select" id="janitor_id" name="janitor_id" required>
                                <?php if (empty($janitors)): ?>
                                <option value="">No Janitors Available</option>
                                <?php else: ?>
                                <option value="">Select a Janitor</option>
                                <?php foreach ($janitors as $id => $name): ?>
                                <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#addJanitorModal" style="margin-top: 10px;">
                                + Add New Number
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Bin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addJanitorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="bins.php">
                <input type="hidden" name="add_janitor" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Janitor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="janitor_contact" class="form-label">Janitor Contact Number</label>
                        <input type="text" class="form-control" id="janitor_contact" name="janitor_contact" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Janitor</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <div class="modal fade" id="editBinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bin #<span id="editBinId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="bins.php">
                    <input type="hidden" name="bin_id" id="editBinIdInput">
                    <input type="hidden" name="update_bin" value="1">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="editCapacity" class="form-label">Capacity (kg)</label>
                            <input type="number" class="form-control" id="editCapacity" name="capacity" min="1" step="0.01" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editCurrentLevel" class="form-label">Fill Level (%)</label>
                            <input type="number" class="form-control" id="editCurrentLevel" name="current_level" min="0" max="100" step="0.01" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="empty">Empty</option>
                                <option value="partial">Partial</option>
                                <option value="full">Full</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editJanitorId" class="form-label">Assign Janitor</label>
                            <select class="form-select" id="editJanitorId" name="janitor_id" required>
                                <?php if (empty($janitors)): ?>
                                <option value="">No Janitors Available</option>
                                <?php else: ?>
                                <?php foreach ($janitors as $id => $name): ?>
                                <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="emptyBinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Empty Bin #<span id="emptyBinId"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="bins.php">
                    <input type="hidden" name="bin_id" id="emptyBinIdInput">
                    <input type="hidden" name="previous_level" id="emptyBinPreviousLevel">
                    <input type="hidden" name="empty_bin" value="1">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Are you sure you want to empty this bin? This action will:
                            <ul>
                                <li>Set the current level to 0%</li>
                                <li>Change status to "empty"</li>
                                <li>Update "Last emptied" timestamp</li>
                            </ul>
                        </div>
                        <p>Current level: <strong><span id="emptyBinCurrentLevel"></span>%</strong></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-trash"></i> Empty Bin
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteBinModal" tabindex="-1" aria-labelledby="deleteBinModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="deleteBinForm" method="POST" action="bins.php">
            <input type="hidden" name="delete_bin" value="1">
            <input type="hidden" name="bin_id" id="deleteBinIdInput">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteBinModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this bin? This action cannot be undone.
                    <br><br>
                    <strong>Bin ID:</strong> <span id="modalBinId"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBinBtn">Delete Bin</button>
                </div>
            </div>
        </form>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        document.querySelectorAll('.edit-bin').forEach(button => {
            button.addEventListener('click', function() {
                const binId = this.getAttribute('data-bin-id');
                const capacity = this.getAttribute('data-capacity');
                const currentLevel = this.getAttribute('data-current-level'); // This is percentage
                const status = this.getAttribute('data-status');
                const janitorId = this.getAttribute('data-janitor-id');

                document.getElementById('editBinId').textContent = binId;
                document.getElementById('editBinIdInput').value = binId;
                document.getElementById('editCapacity').value = capacity;
                document.getElementById('editCurrentLevel').value = currentLevel;
                document.getElementById('editStatus').value = status;
                document.getElementById('editJanitorId').value = janitorId;

                const modal = new bootstrap.Modal(document.getElementById('editBinModal'));
                modal.show();
            });
        });

        document.querySelectorAll('.btn-empty').forEach(button => {
            button.addEventListener('click', function() {
                const binId = this.getAttribute('data-bin-id');
                const currentLevel = this.getAttribute('data-current-level'); 

                document.getElementById('emptyBinId').textContent = binId;
                document.getElementById('emptyBinIdInput').value = binId;
                document.getElementById('emptyBinCurrentLevel').textContent = currentLevel;
                document.getElementById('emptyBinPreviousLevel').value = currentLevel;

                const modal = new bootstrap.Modal(document.getElementById('emptyBinModal'));
                modal.show();
            });
        });

    let selectedBinId = null;
    document.querySelectorAll('.delete-bin').forEach(button => {
        button.addEventListener('click', function() {
            selectedBinId = this.getAttribute('data-bin-id');
            document.getElementById('modalBinId').textContent = selectedBinId;
            document.getElementById('deleteBinIdInput').value = selectedBinId;
        });
    });

    document.getElementById('confirmDeleteBinBtn').addEventListener('click', function() {
        document.getElementById('deleteBinForm').submit();
    });

    </script>
</body>

</html>