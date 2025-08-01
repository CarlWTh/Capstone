<?php
require_once 'config.php';
checkAdminAuth();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$total_admins_query = $conn->query("SELECT COUNT(*) FROM Admin");
$total_admins = $total_admins_query->fetch_row()[0];
$total_pages = ceil($total_admins / $per_page);
$stmt = $conn->prepare("
    SELECT admin_id, username, email, created_at, is_admin
    FROM Admin
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$admins = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_admin'])) {
        $admin_id = (int)$_POST['admin_id'];
        $current_is_admin_status = (int)$_POST['current_is_admin_status']; 
        $new_is_admin_status = $current_is_admin_status ? 0 : 1; 

        if ($admin_id == $_SESSION['admin_id'] && $new_is_admin_status == 0) {
            redirectWithMessage('users.php', 'error', 'You cannot revoke your own admin rights!');
        } else {
            $stmt = $conn->prepare("UPDATE Admin SET is_admin = ? WHERE admin_id = ?");
            $stmt->bind_param("ii", $new_is_admin_status, $admin_id);

            if ($stmt->execute()) {
                $action = $new_is_admin_status ? 'granted admin rights to' : 'revoked admin rights from';
                logAdminActivity('Admin Update', "$action admin #$admin_id");
                redirectWithMessage('users.php', 'success', 'Admin updated successfully!');
            } else {
                redirectWithMessage('users.php', 'error', 'Failed to update admin: ' . $conn->error);
            }
        }
    } elseif (isset($_POST['delete_admin'])) {
        $admin_id = (int)$_POST['admin_id'];

        if ($admin_id == $_SESSION['admin_id']) {
            redirectWithMessage('users.php', 'error', 'You cannot delete your own account!');
        }

        $stmt = $conn->prepare("DELETE FROM Admin WHERE admin_id = ?");
        $stmt->bind_param("i", $admin_id);

        if ($stmt->execute()) {
            logAdminActivity('Admin Delete', "Deleted admin #$admin_id");
            redirectWithMessage('users.php', 'success', 'Admin deleted successfully!');
        } else {
            redirectWithMessage('users.php', 'error', 'Failed to delete admin: ' . $conn->error);
        }
    }
    elseif (isset($_POST['add_admin'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($username) || empty($email) || empty($password)) {
            redirectWithMessage('users.php', 'error', 'All fields are required to add a new admin.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('users.php', 'error', 'Invalid email format.');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $is_admin = 1;
            $stmt = $conn->prepare("INSERT INTO Admin (username, email, password, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $username, $email, $hashed_password, $is_admin);

            if ($stmt->execute()) {
                logAdminActivity('Admin Create', "Added new admin: $username");
                redirectWithMessage('users.php', 'success', 'New admin added successfully!');
            } else {
                if ($conn->errno == 1062) {
                    redirectWithMessage('users.php', 'error', 'Username or email already exists.');
                } else {
                    redirectWithMessage('users.php', 'error', 'Error adding admin: ' . $conn->error);
                }
            }
        }
    }
}

logAdminActivity('Admin Access', 'Viewed admins list');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
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
                <li>
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
                <li class="active">
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
            <h2><i class="bi bi-people"></i> Admin Management</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span> 
                    <i class="bi bi-chevron-down"></i>
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
                <h3>System Users</h3>
                <button class="btn btn-primary" id="addAdminBtn">
                    <i class="bi bi-plus"></i> Add Admin
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="transaction-logs">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Admin Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($admins)): ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($admin['admin_id']) ?></td>
                                        <td><?= htmlspecialchars($admin['username']) ?></td>
                                        <td><?= htmlspecialchars($admin['email']) ?></td>
                                        <td>
                                            <?php if ($admin['admin_id'] == $_SESSION['admin_id']): ?>
                                                <span class="status green">You (Admin)</span>
                                            <?php else: ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['admin_id']) ?>">
                                                    <input type="hidden" name="current_is_admin_status" value="<?= htmlspecialchars($admin['is_admin']) ?>">
                                                    <button type="submit" name="toggle_admin" class="btn btn-sm <?= $admin['is_admin'] ? 'btn-success' : 'btn-secondary' ?>">
                                                        <?= $admin['is_admin'] ? 'Revoke Admin' : 'Grant Admin' ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M j, Y h:i A', strtotime($admin['created_at'])) ?></td>
                                        <td>
                                            <?php if ($admin['admin_id'] != $_SESSION['admin_id']): ?>
                                                <button class="btn btn-sm btn-danger delete-admin"
                                                    data-admin-id="<?= htmlspecialchars($admin['admin_id']) ?>"
                                                    data-username="<?= htmlspecialchars($admin['username']) ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No admin users found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <a href="?page=<?= $page - 1 ?>" class="btn btn-secondary <?= $page <= 1 ? 'disabled' : '' ?>">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>
                    <span>Page <?= $page ?> of <?= $total_pages ?></span>
                    <a href="?page=<?= $page + 1 ?>" class="btn btn-secondary <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="addAdminModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Admin</h3>
                <button class="close add-modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="add_admin" value="1">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary add-modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button class="close delete-modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="delete_admin" value="1">
                <input type="hidden" name="admin_id" id="deleteAdminId">
                <div class="modal-body">
                    <p>Are you sure you want to delete admin "<span id="deleteAdminName"></span>"?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary delete-modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeAllModals() {
            document.querySelectorAll('.modal').forEach(modal => {
                modal.classList.remove('show');
            });
        }
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });
        document.querySelector('.dropdown-header').addEventListener('click', function() {
            document.querySelector('.dropdown-content').classList.toggle('show-dropdown');
        });
        document.getElementById('addAdminBtn').addEventListener('click', function() {
            document.getElementById('addAdminModal').classList.add('show');
        });
        document.querySelectorAll('.add-modal-close, .add-modal-cancel').forEach(btn => {
            btn.addEventListener('click', function() {
                closeAllModals();
            });
        });
        document.querySelectorAll('.delete-admin').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteAdminId').value = this.dataset.adminId;
                document.getElementById('deleteAdminName').textContent = this.dataset.username;
                document.getElementById('deleteModal').classList.add('show');
            });
        });
        document.querySelectorAll('.delete-modal-close, .delete-modal-cancel').forEach(btn => {
            btn.addEventListener('click', function() {
                closeAllModals();
            });
        });
        window.addEventListener('click', function(event) {
            document.querySelectorAll('.modal').forEach(modal => {
                if (event.target == modal) {
                    modal.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>