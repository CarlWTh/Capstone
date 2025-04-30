<?php
require_once 'config.php';
checkAdminAuth();

// Handle minutes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_minutes'])) {
    $minutes = (int)$_POST['minutes_per_bottle'];
    $stmt = $conn->prepare("
        INSERT INTO SystemSettings (name, value) 
        VALUES ('minutes_per_bottle', ?)
        ON DUPLICATE KEY UPDATE value = ?
    ");
    $stmt->bind_param("ii", $minutes, $minutes);
    
    if ($stmt->execute()) {
        redirectWithMessage('bottles.php', 'success', 'Minutes updated!');
    }
}

// Get current bottles (simplified)
$bottles = $conn->query("SELECT * FROM Bottle")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bottle Types - <?php echo SITE_NAME; ?></title>
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
            <button class="sidebar-toggle">
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
                    <a href="deposits.php">
                        <i class="bi bi-recycle"></i>
                        <span class="menu-text">Bottle Deposits</span>
                    </a>
                </li>
                <li>
                    <a href="vouchers.php">
                        <i class="bi bi-ticket-perforated"></i>
                        <span class="menu-text">Vouchers</span>
                    </a>
                </li>
                <li>
                    <a href="bins.php">
                        <i class="bi bi-trash"></i>
                        <span class="menu-text">Trash Bins</span>
                    </a>
                </li>
                <li>
                        <a href="student_sessions.php">
                            <i class="bi bi-phone"></i>
                            <span class="menu-text">Student Sessions</span>
                        </a>
                    </li>
                <li>
                    <a href="sessions.php">
                        <i class="bi bi-wifi"></i>
                        <span class="menu-text">Internet Sessions</span>
                    </a>
                </li>
                <li class="active">
                    <a href="bottles.php">
                        <i class="bi bi-cup-straw"></i>
                        <span class="menu-text">Bottle Types</span>
                    </a>
                </li>
                <li>
                    <a href="users.php">
                        <i class="bi bi-people"></i>
                        <span class="menu-text">Users</span>
                    </a>
                </li>
                <li>
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span class="menu-text">Activity Logs</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span class="menu-text">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="main-header">
            <h2>Bottle Types</h2>
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
                <h3>Manage Bottle Types</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBottleModal">
                    <i class="bi bi-plus"></i> Add Bottle Type
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Weight Range (kg)</th>
                                <th>Base Minutes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bottles as $bottle): ?>
                            <tr>
                                <td><?php echo $bottle['bottle_id']; ?></td>
                                <td><?php echo htmlspecialchars($bottle['type']); ?></td>
                                <td><?php echo $bottle['accepted_weight_range_min']; ?> - <?php echo $bottle['accepted_weight_range_max']; ?></td>
                                <td><?php echo $bottle['base_minutes']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-bottle"
                                            data-bottle-id="<?php echo $bottle['bottle_id']; ?>"
                                            data-type="<?php echo htmlspecialchars($bottle['type']); ?>"
                                            data-min-weight="<?php echo $bottle['accepted_weight_range_min']; ?>"
                                            data-max-weight="<?php echo $bottle['accepted_weight_range_max']; ?>"
                                            data-base-minutes="<?php echo $bottle['base_minutes']; ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-bottle"
                                            data-bottle-id="<?php echo $bottle['bottle_id']; ?>"
                                            data-type="<?php echo htmlspecialchars($bottle['type']); ?>">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Bottle Modal -->
    <div class="modal fade" id="addBottleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Bottle Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="bottles.php">
                    <input type="hidden" name="add_bottle" value="1">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="type" class="form-label">Bottle Type</label>
                            <input type="text" class="form-control" id="type" name="type" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="min_weight" class="form-label">Minimum Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="min_weight" name="min_weight" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="max_weight" class="form-label">Maximum Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="max_weight" name="max_weight" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="base_minutes" class="form-label">Base Internet Minutes</label>
                            <input type="number" class="form-control" id="base_minutes" name="base_minutes" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Bottle Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Bottle Modal -->
    <div class="modal fade" id="editBottleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Bottle Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="bottles.php">
                    <input type="hidden" name="update_bottle" value="1">
                    <input type="hidden" name="bottle_id" id="editBottleId">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label for="editType" class="form-label">Bottle Type</label>
                            <input type="text" class="form-control" id="editType" name="type" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editMinWeight" class="form-label">Minimum Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="editMinWeight" name="min_weight" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editMaxWeight" class="form-label">Maximum Weight (kg)</label>
                            <input type="number" step="0.01" class="form-control" id="editMaxWeight" name="max_weight" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="editBaseMinutes" class="form-label">Base Internet Minutes</label>
                            <input type="number" class="form-control" id="editBaseMinutes" name="base_minutes" required>
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

    <!-- Delete Bottle Modal -->
    <div class="modal fade" id="deleteBottleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Bottle Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="bottles.php">
                    <input type="hidden" name="delete_bottle" value="1">
                    <input type="hidden" name="bottle_id" id="deleteBottleId">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the bottle type "<span id="deleteBottleType"></span>"?</p>
                        <p class="text-danger">Warning: This action cannot be undone!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
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

        // Edit bottle modal
        document.querySelectorAll('.edit-bottle').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('editBottleId').value = this.getAttribute('data-bottle-id');
                document.getElementById('editType').value = this.getAttribute('data-type');
                document.getElementById('editMinWeight').value = this.getAttribute('data-min-weight');
                document.getElementById('editMaxWeight').value = this.getAttribute('data-max-weight');
                document.getElementById('editBaseMinutes').value = this.getAttribute('data-base-minutes');
                
                const modal = new bootstrap.Modal(document.getElementById('editBottleModal'));
                modal.show();
            });
        });

        // Delete bottle modal
        document.querySelectorAll('.delete-bottle').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('deleteBottleId').value = this.getAttribute('data-bottle-id');
                document.getElementById('deleteBottleType').textContent = this.getAttribute('data-type');
                
                const modal = new bootstrap.Modal(document.getElementById('deleteBottleModal'));
                modal.show();
            });
        });
    </script>
</body>
</html>