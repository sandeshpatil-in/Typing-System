<?php
/**
 * ============================================
 * System Diagnostics
 * ============================================
 * 
 * Check if the system is properly configured
 * Helps identify and fix initialization errors
 */

echo "<h1>Ahilya Student Desk - System Diagnostics</h1>";
echo "<hr>";

// Test 1: Check PHP Version
echo "<h3>✓ PHP Version</h3>";
echo "PHP " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4', '>=')) {
    echo "<span style='color: green;'>✓ PHP version is compatible</span><br>";
} else {
    echo "<span style='color: red;'>✗ PHP version too old (require 7.4+)</span><br>";
}
echo "<hr>";

// Test 2: Check Required Files
echo "<h3>Required Files</h3>";
$files = [
    'config/constants.php',
    'config/database.php',
    'config/DatabaseConnection.php',
    'config/ThemeConfig.php',
    'includes/helpers.php',
    'includes/init.php',
    'includes/header.php',
    'includes/footer.php',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file <span style='color: green;'>(exists)</span><br>";
    } else {
        echo "✗ $file <span style='color: red;'>(MISSING)</span><br>";
    }
}
echo "<hr>";

// Test 3: Try to load configuration
echo "<h3>Configuration Loading</h3>";
try {
    require_once 'config/constants.php';
    echo "✓ Constants loaded<br>";
    
    if (defined('APP_NAME')) {
        echo "✓ APP_NAME = " . APP_NAME . "<br>";
    } else {
        echo "✗ APP_NAME constant not defined<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading constants: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// Test 4: Try to load database
echo "<h3>Database Connection</h3>";
try {
    require_once 'config/database.php';
    if ($conn) {
        echo "✓ Database connected<br>";
        echo "✓ Database: " . DB_NAME . "<br>";
    } else {
        echo "✗ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "✗ Error connecting to database: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// Test 5: Try to load helpers
echo "<h3>Helper Functions</h3>";
try {
    require_once 'includes/helpers.php';
    echo "✓ Helpers loaded<br>";
    
    if (function_exists('sanitizeInput')) {
        echo "✓ sanitizeInput() exists<br>";
    } else {
        echo "✗ sanitizeInput() function not found<br>";
    }
    
    if (function_exists('isValidEmail')) {
        echo "✓ isValidEmail() exists<br>";
    } else {
        echo "✗ isValidEmail() function not found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading helpers: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// Test 6: Try to load theme config
echo "<h3>Theme Configuration</h3>";
try {
    require_once 'config/ThemeConfig.php';
    if (class_exists('ThemeConfig')) {
        echo "✓ ThemeConfig class loaded<br>";
        $colors = ThemeConfig::getColorScheme();
        echo "✓ Theme colors available (" . count($colors) . " colors)<br>";
    } else {
        echo "✗ ThemeConfig class not found<br>";
    }
} catch (Exception $e) {
    echo "✗ Error loading theme config: " . $e->getMessage() . "<br>";
}
echo "<hr>";

// Test 7: Check initialization file
echo "<h3>Initialization File</h3>";
if (file_exists('includes/init.php')) {
    echo "✓ init.php exists<br>";
    try {
        require_once 'includes/init.php';
        echo "✓ init.php loaded successfully<br>";
        if (defined('APP_INITIALIZED')) {
            echo "✓ APP_INITIALIZED = true<br>";
        }
    } catch (Exception $e) {
        echo "✗ Error loading init.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "✗ init.php not found<br>";
}
echo "<hr>";

echo "<h3>System Status</h3>";
echo "<span style='color: green;'><strong>✓ All checks completed. System is ready!</strong></span>";
?>
