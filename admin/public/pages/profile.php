<?php
require_once '../../private/config/config.php';
require_once '../../private/helpers/profile_backend.php';
checkAdminAuth();


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/sidebar.css">
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
                    <a href="devices.php">
                        <i class="bi bi-phone"></i>
                            <span>Devices</span>
                    </a>
                </li>
                <li>
                    <a href="activity_logs.php">
                        <i class="bi bi-clock-history"></i>
                        <span>Activity Logs</span>
                    </a>
                </li>
                <li class="active">
                    <a href="profile.php">
                        <i class="bi bi-person-circle"></i>
                        <span>My Account</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php">
                        <i class="bi bi-gear"></i> 
                        <span>Settings</span>
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
            <h2><i class="bi bi-person-circle"></i> My Account</h2>
        </div>

        <?php displayFlashMessage(); ?>

        <div class="profile-grid">
            <!-- Profile Information Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-person-fill"></i> Account Information</h3>
                    <button class="btn btn-primary" id="editProfileBtn">
                        <i class="bi bi-pencil"></i> Edit Info
                    </button>
                </div>

                <div class="card-body">
                    <div class="profile-info">
                        <div class="profile-item">
                            <label>Username:</label>
                            <span><?= htmlspecialchars($profile['username']) ?></span>
                        </div>
                        <div class="profile-item">
                            <label>Email:</label>
                            <span><?= htmlspecialchars($profile['email']) ?></span>
                        </div>
                        <div class="profile-item">
                            <label>Account Created:</label>
                            <span><?= date('F j, Y \a\t h:i A', strtotime($profile['created_at'])) ?></span>
                        </div>
                        <div class="profile-item">
                            <label>Account ID:</label>
                            <span><?= htmlspecialchars($profile['admin_id']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings Card -->
            <div class="settings-card">
                <div class="settings-header">
                    <div class="settings-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <h2>Security</h2>
                </div>
                <div class="settings-body">
                    <form method="POST">
                        <input type="hidden" name="change_password" value="1">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-segment"></div>
                                    <div class="strength-segment"></div>
                                    <div class="strength-segment"></div>
                                    <div class="strength-segment"></div>
                                </div>
                                <small class="help-text">Use 8+ characters with a mix of letters, numbers & symbols</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Account Information</h3>
                <button class="close edit-modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?= htmlspecialchars($profile['username']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary edit-modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="close password-modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="change_password" value="1">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="modal_current_password">Current Password</label>
                        <input type="password" id="modal_current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_new_password">New Password</label>
                        <input type="password" id="modal_new_password" name="new_password" required minlength="8">
                        <small class="form-text">Password must be at least 8 characters long</small>
                    </div>
                    <div class="form-group">
                        <label for="modal_confirm_password">Confirm New Password</label>
                        <input type="password" id="modal_confirm_password" name="confirm_password" required minlength="8">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary password-modal-cancel">Cancel</button>
                    <button type="submit" class="btn btn-warning">Change Password</button>
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

        // Sidebar toggle
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
            document.querySelector('.main-content').classList.toggle('expanded');
        });

        // Edit Profile Modal
        document.getElementById('editProfileBtn').addEventListener('click', function() {
            document.getElementById('editProfileModal').classList.add('show');
        });

        document.querySelectorAll('.edit-modal-close, .edit-modal-cancel').forEach(btn => {
            btn.addEventListener('click', function() {
                closeAllModals();
            });
        });

        // Change Password Modal
        document.getElementById('changePasswordBtn')?.addEventListener('click', function() {
            document.getElementById('changePasswordModal').classList.add('show');
        });

        document.querySelectorAll('.password-modal-close, .password-modal-cancel').forEach(btn => {
            btn.addEventListener('click', function() {
                closeAllModals();
            });
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const checks = {
                length: password.length >= 8,
                lower: /[a-z]/.test(password),
                upper: /[A-Z]/.test(password),
                number: /\d/.test(password),
                symbol: /[!@#$%^&*(),.?":{}|<>]/.test(password)
            };

            strength = Object.values(checks).filter(Boolean).length;
            return Math.min(4, strength);
        }

        function updateStrengthMeter(strength) {
            const segments = document.querySelectorAll('.strength-segment');
            const classes = ['weak', 'fair', 'good', 'strong'];
            
            segments.forEach((segment, index) => {
                segment.className = 'strength-segment';
                if (index < strength && strength > 0) {
                    segment.classList.add(classes[Math.min(strength - 1, 3)]);
                }
            });
        }

        // Password strength for main form
        document.getElementById('new_password').addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            updateStrengthMeter(strength);
        });

        // Password confirmation validation for main form
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Password confirmation validation for modal (if exists)
        document.getElementById('modal_confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('modal_new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Close modals when clicking outside
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