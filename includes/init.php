<?php
/**
 * ============================================
 * Application Initialization
 * ============================================
 * 
 * Load all required configuration and helpers
 * Must be included at the start of every page
 */

// Prevent multiple includes
if (defined('APP_INITIALIZED')) {
    return;
}
define('APP_INITIALIZED', true);

// Load constants
require_once __DIR__ . '/../config/constants.php';

// Load database
require_once __DIR__ . '/../config/database.php';

// Load helpers
require_once __DIR__ . '/helpers.php';

// Load access and plan helpers
require_once __DIR__ . '/access.php';

// Load theme config
require_once __DIR__ . '/../config/ThemeConfig.php';

// Initialize session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
