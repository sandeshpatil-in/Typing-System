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

// Load helpers
require_once __DIR__ . '/helpers.php';

// Start the session only after secure cookie settings are available.
initSession();

// Baseline security headers (sent before any HTML output)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

// Load database
require_once __DIR__ . '/../config/database.php';
/** @var mysqli $conn */

// Load access and plan helpers
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/auth_security.php';
require_once __DIR__ . '/typing_levels.php';

if (function_exists('pruneExpiredTypingResults')) {
    pruneExpiredTypingResults($conn);
}

// Load theme config
require_once __DIR__ . '/../config/ThemeConfig.php';

?>
