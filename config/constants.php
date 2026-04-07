<?php
/**
 * ============================================
 * Application Configuration Constants
 * ============================================
 * 
 * Centralized configuration management
 * Professional enterprise-level structure
 */

if (!function_exists('loadEnvFile')) {
    function loadEnvFile($filePath) {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));

            if ($name === '') {
                continue;
            }

            $value = trim($value, "\"'");

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('detectBaseUrl')) {
    function detectBaseUrl($projectRoot) {
        if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST']) || empty($_SERVER['SCRIPT_NAME'])) {
            return 'http://localhost/ahilya-typing/';
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']);
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $scriptDir = $scriptDir === '.' ? '' : rtrim($scriptDir, '/');

        $scriptFilename = isset($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_FILENAME']) : '';
        $projectRoot = str_replace('\\', '/', rtrim((string) $projectRoot, '/'));
        $currentDir = $scriptFilename !== '' ? str_replace('\\', '/', dirname($scriptFilename)) : '';
        $relativeDir = '';

        if ($currentDir !== '' && str_starts_with($currentDir, $projectRoot)) {
            $relativeDir = trim(substr($currentDir, strlen($projectRoot)), '/');
        }

        $basePath = $scriptDir;
        if ($relativeDir !== '' && str_ends_with($basePath, '/' . $relativeDir)) {
            $basePath = substr($basePath, 0, -strlen('/' . $relativeDir));
        }

        $basePath = trim($basePath, '/');

        return $scheme . '://' . $host . ($basePath !== '' ? '/' . $basePath : '') . '/';
    }
}

loadEnvFile(dirname(dirname(__FILE__)) . '/.env');

// ==========================================
// DATABASE CONFIGURATION
// ==========================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'ahilya_typing');
define('DB_CHARSET', 'utf8mb4');


// infinityfree database configuration

//define('DB_HOST', 'sql100.infinityfree.com');
//define('DB_USER', 'if0_41473773');
//define('DB_PASS', 'GhdmfRkqW9cX9');
//define('DB_NAME', 'if0_41473773_ahilya_typing');
//define('DB_CHARSET', 'utf8mb4');


// ==========================================
// APPLICATION CONFIGURATION
// ==========================================
define('APP_NAME', getenv('APP_NAME') ?: 'Ahilya Typing');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production'); // development | production
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'Asia/Kolkata');

// Ensure all date/time functions use Indian Standard Time
date_default_timezone_set(APP_TIMEZONE);

// ==========================================
// PATH CONFIGURATION
// ==========================================
$baseUrl = getenv('BASE_URL') ?: detectBaseUrl(dirname(dirname(__FILE__)));
define('BASE_URL', rtrim($baseUrl, '/') . '/');

//define('BASE_URL', 'https://ahilyatyping.com/');
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('ASSETS_PATH', ROOT_PATH . '/assets/');
define('INCLUDES_PATH', ROOT_PATH . '/includes/');

// ==========================================
// SESSION CONFIGURATION
// ==========================================
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('STUDENT_SESSION_KEY', 'student_id');
define('ADMIN_SESSION_KEY', 'admin_id');

// ==========================================
// SECURITY CONFIGURATION
// ==========================================
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_ATTEMPT_TIMEOUT', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 6);
define('CAPTCHA_EXPIRY_SECONDS', 600);
define('PASSWORD_RESET_EXPIRY_MINUTES', max(5, (int) (getenv('PASSWORD_RESET_EXPIRY_MINUTES') ?: 30)));
define('GUEST_TEST_LIMIT', 5);
define('PLAN_DURATION_DAYS', 30);
define('ATTEMPT_RETENTION_DAYS', 5);
define('ATTEMPT_RETENTION_CLEANUP_INTERVAL', 3600); // 1 hour

// ==========================================
// PAGINATION & LIMITS
// ==========================================
define('ITEMS_PER_PAGE', 10);
define('TYPING_TEST_MAX_TIME', 600); // 10 minutes max

// ==========================================
// PAYMENT CONFIGURATION
// ==========================================
define('PLAN_NAME', 'Pro Monthly');
define('PLAN_PRICE', 199.00);
define('PLAN_PRICE_PAISE', (int) round(PLAN_PRICE * 100));
define('PAYMENT_CURRENCY', 'INR');
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: '');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: '');
define('MAIL_FROM_ADDRESS', getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@example.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: APP_NAME);
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');

// ==========================================
// ERROR HANDLING
// ==========================================
define('ERROR_LOG_FILE', ROOT_PATH . '/logs/error.log');
define('DEBUG_MODE', APP_ENV === 'development');

// ==========================================
// UI THEME COLORS (Dark Mode)
// ==========================================
define('COLOR_PRIMARY', '#0f8bff');
define('COLOR_PRIMARY_ALT', '#82b7ff');
define('COLOR_BG_PRIMARY', '#0d0d0d');
define('COLOR_BG_SECONDARY', '#1a1a1a');
define('COLOR_TEXT_PRIMARY', '#e0e0e0');
define('COLOR_TEXT_MUTED', '#a0a0a0');
define('COLOR_BORDER', '#333333');
define('COLOR_SUCCESS', '#51cf66');
define('COLOR_DANGER', '#ff6b6b');
define('COLOR_WARNING', '#ffd43b');
define('COLOR_INFO', '#4dabf7');

// ==========================================
// FONT CONFIGURATION
// ==========================================
define('FONT_PRIMARY', "'Lato', sans-serif");
define('FONT_SIZE_BASE', '16px');
define('FONT_WEIGHT_NORMAL', '400');
define('FONT_WEIGHT_MEDIUM', '500');
define('FONT_WEIGHT_BOLD', '700');
define('FONT_WEIGHT_HEAVY', '900');

// Create logs directory if not exists
if (!is_dir(dirname(ERROR_LOG_FILE))) {
    mkdir(dirname(ERROR_LOG_FILE), 0755, true);
}

?>
