<?php
/**
 * Secure Logout Handler
 * Properly destroys session and redirects to login
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

// Perform logout
logout();

// Redirect to login page with logout message
header("Location: login.php?logout=success");
exit();
?>