<?php
session_start();
require_once '../config/config.php'; // Fixed path to config.php

define('ENCRYPTION_KEY', 'your-secret-key-32-chars-long!!');
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('COOKIE_NAME', 'remember_admin_token');
define('COOKIE_EXPIRY', 30 * 24 * 60 * 60);

header('Content-Type: application/json');

// Utility functions
function encryptData($data) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt(json_encode($data), ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}
function decryptData($encryptedData) {
    $data = base64_decode($encryptedData);
    if (strpos($data, '::') === false) return null;
    list($encrypted_data, $iv) = explode('::', $data, 2);
    $decrypted = openssl_decrypt($encrypted_data, ENCRYPTION_METHOD, ENCRYPTION_KEY, 0, $iv);
    return json_decode($decrypted, true);
}
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
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}
function clearRememberMeCookie() {
    setcookie(
        COOKIE_NAME,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Handle auto-login via cookie
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_COOKIE[COOKIE_NAME]) && empty($_SESSION['admin_id'])) {
    try {
        $cookie_data = decryptData($_COOKIE[COOKIE_NAME]);
        if ($cookie_data && isset($cookie_data['admin_id'])) {
            if (isset($cookie_data['created_at']) && (time() - $cookie_data['created_at']) <= COOKIE_EXPIRY) {
                $stmt = $conn->prepare("SELECT admin_id, username, email, password_hash, is_admin FROM Admin WHERE admin_id = ?");
                $stmt->bind_param("i", $cookie_data['admin_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows === 1) {
                    $admin = $result->fetch_assoc();
                    if ($admin['password_hash'] === $cookie_data['password_hash']) {
                        $_SESSION['admin_id'] = $admin['admin_id'];
                        $_SESSION['username'] = $admin['username'];
                        $_SESSION['email'] = $admin['email'];
                        $_SESSION['is_admin_session'] = $admin['is_admin'];
                        setRememberMeCookie($admin);
                        $stmt->close();
                        echo json_encode(['success' => true, 'redirect' => '/admin/public/pages/dashboard.php']);
                        exit();
                    } else {
                        clearRememberMeCookie();
                    }
                } else {
                    clearRememberMeCookie();
                }
                $stmt->close();
            } else {
                clearRememberMeCookie();
            }
        }
    } catch (Exception $e) {
        clearRememberMeCookie();
    }
    echo json_encode(['success' => false]);
    exit();
}

// Handle login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $remember = !empty($input['remember']);

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'error' => 'Please enter both username and password.']);
        exit();
    }
    $stmt = $conn->prepare("SELECT admin_id, username, email, password_hash, is_admin FROM Admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['is_admin_session'] = $admin['is_admin'];
            if ($remember) {
                setRememberMeCookie($admin);
            } else {
                clearRememberMeCookie();
            }
            $stmt->close();
            echo json_encode(['success' => true, 'redirect' => '/admin/public/pages/dashboard.php']);
            exit();
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid username or password.']);
    }
    $stmt->close();
    exit();
}

// Default: not allowed
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
exit();
?>
