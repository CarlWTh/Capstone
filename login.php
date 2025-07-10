<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['password_reset']) && $_GET['password_reset'] === 'success') {
    $success = "Password reset successful! Please log in with your new password.";
}

if (empty($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    $stmt = $conn->prepare("SELECT id, username, is_admin FROM users WHERE remember_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        $new_token = bin2hex(random_bytes(32));
        
        setcookie(
            'remember_token', 
            $new_token, 
            [
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'],
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
        
        $update_stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_token, $user['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        header("Location: dashboard.php");
        exit();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password_hash, is_admin FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    
                    setcookie(
                        'remember_token', 
                        $token, 
                        [
                            'path' => '/',
                            'domain' => $_SERVER['HTTP_HOST'],
                            'secure' => isset($_SERVER['HTTPS']),
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );
                    
                    $stmt = $conn->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->bind_param("si", $token, $user['id']);
                    $stmt->execute();
                    $stmt->close();
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } else {
            $error = "Invalid username or password";
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
