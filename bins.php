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
</head>
<body class="dashboard-container">
    <!-- Sidebar -->
    <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1><?php echo SITE_NAME; ?></h1>
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
                            <span class="menu-text">Dashboard</span>
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
                    <li>
                     <a href="student_sessions.php">
                        <i class="bi bi-phone"></i>
                        <span>Student Sessions</span>
                        </a>
                    </li>
                    <li>
                     <a href="sessions.php">
                        <i class="bi bi-wifi"></i>
                        <span>Internet Sessions</span>
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
                    <span><?php echo htmlspecialchars($_SESSION['username']);?></span>
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
                    <div class="col-md-4 mb-4">
                       <div class="health-card">
                            <div>
                                <h4>Bin #<?php echo $bin['bin_id']; ?></h4>
                                <div class="d-flex justify-content-between mb-1">
                                   <span>Capacity</span>
                                    <span><?php
                                    $percentage = ($bin['capacity'] > 0) ? round(($bin['current_level'] / $bin['capacity']) * 100) : 0;
                                    echo $percentage;
                                    ?>%</span>
                                </div> 
                                 <div class="progress-bar">
                                  <div class="progress" style="width: <?php
                                        echo $percentage;
                                    ?>%"></div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $bin['current_level']; ?> / <?php echo $bin['capacity']; ?> kg
                                </small>
                                <div class="mt-2">
                                    <span class="status <?php 
                                        echo $bin['status'] == 'full' ? 'red' : 
                                             ($bin['status'] == 'partial' ? 'orange' : 'green'); 
                                    ?>">
                                        <?php echo ucfirst($bin['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div> 
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