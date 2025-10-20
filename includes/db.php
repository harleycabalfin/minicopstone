<?php
/**
 * Database Connection Handler
 * Uses MySQLi with prepared statements for security
 */

require_once __DIR__ . '/../config/database.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            // Create connection with error handling
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset for security (prevent SQL injection via encoding)
            if (!$this->conn->set_charset(DB_CHARSET)) {
                throw new Exception("Error setting charset: " . $this->conn->error);
            }
            
        } catch (Exception $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please contact administrator.");
        }
    }
    
    // Singleton pattern - only one connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    // Get connection object
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Close connection
    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Global function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}

// Prepared statement helper function
function executeQuery($query, $types = "", $params = []) {
    $db = getDB();
    $stmt = $db->prepare($query);
    
    if ($stmt === false) {
        error_log("Prepare failed: " . $db->error);
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    return $stmt;
}

// Sanitize input function
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}
?>