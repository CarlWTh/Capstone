<?php
session_start();
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if email is provided
if (!isset($_GET['email'])) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_GET['email'];
$error = '';
$debug_output = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);
    
    if (empty($code)) {
        $error = "Please enter the verification code";
    } else {
        // Check if the code is valid
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_token_expires > NOW()");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Store user ID in session for password reset
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_email'] = $email;
            
            // Redirect to create new password
            header("Location: create-new-password.php");
            exit();
        } else {
            $error = "Invalid or expired verification code";
            $debug_output = "Code verification failed for email: $email with code: $code<br>";
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
    <title>Verify Code - Bottle Recycling System</title>
    <link rel="stylesheet" href="/css/styles.css">
    <style>
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            color: #333;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #007bff;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <h2>Verify Code</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <p>We've sent a verification code to <?php echo htmlspecialchars($email); ?>. Please enter it below.</p>
            
            <div class="form-group">
                <label for="code">Verification Code</label>
                <input 
                    type="text" 
                    id="code" 
                    name="code" 
                    placeholder="Enter 6-digit code" 
                    required
                    pattern="\d{6}"
                    title="Please enter a 6-digit code"
                >
                <i class="icon-lock"></i>
            </div>
            
            <button type="submit" class="login-button">
                Verify Code
            </button>
            
            <p style="text-align: center; margin-top: 10px;">
                <a href="forgot-password.php">Request a new code</a>
            </p>
            
            <?php if (!empty($debug_output)): ?>
                <div class="debug-info">
                    <h3>Debug Information</h3>
                    <?php echo $debug_output; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>