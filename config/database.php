<?php

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'poultry_farm_db');

// Database connection options
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// System configuration
define('SITE_URL', 'http://localhost/poultry-farm-system');
define('SITE_NAME', 'Poultry Farm Management System');
define('TIMEZONE', 'Asia/Manila');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);

// Inventory thresholds
define('FEED_LOW_STOCK_THRESHOLD', 50); // kg
define('FEED_CRITICAL_STOCK_THRESHOLD', 20); // kg

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>