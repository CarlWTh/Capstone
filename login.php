<?php
session_start();
require_once 'config.php'; // Ensure this path is correct

// If an admin is already logged in, redirect to the dashboard
if (isset($_SESSION['admin_id'])) {
    // It's good practice to also check if the username is set here
    // if (isset($_SESSION['username'])) { // Optional: add this for even more robustness
        header("Location: dashboard.php"); // Assuming 'dashboard.php' is your admin dashboard
        exit();
    // }
}

$error = '';
$success = '';

// Handle password reset success message
if (isset($_GET['password_reset']) && $_GET['password_reset'] === 'success') {
    $success = "Password reset successful! Please log in with your new password.";
}

// Handle "Remember Me" functionality for admin
if (empty($_SESSION['admin_id']) && isset($_COOKIE['remember_admin_token'])) {
    $token = $_COOKIE['remember_admin_token'];

    // Select from the 'Admin' table
    $stmt = $conn->prepare("SELECT admin_id, username, is_admin FROM Admin WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['username'] = $admin['username']; // Changed here: from admin_username to username
        $_SESSION['is_admin_session'] = $admin['is_admin']; // Set the admin status in session

        // Generate a new token for security
        $new_token = bin2hex(random_bytes(32));

        setcookie(
            'remember_admin_token',
            $new_token,
            [
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        // Update the new token in the 'Admin' table
        $update_stmt = $conn->prepare("UPDATE Admin SET remember_token = ? WHERE admin_id = ?");
        $update_stmt->bind_param("si", $new_token, $admin['admin_id']);
        $update_stmt->execute();
        $update_stmt->close();

        header("Location: dashboard.php");
        exit();
    }
    $stmt->close();
}

// Handle form submission for admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Select from the 'Admin' table
        $stmt = $conn->prepare("SELECT admin_id, username, password_hash, is_admin FROM Admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username']; // Changed here: from admin_username to username
                $_SESSION['is_admin_session'] = $admin['is_admin']; // Set the admin status in session

                // Handle "Remember Me"
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));

                    setcookie(
                        'remember_admin_token',
                        $token,
                        [
                            'path' => '/',
                            'domain' => $_SERVER['HTTP_HOST'],
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );

                    // Update the remember token in the 'Admin' table
                    $stmt = $conn->prepare("UPDATE Admin SET remember_token = ? WHERE admin_id = ?");
                    $stmt->bind_param("si", $token, $admin['admin_id']);
                    $stmt->execute();
                    $stmt->close();
                }

                // Redirect to the dashboard after successful login
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
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
    <title>Admin Login - Bottle Recycling System</title>
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST" action="login.php">
            <h2>Admin Login</h2>

            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <p><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registration']) && $_GET['registration'] === 'success'): ?>
                <div class="success-message">
                    <p>Registration successful! Please log in.</p>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password" required
                >
            </div>

            <div class="form-extras">
                <div class="remember-forgot">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Remember Me
                    </label>

                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>
            </div>

            <button type="submit" class="login-button">
                Login
            </button>
            <p style="text-align: center; margin-top: 10px;">
                Don't have an account? <a href="register.php">Register</a>
            </p>
        </form>
    </div>
</body>
</html>