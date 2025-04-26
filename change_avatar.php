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
    // Check if form has a cropped image data
    if (isset($_POST['cropped_data']) && !empty($_POST['cropped_data'])) {
        // Get the base64 image data
        $image_data = $_POST['cropped_data'];
        
        // Extract image content and decode base64
        list($type, $data) = explode(';', $image_data);
        list(, $data) = explode(',', $data);
        $image_data_decoded = base64_decode($data);
        
        // Get image extension from the mime type
        list(, $mime) = explode(':', $type);
        
        // Set file extension based on mime type
        $file_ext = 'jpg'; // Default
        if ($mime == 'image/png') {
            $file_ext = 'png';
        } elseif ($mime == 'image/gif') {
            $file_ext = 'gif';
        } elseif ($mime == 'image/jpeg' || $mime == 'image/jpg') {
            $file_ext = 'jpg';
        }
        
        // Create unique filename
        $new_file_name = uniqid('avatar_') . '.' . $file_ext;
        $upload_path = 'uploads/avatars/' . $new_file_name;
        
        // Create directory if it doesn't exist
        if (!is_dir('uploads/avatars/')) {
            mkdir('uploads/avatars/', 0755, true);
        }
        
        // Save the image file
        if (file_put_contents($upload_path, $image_data_decoded)) {
            // Update database with new avatar path
            $stmt = $conn->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->bind_param("si", $upload_path, $user_id);
            
            if ($stmt->execute()) {
                $success_message = "Avatar updated successfully!";
                
                // Delete old avatar if it exists and is not the default
                if (!empty($user['avatar_path']) && $user['avatar_path'] !== '/api/placeholder/200/200' && file_exists($user['avatar_path'])) {
                    unlink($user['avatar_path']);
                }
                
                $current_avatar = $upload_path; // Update current avatar
            } else {
                $error_message = "Error updating avatar in database";
            }
            
            $stmt->close();
        } else {
            $error_message = "Error saving image file";
        }
    }
    
    // Handle regular file upload - keeping this for backward compatibility
    elseif (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
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
    <!-- Include Cropper.js library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
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
        
       
/* Modal for image cropping */
.cropper-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.85); /* Darker background for better contrast */
    z-index: 1000;
    overflow: auto;
    justify-content: center;
    align-items: center;
}

.cropper-modal.active {
    display: flex;
}

.cropper-container {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow: auto;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5); /* Add shadow for depth */
}

.cropper-title {
    margin-top: 0;
    text-align: center;
    color: var(--primary-color);
    margin-bottom: 15px;
}

.img-container {
    max-height: 400px;
    margin-bottom: 20px;
    background-color: #f5f5f5; /* Light background for the image container */
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden; /* Keep the cropper elements contained */
}

.img-container img {
    display: block;
    max-width: 100%;
}

.cropper-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.btn-crop {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s;
}

.btn-crop:hover {
    background-color: var(--hover-color, #0056b3);
}

.btn-cancel {
    background-color: #95a5a6;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.btn-cancel:hover {
    background-color: #7f8c8d;
}

/* Improve cropper visibility */
.cropper-view-box,
.cropper-face {
    border-radius: 50%; /* Make the crop area circular to match avatar shape */
}

.cropper-modal .cropper-line, 
.cropper-modal .cropper-point {
    opacity: 1; /* Make cropper controls more visible */
}

.cropper-crop-box {
    box-shadow: 0 0 0 1px #39f; /* Add border around crop box */
}

/* Improve the user experience when the modal is open */
body.modal-open {
    overflow: hidden; /* Prevent scrolling when modal is open */
}
        
        .btn-crop {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #95a5a6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
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
                        
                        <form method="post" id="avatarForm" enctype="multipart/form-data" action="change_avatar.php">
                            <input type="hidden" name="cropped_data" id="croppedImageData">
                            
                            <div class="avatar-options">
                                <div class="avatar-upload-container">
                                    <label class="avatar-upload-btn">
                                        <i class='bx bx-upload'></i> Upload New Avatar
                                        <input type="file" name="avatar" id="avatarUpload" accept="image/*">
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
    
    <!-- Image Cropper Modal -->
    <div class="cropper-modal" id="cropperModal">
        <div class="cropper-container">
            <h2 class="cropper-title">Adjust Your Avatar</h2>
            <div class="img-container">
                <img id="cropperImage" src="">
            </div>
            <div class="cropper-buttons">
                <button class="btn-cancel" id="cancelCrop">Cancel</button>
                <button class="btn-crop" id="cropImage">Crop & Save</button>
            </div>
        </div>
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
            
            // Image cropper variables
            let cropper;
            const avatarUpload = document.getElementById('avatarUpload');
            const cropperModal = document.getElementById('cropperModal');
            const cropperImage = document.getElementById('cropperImage');
            const cancelCropBtn = document.getElementById('cancelCrop');
            const cropImageBtn = document.getElementById('cropImage');
            const croppedImageData = document.getElementById('croppedImageData');
            const saveButton = document.getElementById('saveButton');
            const avatarForm = document.getElementById('avatarForm');
            
            // When a file is selected
            avatarUpload.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    
                    // Check file size
                    if (file.size > 2097152) { // 2MB
                        alert("File size too large. Maximum size is 2MB");
                        return;
                    }
                    
                    // Check file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        alert("Invalid file type. Allowed types: JPG, JPEG, PNG, GIF");
                        return;
                    }
                    
                    // Create a FileReader to read the image
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Set the source of the cropper image
                        cropperImage.src = e.target.result;
                        
                        // Show the cropper modal
                        cropperModal.classList.add('active');
                        
                        // Initialize cropper after setting the image source
                        setTimeout(() => {
                            if (cropper) {
                                cropper.destroy();
                            }
                            
                            cropper = new Cropper(cropperImage, {
                                aspectRatio: 1, // 1:1 aspect ratio for circular avatar
                                viewMode: 1, // restrict the crop box to not exceed the size of the canvas
                                guides: true,
                                center: true,
                                dragMode: 'move',
                                scalable: true,
                                zoomable: true,
                                zoomOnTouch: true,
                                zoomOnWheel: true,
                                wheelZoomRatio: 0.1
                            });
                        }, 100);
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
            
            // Cancel crop button
            cancelCropBtn.addEventListener('click', function() {
                cropperModal.classList.remove('active');
                avatarUpload.value = ''; // Clear the file input
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            });
            
            // Crop and preview button
            cropImageBtn.addEventListener('click', function() {
                if (cropper) {
                    // Get cropped canvas data
                    const canvas = cropper.getCroppedCanvas({
                        width: 200,
                        height: 200,
                        imageSmoothingEnabled: true,
                        imageSmoothingQuality: 'high'
                    });
                    
                    // Convert canvas to data URL
                    const dataURL = canvas.toDataURL('image/jpeg', 0.9);
                    
                    // Set preview image
                    document.getElementById('avatarPreview').src = dataURL;
                    
                    // Store cropped image data in hidden input
                    croppedImageData.value = dataURL;
                    
                    // Enable save button
                    saveButton.disabled = false;
                    
                    // Close modal
                    cropperModal.classList.remove('active');
                    
                    // Destroy cropper
                    cropper.destroy();
                    cropper = null;
                }
            });
        });
    </script>
</body>
</html>