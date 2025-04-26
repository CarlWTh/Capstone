<?php
session_start();
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get current user data
$stmt = $conn->prepare("SELECT username, email, full_name, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);;
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update basic profile information
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Password change requested
            if (!empty($current_password)) {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $stmt->close();
                
                if (password_verify($current_password, $user_data['password'])) {
                    // Check if new passwords match
                    if ($new_password === $confirm_password) {
                        if (strlen($new_password) >= 8) {
                            // Hash new password and update
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $stmt->bind_param("si", $hashed_password, $user_id);
                            $stmt->execute();
                            $stmt->close();
                        } else {
                            throw new Exception("New password must be at least 8 characters long");
                        }
                    } else {
                        throw new Exception("New passwords do not match");
                    }
                } else {
                    throw new Exception("Current password is incorrect");
                }
            }
            
            // Commit transaction
            $conn->commit();
            $success_message = "Profile updated successfully!";
            
            // Refresh user data
            $stmt = $conn->prepare("SELECT username, email, full_name, phone FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
        } catch (Exception $e) {
            // Roll back transaction on error
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Bottle Recycling Admin</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <h1>Recycling Admin</h1>
                    <span class="logo-short"></span>
                </div>

                <button id="sidebar-toggle" class="sidebar-toggle">
                    <i class='bx bx-menu'></i>
                </button>
            </div>

            <nav>
                <ul>
                    <li>
                        <a href="dashboard.php">
                            <i class='bx bxs-dashboard'></i>
                            <span class="menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="transactions.php">
                            <i class='bx bx-transfer-alt'></i>
                            <span class="menu-text">Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="monitoring.php">
                            <i class='bx bx-line-chart'></i>
                            <span class="menu-text">System Monitoring</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class='bx bx-cog'></i>
                            <span class="menu-text">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php">
                            <i class='bx bxs-report'></i>
                            <span class="menu-text">Reports</span>
                        </a>
                    </li>
                    <li class="logout">
                        <a href="logout.php">
                            <i class='bx bx-log-out'></i>
                            <span class="menu-text">Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="main-header">
                <h2>Edit Profile</h2>
                <div class="user-info">
                    <div class="profile-dropdown">
                        <div class="dropdown-header" id="profileDropdownBtn">
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <img src="/api/placeholder/40/40" alt="Admin Avatar" class="avatar-img">
                            <i class='bx bx-chevron-down'></i>
                        </div>
                        <div class="dropdown-content" id="profileDropdown">
                            <a href="edit_profile.php"><i class='bx bx-user'></i> Edit Profile</a>
                            <a href="change_avatar.php"><i class='bx bx-image'></i> Change Avatar</a>
                            <a href="logout.php"><i class='bx bx-log-out'></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-container">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h2>Personal Information</h2>
                    <form method="post" action="edit_profile.php" class="profile-form">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            <div class="help-text">Username cannot be changed</div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <h2>Change Password</h2>
                        <div class="help-text">Leave password fields empty if you don't want to change your password</div>

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" onkeyup="checkPasswordStrength()">
                            
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-segment" id="segment1"></div>
                                    <div class="strength-segment" id="segment2"></div>
                                    <div class="strength-segment" id="segment3"></div>
                                    <div class="strength-segment" id="segment4"></div>
                                </div>
                                <span id="password-strength-text">Password strength</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" onkeyup="checkPasswordMatch()">
                            <div class="help-text" id="password-match-message"></div>
                        </div>

                        <div class="form-actions">
                            <a href="dashboard.php" class="btn-secondary">Cancel</a>
                            <button type="submit" class="btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Profile dropdown functionality
            const profileDropdownBtn = document.getElementById('profileDropdownBtn');
            const profileDropdown = document.getElementById('profileDropdown');
            
            if (profileDropdownBtn && profileDropdown) {
                profileDropdownBtn.addEventListener('click', function() {
                    profileDropdown.classList.toggle('show-dropdown');
                });
                
                // Close the dropdown if clicked outside
                window.addEventListener('click', function(event) {
                    if (!event.target.closest('.profile-dropdown')) {
                        profileDropdown.classList.remove('show-dropdown');
                    }
                });
            }
            
            // Sidebar toggle functionality
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const dashboardContainer = document.querySelector('.dashboard-container');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    dashboardContainer.classList.toggle('sidebar-collapsed');
                });
            }
        });

        // Password strength checker
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const segments = [
                document.getElementById('segment1'),
                document.getElementById('segment2'),
                document.getElementById('segment3'),
                document.getElementById('segment4')
            ];
            const strengthText = document.getElementById('password-strength-text');
            
            // Reset all segments
            segments.forEach(segment => {
                segment.style.backgroundColor = '#ddd';
            });
            
            if (password.length === 0) {
                strengthText.textContent = 'Password strength';
                return;
            }
            
            // Check password strength
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            // Update segments based on strength
            for (let i = 0; i < strength; i++) {
                segments[i].style.backgroundColor = [
                    '#e74c3c',  // Weak
                    '#f39c12',  // Fair
                    '#3498db',  // Good
                    '#2ecc71'   // Strong
                ][i];
            }
            
            // Update strength text
            const strengthLabels = ['Weak', 'Fair', 'Good', 'Strong'];
            strengthText.textContent = strengthLabels[strength - 1] || 'Too Weak';
        }
        
        // Check if passwords match
        function checkPasswordMatch() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchMessage = document.getElementById('password-match-message');
            
            if (confirmPassword.length === 0) {
                matchMessage.textContent = '';
                return;
            }
            
            if (newPassword === confirmPassword) {
                matchMessage.textContent = 'Passwords match!';
                matchMessage.style.color = '#2ecc71';
            } else {
                matchMessage.textContent = 'Passwords do not match!';
                matchMessage.style.color = '#e74c3c';
            }
        }
    </script>
</body>
</html>