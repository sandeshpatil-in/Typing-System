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

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Establish database connection
$host = DB_HOST;
$user = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

// Create connection
$conn = false;

try {
    $conn = @mysqli_connect($host, $user, $password, $database);
} catch (Throwable $exception) {
    if (function_exists('logError')) {
        logError('Database connection exception: ' . $exception->getMessage(), 'CRITICAL');
    }
}

// Check connection
if (!$conn) {
    if (function_exists('logError')) {
        logError('Database connection failed: ' . mysqli_connect_error(), 'CRITICAL');
    }

    if (function_exists('handleError')) {
        handleError('Database connection failed', 500);
    }

    http_response_code(500);
    exit('Database connection failed.');
}

// Set charset
$charsetSet = false;

try {
    $charsetSet = mysqli_set_charset($conn, DB_CHARSET);
} catch (Throwable $exception) {
    if (function_exists('logError')) {
        logError('Database charset exception: ' . $exception->getMessage(), 'CRITICAL');
    }
}

if (!$charsetSet) {
    if (function_exists('logError')) {
        logError('Failed to set database charset', 'CRITICAL');
    }

    if (function_exists('handleError')) {
        handleError('Database initialization failed', 500);
    }

    http_response_code(500);
    exit('Database initialization failed.');
}

?>
