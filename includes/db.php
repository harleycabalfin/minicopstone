<?php
// ======================================================================
// DATABASE CONNECTION + AUTH + SESSION + UTILITY FUNCTIONS
// ======================================================================

// ---- DATABASE CONFIG ----
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "poultry_farm_db"; // your uploaded DB name

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// ---- SITE NAME ----
define("SITE_NAME", "Poultry Farm System");

// ======================================================================
// SESSION MANAGEMENT
// ======================================================================

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// Start session automatically when file is included
startSecureSession();

// ======================================================================
// DATABASE ACCESS HELPER
// ======================================================================
function getDB() {
    global $conn;
    return $conn;
}

// ======================================================================
// AUTHENTICATION HELPERS
// ======================================================================

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Require login for admin-only pages
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header("Location: ../login.php");
        exit();
    }
}

// Require login for user-only pages
function requireUser() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// ======================================================================
// SESSION TIMEOUT HANDLER
// ======================================================================
function checkSessionTimeout() {
    $timeout_duration = 1800; // 30 minutes
    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        session_unset();
        session_destroy();
        header("Location: ../login.php?timeout=true");
        exit();
    }
    $_SESSION['LAST_ACTIVITY'] = time();
}

// ======================================================================
// LOGIN FUNCTION
// ======================================================================
function login($username, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // If passwords are plain text (like your current DB)
        if ($password === $user['password_hash']) {
            startSecureSession();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['LAST_ACTIVITY'] = time();

            // Record last login
            $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update->bind_param("i", $user['user_id']);
            $update->execute();

            // Log system action
            logAction($user['user_id'], "User logged in", "users", $user['user_id']);

            return [
                'success' => true,
                'role' => $user['role']
            ];
        } else {
            return ['success' => false, 'message' => 'Invalid password.'];
        }
    } else {
        return ['success' => false, 'message' => 'User not found or inactive.'];
    }
}

// ======================================================================
// SYSTEM LOG FUNCTION
// ======================================================================
function logAction($user_id, $action, $table_affected = null, $record_id = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, action, table_affected, record_id, ip_address, user_agent) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_affected, $record_id, $ip, $agent);
    $stmt->execute();
}
?>
