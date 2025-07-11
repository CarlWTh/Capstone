<?php
require_once 'config.php';
checkAdminAuth();
// Get all trash bins
$bins = $conn->query("
    SELECT bin_id, capacity, current_level, status, sensor_config
    FROM TrashBin
    ORDER BY status DESC, current_level DESC
")->fetch_all(MYSQLI_ASSOC);

// Log activity
logAdminActivity('Bins Access', 'Viewed trash bins list');

// Handle bin update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bin'])) {
    $bin_id = (int)$_POST['bin_id'];
    $capacity = (float)$_POST['capacity'];
    $current_level = (float)$_POST['current_level'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE TrashBin SET capacity = ?, current_level = ?, status = ? WHERE bin_id = ?");
    $stmt->bind_param("ddsi", $capacity, $current_level, $status, $bin_id);

    if ($stmt->execute()) {
        logAdminActivity('Bin Update', "Updated bin #$bin_id");
        redirectWithMessage('bins.php', 'success', 'Bin updated successfully!');
    } else {
        redirectWithMessage('bins.php', 'error', 'Failed to update bin.');
    }
}

// Sample data for demonstration (replace with actual data later)
$emptying_logs = [
    ['bin_id' => 1, 'previous_level' => 45.5, 'emptied_by_name' => 'John Doe', 'emptied_at' => '2024-01-15 14:30:00'],
    ['bin_id' => 2, 'previous_level' => 38.2, 'emptied_by_name' => 'Jane Smith', 'emptied_at' => '2024-01-15 13:15:00'],
    ['bin_id' => 3, 'previous_level' => 52.8, 'emptied_by_name' => 'Mike Johnson', 'emptied_at' => '2024-01-15 11:45:00'],
    ['bin_id' => 1, 'previous_level' => 41.3, 'emptied_by_name' => 'Sarah Wilson', 'emptied_at' => '2024-01-14 16:20:00'],
    ['bin_id' => 2, 'previous_level' => 35.9, 'emptied_by_name' => 'Tom Brown', 'emptied_at' => '2024-01-14 15:10:00'],
];

// Function to get fill level color
function getFillLevelColor($percentage)
{
    if ($percentage >= 90) return 'danger';
    if ($percentage >= 70) return 'warning';
    if ($percentage >= 50) return 'info';
    return 'success';
}

// Function to format time ago
function timeAgo($datetime)
{
    if (!$datetime) return 'Never';
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time / 60) . ' minutes ago';
    if ($time < 86400) return floor($time / 3600) . ' hours ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    return date('M d, Y', strtotime($datetime));
}

// Sample last emptied data for each bin (replace with actual data later)
$last_emptied_data = [
    1 => '2024-01-15 14:30:00',
    2 => '2024-01-15 13:15:00',
    3 => '2024-01-15 11:45:00',
];
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
            background: gray;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .log-table table {
            width: 100%;
            margin: 0;
        }

        .log-table th {
            background: rgb(120, 157, 148);
            font-weight: 600;
            padding: 12px;
            border-bottom: 1px solidrgb(222, 230, 225);
        }

        .log-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f3f4;
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
            color: #6c757d;
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
            color: #6c757d;
        }
    </style>
</head>

<body class="dashboard-container">
    <!-- Sidebar -->
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
            <h2>Trash Bins</h2>
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
                    <?php foreach ($bins as $bin): ?>
                        <?php
                        $percentage = ($bin['capacity'] > 0) ? round(($bin['current_level'] / $bin['capacity']) * 100) : 0;
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

                                    <!-- Fill Level Indicator -->
                                    <div class="fill-level-indicator">
                                        <div class="fill-level-bar <?php echo $fillLevelColor; ?>"
                                            style="width: <?php echo $percentage; ?>%"></div>
                                    </div>

                                    <div class="level-details">
                                        <div class="level-percentage">
                                            Fill Level: <?php echo $percentage; ?>%
                                        </div>
                                        <div class="level-weight">
                                            <?php echo $bin['current_level']; ?> / <?php echo $bin['capacity']; ?> kg
                                        </div>
                                    </div>

                                    <div class="last-emptied">
                                        <i class="bi bi-clock"></i> Last emptied: <?php echo timeAgo($last_emptied_data[$bin['bin_id']] ?? null); ?>
                                    </div>
                                </div>
                                <div class="bin-actions">
                                    <button class="btn btn-sm btn-primary edit-bin"
                                        data-bin-id="<?php echo $bin['bin_id']; ?>"
                                        data-capacity="<?php echo $bin['capacity']; ?>"
                                        data-current-level="<?php echo $bin['current_level']; ?>"
                                        data-status="<?php echo $bin['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Last Emptied Log Section -->
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
                                                <strong>Bin #<?php echo $log['bin_id']; ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $log['previous_level']; ?> kg</span>
                                            </td>
                                            <td>
                                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($log['emptied_by_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y h:i A', strtotime($log['emptied_at'])); ?>
                                            </td>
                                            <td class="text-muted">
                                                <?php echo timeAgo($log['emptied_at']); ?>
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

    <!-- Add Bin Modal -->
    <div class="modal fade" id="addBinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Bin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="add_bin.php">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="capacity" class="form-label">Capacity (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="capacity" name="capacity" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="current_level" class="form-label">Current Level (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="current_level" name="current_level" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="empty">Empty</option>
                                <option value="partial">Partial</option>
                                <option value="full">Full</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
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

    <!-- Edit Bin Modal -->
    <div class="modal fade" id="editBinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
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
                            <input type="number" step="0.01" class="form-control" id="editCapacity" name="capacity" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editCurrentLevel" class="form-label">Current Level (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="editCurrentLevel" name="current_level" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="empty">Empty</option>
                                <option value="partial">Partial</option>
                                <option value="full">Full</option>
                                <option value="maintenance">Maintenance</option>
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

    <!-- Empty Bin Modal -->
    <div class="modal fade" id="emptyBinModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
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
                                <li>Set the current level to 0 kg</li>
                                <li>Change status to "empty"</li>
                                <li>Log this action with timestamp</li>
                            </ul>
                        </div>
                        <p>Current level: <strong><span id="emptyBinCurrentLevel"></span> kg</strong></p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Profile dropdown
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });

        // Edit bin modal
        document.querySelectorAll('.edit-bin').forEach(button => {
            button.addEventListener('click', function() {
                const binId = this.getAttribute('data-bin-id');
                const capacity = this.getAttribute('data-capacity');
                const currentLevel = this.getAttribute('data-current-level');
                const status = this.getAttribute('data-status');

                document.getElementById('editBinId').textContent = binId;
                document.getElementById('editBinIdInput').value = binId;
                document.getElementById('editCapacity').value = capacity;
                document.getElementById('editCurrentLevel').value = currentLevel;
                document.getElementById('editStatus').value = status;

                const modal = new bootstrap.Modal(document.getElementById('editBinModal'));
                modal.show();
            });
        });
    </script>
</body>

</html>