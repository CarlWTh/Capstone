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

// Get current avatar information
$stmt = $conn->prepare("SELECT avatar_path FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$current_avatar = $user['avatar_path'] ?? '/api/placeholder/200/200';
$stmt->close();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if file was uploaded
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        $file_type = $_FILES['avatar']['type'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Set allowed file extensions
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        // Validate file extension
        if (in_array($file_ext, $allowed_extensions)) {
            // Validate file size (max 2MB)
            if ($file_size <= 2097152) {
                // Create unique filename
                $new_file_name = uniqid('avatar_') . '.' . $file_ext;
                $upload_path = 'uploads/avatars/' . $new_file_name;
                
                // Create directory if it doesn't exist
                if (!is_dir('uploads/avatars/')) {
                    mkdir('uploads/avatars/', 0755, true);
                }
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Update database with new avatar path
                    $stmt = $conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
                    $stmt->bind_param("si", $upload_path, $user_id);
                    
                    if ($stmt->execute()) {
                        $success_message = "Avatar updated successfully!";
                        $current_avatar = $upload_path; // Update current avatar
                        
                        // Delete old avatar if it exists and is not the default
                        if (!empty($user['avatar_path']) && $user['avatar_path'] !== '/api/placeholder/200/200' && file_exists($user['avatar_path'])) {
                            unlink($user['avatar_path']);
                        }
                    } else {
                        $error_message = "Error updating avatar in database";
                    }
                    
                    $stmt->close();
                } else {
                    $error_message = "Error uploading file";
                }
            } else {
                $error_message = "File size too large. Maximum size is 2MB";
            }
        } else {
            $error_message = "Invalid file type. Allowed types: JPG, JPEG, PNG, GIF";
        }
    } elseif ($_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle upload errors
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        ];
        $error_message = $upload_errors[$_FILES['avatar']['error']] ?? "Unknown upload error";
    }
    
    // Handle avatar removal
    if (isset($_POST['remove_avatar']) && $_POST['remove_avatar'] == '1') {
        // Update database with default avatar path
        $default_avatar = '/api/placeholder/200/200';
        $stmt = $conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
        $stmt->bind_param("si", $default_avatar, $user_id);
        
        if ($stmt->execute()) {
            $success_message = "Avatar removed successfully!";
            
            // Delete old avatar if it exists and is not the default
            if (!empty($user['avatar_path']) && $user['avatar_path'] !== '/api/placeholder/200/200' && file_exists($user['avatar_path'])) {
                unlink($user['avatar_path']);
            }
            
            $current_avatar = $default_avatar;
        } else {
            $error_message = "Error removing avatar";
        }
        
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Avatar - Bottle Recycling Admin</title>
    <link rel="stylesheet" href="/css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .avatar-preview {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            display: block;
            margin: 0 auto 20px;
            border: 4px solid var(--primary-color);
        }
        
        .avatar-options {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .avatar-upload-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .avatar-upload-btn {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background-color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .avatar-upload-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .avatar-upload-container input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        
        .avatar-remove-btn {
            background-color: var(--red-danger);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .avatar-remove-btn:hover {
            background-color: #c0392b;
        }
        
        .avatar-container {
            text-align: center;
            padding: 30px 0;
        }
        
        .avatar-help-text {
            text-align: center;
            color: var(--gray-medium);
            margin-bottom: 30px;
        }
    </style>
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
                <h2>Change Avatar</h2>
                <div class="user-info">
                    <div class="profile-dropdown">
                        <div class="dropdown-header" id="profileDropdownBtn">
                            <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <img src="<?php echo htmlspecialchars($current_avatar); ?>" alt="Admin Avatar" class="avatar-img">
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
                    <h2>Personalize Your Profile</h2>
                    
                    <div class="avatar-container">
                        <img src="<?php echo htmlspecialchars($current_avatar); ?>" alt="Current Avatar" class="avatar-preview" id="avatarPreview">
                        
                        <p class="avatar-help-text">Upload a new avatar or remove your current one</p>
                        
                        <form method="post" enctype="multipart/form-data" action="change_avatar.php">
                            <div class="avatar-options">
                                <div class="avatar-upload-container">
                                    <label class="avatar-upload-btn">
                                        <i class='bx bx-upload'></i> Upload New Avatar
                                        <input type="file" name="avatar" id="avatarUpload" accept="image/*" onchange="previewAvatar(this)">
                                    </label>
                                </div>
                                
                                <button type="submit" name="remove_avatar" value="1" class="avatar-remove-btn">
                                    <i class='bx bx-trash'></i> Remove Avatar
                                </button>
                            </div>
                            
                            <div class="form-actions">
                                <a href="dashboard.php" class="btn-secondary">Cancel</a>
                                <button type="submit" class="btn-primary" id="saveButton" disabled>Save Changes</button>
                            </div>
                        </form>
                    </div>
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
        
        // Avatar preview functionality
        function previewAvatar(input) {
            const preview = document.getElementById('avatarPreview');
            const saveButton = document.getElementById('saveButton');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    saveButton.disabled = false;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>