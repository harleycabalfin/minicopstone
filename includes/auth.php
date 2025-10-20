<?php
/**
 * Authentication and Security Functions
 * Handles login, session management, and access control
 */

require_once __DIR__ . '/db.php';

// Start secure session
function startSecureSession() {
    // Prevent session fixation
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        
        session_start();
        
        // Regenerate session ID to prevent fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSecureSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Check if user is admin
function isAdmin() {
    startSecureSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Require login (redirect if not logged in)
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . SITE_URL . "/login.php");
        exit();
    }
}

// Require admin access
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . SITE_URL . "/user/dashboard.php");
        exit();
    }
}

// Login function with brute force protection
function login($username, $password) {
    $db = getDB();
    
    // Check login attempts (basic brute force protection)
    if (checkLoginAttempts($username)) {
        return [
            'success' => false, 
            'message' => 'Too many failed login attempts. Please try again later.'
        ];
    }
    
    // Sanitize username
    $username = sanitizeInput($username);
    
    // Prepare statement to prevent SQL injection
    $stmt = $db->prepare("SELECT user_id, username, password_hash, full_name, email, role, is_active 
                          FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check if account is active
        if ($user['is_active'] != 1) {
            logLoginAttempt($username, false);
            return [
                'success' => false, 
                'message' => 'Account is deactivated. Please contact administrator.'
            ];
        }
        
        // Verify password using password_verify (secure hashing)
        if (password_verify($password, $user['password_hash'])) {
            // Password correct - create session
            startSecureSession();
            
            // Regenerate session ID after successful login
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Update last login time
            $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();
            
            // Log successful login
            logActivity($user['user_id'], "User logged in", "users", $user['user_id']);
            clearLoginAttempts($username);
            
            return [
                'success' => true, 
                'message' => 'Login successful',
                'role' => $user['role']
            ];
        } else {
            // Wrong password
            logLoginAttempt($username, false);
            return [
                'success' => false, 
                'message' => 'Invalid username or password'
            ];
        }
    } else {
        // User not found
        logLoginAttempt($username, false);
        return [
            'success' => false, 
            'message' => 'Invalid username or password'
        ];
    }
}

// Logout function
function logout() {
    startSecureSession();
    
    // Log logout activity
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], "User logged out", "users", $_SESSION['user_id']);
    }
    
    // Destroy session
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Basic brute force protection
function checkLoginAttempts($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $attempts = $_SESSION['login_attempts'][$username] ?? 0;
    return $attempts >= MAX_LOGIN_ATTEMPTS;
}

function logLoginAttempt($username, $success) {
    if (!$success) {
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
    }
}

function clearLoginAttempts($username) {
    if (isset($_SESSION['login_attempts'][$username])) {
        unset($_SESSION['login_attempts'][$username]);
    }
}

// Log user activity
function logActivity($user_id, $action, $table_affected = null, $record_id = null) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $db->prepare("INSERT INTO system_logs (user_id, action, table_affected, record_id, ip_address, user_agent) 
                          VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_affected, $record_id, $ip, $user_agent);
    $stmt->execute();
}

// Check session timeout
function checkSessionTimeout() {
    startSecureSession();
    
    if (isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        
        if ($elapsed >= SESSION_LIFETIME) {
            logout();
            header("Location: " . SITE_URL . "/login.php?timeout=1");
            exit();
        }
    }
}

// CSRF Token generation and validation
function generateCSRFToken() {
    startSecureSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    startSecureSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>