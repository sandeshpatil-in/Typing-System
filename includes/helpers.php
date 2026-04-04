<?php
/**
 * ============================================
 * Utility Helper Functions
 * ============================================
 * 
 * Reusable functions for common operations
 * Input validation, sanitization, and security
 */

// ==========================================
// INPUT VALIDATION & SANITIZATION
// ==========================================

/**
 * Sanitize user input
 * 
 * @param mixed $input Input value to sanitize
 * @return mixed Sanitized value
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * 
 * @param string $email Email to validate
 * @return bool True if valid
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate required fields
 * 
 * @param array $fields Required field names
 * @param array $data Data to validate
 * @return bool True if all fields exist
 */
function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    return true;
}

/**
 * Get safe GET parameter
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value if not found
 * @return mixed Safe parameter value
 */
function getSafeGet($key, $default = null) {
    return isset($_GET[$key]) ? sanitizeInput($_GET[$key]) : $default;
}

/**
 * Get safe POST parameter
 * 
 * @param string $key Parameter key
 * @param mixed $default Default value if not found
 * @return mixed Safe parameter value
 */
function getSafePost($key, $default = null) {
    return isset($_POST[$key]) ? sanitizeInput($_POST[$key]) : $default;
}

// ==========================================
// ERROR HANDLING
// ==========================================

/**
 * Log error to file
 * 
 * @param string $message Error message
 * @param string $type Error type
 * @return void
 */
function logError($message, $type = 'ERROR') {
    // Use IST for consistent logging across the app
    $timestamp = date('d-m-Y H:i:s');
    $logEntry = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    if (DEBUG_MODE || APP_ENV === 'production') {
        error_log($logEntry, 3, ERROR_LOG_FILE);
    }
}

/**
 * Handle error gracefully
 * 
 * @param string $message Error message
 * @param int $code HTTP status code
 * @return void
 */
function handleError($message, $code = 500) {
    logError($message);
    
    if (DEBUG_MODE) {
        echo "<div class='error-message'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($message);
        echo "</div>";
    } else {
        http_response_code($code);
        die("An error occurred. Please try again later.");
    }
}

// ==========================================
// SESSION MANAGEMENT
// ==========================================

/**
 * Start secure session
 * 
 * @return void
 */
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            ]);
        }
        session_start();
        
        // Set secure session configuration
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
    }
}

/**
 * Check if user is student and logged in
 * 
 * @return bool True if student logged in
 */
function isStudentLoggedIn() {
    return isset($_SESSION[STUDENT_SESSION_KEY]) && !empty($_SESSION[STUDENT_SESSION_KEY]);
}

/**
 * Check if user is admin and logged in
 * 
 * @return bool True if admin logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION[ADMIN_SESSION_KEY]) && !empty($_SESSION[ADMIN_SESSION_KEY]);
}

/**
 * Get current logged in student ID
 * 
 * @return int|null Student ID or null
 */
function getStudentId() {
    return $_SESSION[STUDENT_SESSION_KEY] ?? null;
}

/**
 * Get current logged in admin ID
 * 
 * @return int|null Admin ID or null
 */
function getAdminId() {
    return $_SESSION[ADMIN_SESSION_KEY] ?? null;
}

/**
 * Store authenticated student session
 *
 * @param int $studentId Student ID
 * @return void
 */
function loginStudent($studentId) {
    initSession();
    session_regenerate_id(true);
    $_SESSION[STUDENT_SESSION_KEY] = (int)$studentId;
}

/**
 * Destroy current session safely
 *
 * @return void
 */
function logoutCurrentUser() {
    initSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Redirect to login page
 * 
 * @param string $type 'student' or 'admin'
 * @return void
 */
function redirectToLogin($type = 'student') {
    $loginPage = ($type === 'admin') ? 'admin/login.php' : 'account/login.php';
    header("Location: " . BASE_URL . $loginPage);
    exit();
}

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Redirect to page
 * 
 * @param string $path Page path
 * @return void
 */
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

/**
 * Get current page name
 * 
 * @return string Page name without extension
 */
function getCurrentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

/**
 * Check if current page is
 * 
 * @param string $page Page name
 * @return bool True if current page matches
 */
function isCurrentPage($page) {
    return getCurrentPage() === $page;
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd-m-Y h:i A') {
    if (empty($date)) {
        return 'Not available';
    }

    $timestamp = strtotime((string) $date);

    if ($timestamp === false) {
        return $date;
    }

    return date($format, $timestamp);
}

/**
 * Generate success alert HTML
 * 
 * @param string $message Success message
 * @return string HTML alert
 */
function successAlert($message) {
    return "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Generate error alert HTML
 * 
 * @param string $message Error message
 * @return string HTML alert
 */
function errorAlert($message) {
    return "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Generate warning alert HTML
 * 
 * @param string $message Warning message
 * @return string HTML alert
 */
function warningAlert($message) {
    return "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Generate or return current CSRF token
 *
 * @return string
 */
function csrfToken() {
    initSession();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token value
 *
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken($token) {
    initSession();

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Store flash data for next request
 *
 * @param string $key
 * @param mixed $value
 * @return void
 */
function setFlash($key, $value) {
    initSession();
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Get and remove flash data
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getFlash($key, $default = null) {
    initSession();

    if (!isset($_SESSION['_flash'][$key])) {
        return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    if (empty($_SESSION['_flash'])) {
        unset($_SESSION['_flash']);
    }

    return $value;
}

/**
 * Send JSON response and stop execution
 *
 * @param array $data
 * @param int $statusCode
 * @return void
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

?>
