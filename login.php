<?php
session_start();
require_once 'config.php'; 

// Encryption configuration
define('ENCRYPTION_KEY', 'your-secret-key-32-chars-long!!'); // Change this to a secure 32-character key
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('COOKIE_NAME', 'remember_admin_token');
define('COOKIE_EXPIRY', 30 * 24 * 60 * 60); // 30 days in seconds

/**
 * Encrypt data for cookie storage
 */
function encryptData($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt(json_encode($data), ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Decrypt data from cookie
 */
function decryptData($encryptedData) {
    $data = base64_decode($encryptedData);
    list($encrypted_data, $iv) = explode('::', $data, 2);
    $decrypted = openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return json_decode($decrypted, true);
}

/**
 * Set remember me cookie
 */
function setRememberMeCookie($admin_data) {
    $cookie_data = [
        'admin_id' => $admin_data['admin_id'],
        'username' => $admin_data['username'],
        'email' => $admin_data['email'],
        'password_hash' => $admin_data['password_hash'],
        'is_admin' => $admin_data['is_admin'],
        'created_at' => time()
    ];
    
    $encrypted_cookie = encryptData($cookie_data);
    
    setcookie(
        COOKIE_NAME,
        $encrypted_cookie,
        [
            'expires' => time() + COOKIE_EXPIRY,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

/**
 * Clear remember me cookie
 */
function clearRememberMeCookie() {
    setcookie(
        COOKIE_NAME,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php"); 
    exit();
}

$error = '';
$success = '';

// Check for success messages
if (isset($_GET['password_reset']) && $_GET['password_reset'] === 'success') {
    $success = "Password reset successful! Please log in with your new password.";
}

if (isset($_GET['registration']) && $_GET['registration'] === 'success') {
    $success = "Registration successful! Please log in.";
}

// Check for remember me cookie if session is empty
if (empty($_SESSION['admin_id']) && isset($_COOKIE[COOKIE_NAME])) {
    try {
        $cookie_data = decryptData($_COOKIE[COOKIE_NAME]);
        
        if ($cookie_data && isset($cookie_data['admin_id'])) {
            // Check if cookie is not too old (additional security check)
            if (isset($cookie_data['created_at']) && (time() - $cookie_data['created_at']) <= COOKIE_EXPIRY) {
                
                // Verify the user still exists and password hash matches
                $stmt = $conn->prepare("SELECT admin_id, username, email, password_hash, is_admin FROM Admin WHERE admin_id = ?");
                $stmt->bind_param("i", $cookie_data['admin_id']);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    
                    // Verify that the password hash in cookie matches database
                    if ($admin['password_hash'] === $cookie_data['password_hash']) {
                        // Set session variables
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['username'] = $admin['username']; 
                        $_SESSION['email'] = $admin['email']; 
                        $_SESSION['is_admin_session'] = $admin['is_admin'];

                        // Refresh the cookie with updated data and new timestamp
                        setRememberMeCookie($admin);
                        
                        $stmt->close();
                        header("Location: dashboard.php");
                        exit();
                    } else {
                        // Password hash doesn't match, clear the cookie
                        clearRememberMeCookie();
                    }
                } else {
                    // User no longer exists, clear the cookie
                    clearRememberMeCookie();
                }
                $stmt->close();
            } else {
                // Cookie is too old, clear it
                clearRememberMeCookie();
            }
        }
    } catch (Exception $e) {
        // Cookie decryption failed, clear it
        clearRememberMeCookie();
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, username, email, password_hash, is_admin FROM Admin WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            if (password_verify($password, $admin['password_hash'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['username']; 
                $_SESSION['email'] = $admin['email']; 
                $_SESSION['is_admin_session'] = $admin['is_admin'];

                // Set remember me cookie if requested
                if (isset($_POST['remember'])) {
                    setRememberMeCookie($admin);
                }
                
                $stmt->close();
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
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
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
                    placeholder="Enter your password" 
                    required
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
    
        </form>
    </div>

    <script>
        // Clear any error messages after 5 seconds
        setTimeout(function() {
            const errorMsg = document.querySelector('.error-message');
            if (errorMsg) {
                errorMsg.style.opacity = '0';
                setTimeout(() => errorMsg.remove(), 300);
            }
        }, 5000);
    </script>
</body>
</html>