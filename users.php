<?php
require_once 'config.php';
checkAdminAuth();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get users
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_pages = ceil($total_users / $per_page);

$users = $conn->query("
    SELECT id, username, email, phone, is_admin, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_admin'])) {
        $user_id = (int)$_POST['user_id'];
        $is_admin = (int)$_POST['is_admin'];
        
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_admin, $user_id);
        
        if ($stmt->execute()) {
            $action = $is_admin ? 'granted admin rights' : 'revoked admin rights';
            logAdminActivity('User Update', "$action for user #$user_id");
            redirectWithMessage('users.php', 'success', 'User updated successfully!');
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        if ($user_id == $_SESSION['user_id']) {
            redirectWithMessage('users.php', 'error', 'You cannot delete your own account!');
        }
        
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            logAdminActivity('User Delete', "Deleted user #$user_id");
            redirectWithMessage('users.php', 'success', 'User deleted successfully!');
        }
    }
}

logAdminActivity('Users Access', 'Viewed users list');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="dashboard-container">
<div class="sidebar" id="sidebar">
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
                    <li class="">
                        <a href="dashboard.php">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="">
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

                    <li>
                        <a href="student_sessions.php">
                            <i class="bi bi-phone"></i>
                            <span class="menu-text">Student Sessions</span>
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
                        <a href="bandwidth_control.php">
                            <i class="bi bi-speedometer2"></i>
                            <span>BANDWIDTH CONTROL</span>
                        </a>
                    </li>
                    <li>
                        <a href="time_and_rates.php">
                            <i class="bi bi-clock"></i>
                            <span>TIME AND RATES</span>
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
            <h2><i class="bi bi-people"></i> User Management</h2>
            <div class="profile-dropdown">
                <div class="dropdown-header">
                    <img src="https://via.placeholder.com/40" alt="Profile" class="avatar-img">
                    <span><?= htmlspecialchars($_SESSION['username']) ?></span>
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

                <h3>System Users</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus"></i> Add User
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
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="status green">You</span>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="is_admin" value="<?= $user['is_admin'] ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_admin" class="btn btn-sm <?= $user['is_admin'] ? 'btn-success' : 'btn-secondary' ?>">
                                                <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-danger delete-user" 
                                                data-user-id="<?= $user['id'] ?>" 
                                                data-username="<?= htmlspecialchars($user['username']) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <button class="btn btn-secondary" <?= $page <= 1 ? 'disabled' : '' ?>>
                        <i class="bi bi-chevron-left"></i> Prev
                    </button>
                    <span>Page <?= $page ?> of <?= $total_pages ?></span>
                    <button class="btn btn-secondary" <?= $page >= $total_pages ? 'disabled' : '' ?>>
                        Next <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <button class="close">&times;</button>
            </div>
            <form method="POST" action="add_user.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="is_admin">
                            <option value="0">User</option>
                            <option value="1">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Deletion</h3>
                <button class="close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="delete_user" value="1">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="modal-body">
                    <p>Delete user "<span id="deleteUserName"></span>"?</p>
                    <p class="text-danger">This action cannot be undone!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

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

        // Delete user modal
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteUserId').value = this.dataset.userId;
                document.getElementById('deleteUserName').textContent = this.dataset.username;
                document.getElementById('deleteModal').classList.add('show');
            });
        });

        // Close modals
        document.querySelectorAll('.close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal').classList.remove('show');
            });
        });
    </script>
</body>
</html>