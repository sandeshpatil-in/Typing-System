<?php
/**
 * ============================================
 * Database Configuration & Connection
 * ============================================
 * 
 * Backward compatible database connection
 */

// Load constants
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/constants.php';
}

// Establish database connection
$host = DB_HOST;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Create connection
$conn = @mysqli_connect($host, $user, $password, $database);

// Check connection
if (!$conn) {
    // Log error if logger available
    if (function_exists('logError')) {
        logError('Database connection failed: ' . mysqli_connect_error(), 'CRITICAL');
    }
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Set charset
if (!mysqli_set_charset($conn, DB_CHARSET)) {
    die("Failed to set database charset");
}

?>
