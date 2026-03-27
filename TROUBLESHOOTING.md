# System Error Fix & Troubleshooting Guide

## 🔧 Quick Fixes

### Issue: Fatal Error / Page Won't Load

**Solution 1: Run Diagnostics**
1. Access: `http://localhost/ahilya-typing/diagnostics.php`
2. Check which component is failing
3. Review error messages
4. Fix issues listed below

**Solution 2: Check PHP Version**
- Requires: PHP 7.4+
- Check: `<?php phpversion(); ?>`

**Solution 3: Verify Database Connection**
- Check MySQL is running
- Verify credentials in `config/constants.php`
- Database should be: `ahilya_typing`
- Default user: `root` with no password

---

## 📋 File Checklist

These files MUST exist:

```
✓ config/constants.php         - Application constants
✓ config/database.php          - Database setup
✓ config/DatabaseConnection.php - DB connection class
✓ config/ThemeConfig.php       - Theme configuration
✓ includes/init.php            - Initialization (NEW)
✓ includes/helpers.php         - Helper functions
✓ includes/header.php          - Header component
✓ includes/footer.php          - Footer component
```

**If any file is missing:**
1. Recreate from documentation
2. Run `diagnostics.php` again
3. Check error messages

---

## 🆘 Common Errors & Solutions

### Error 1: "Fatal error: Uncaught Error: Call to undefined function"

**Cause:** Helper function not loaded

**Solution:**
- Verify `includes/init.php` exists
- Verify `includes/helpers.php` exists
- Check that `includes/header.php` includes `init.php` at the top
- Run diagnostics.php

### Error 2: "Fatal error: Class 'DatabaseConnection' not found"

**Cause:** DatabaseConnection class not loaded

**Solution:**
- Verify `config/DatabaseConnection.php` exists
- Verify `includes/init.php` loads it
- Check file syntax

### Error 3: "Database connection failed"

**Cause:** Wrong database credentials or MySQL not running

**Solution:**
1. Check MySQL is running (XAMPP)
2. Verify credentials in `config/constants.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'ahilya_typing');
   ```
3. Ensure database exists
4. Run diagnostics.php

### Error 4: "Undefined variable: APP_NAME"

**Cause:** Constants not loaded

**Solution:**
- Check `config/constants.php` exists
- Verify it uses `define()` not `=`
- Run diagnostics.php

### Error 5: "Parse error: syntax error"

**Cause:** PHP syntax error in a file

**Solution:**
1. Check file is valid PHP
2. Verify closing `?>` tags
3. Check for missing semicolons
4. Run diagnostics.php which will identify the file

---

## 🧪 Testing Steps

### Test 1: Database Connection
```php
<?php
require 'config/constants.php';
require 'config/database.php';

if ($conn) {
    echo "✓ Database connected!";
} else {
    echo "✗ Database connection failed";
}
?>
```

### Test 2: Helper Functions
```php
<?php
require 'includes/init.php';

echo sanitizeInput("  test  ");  // Should output: test
echo isValidEmail("test@example.com") ? "✓ Valid" : "✗ Invalid";
?>
```

### Test 3: Theme Configuration
```php
<?php
require 'config/ThemeConfig.php';
$color = ThemeConfig::getColor('primary');
echo $color;  // Should output: #0f8bff
?>
```

---

## 📝 File Syntax Check

### Check config/constants.php
```php
<?php
// Should start with <?php
// Should have define() statements
// Should end with ?>
?>
```

### Check includes/helpers.php
```php
<?php
// Should have function definitions
// Each function should have proper closing brace
// Should end with ?>
?>
```

### Check includes/init.php
```php
<?php
// Should have require_once statements
// Should end with ?>
?>
```

---

## 🔍 Debug Mode

To enable debug mode, edit `config/constants.php`:

```php
define('APP_ENV', 'development');  // Changed from 'production'
define('DEBUG_MODE', true);         // Add this line
```

This will show:
- Detailed error messages
- Database errors
- Helper function details

---

## 📞 How to Use Diagnostics

1. **Access Diagnostics:**
   ```
   http://localhost/ahilya-typing/diagnostics.php
   ```

2. **Read Results:**
   - ✓ Green = Working
   - ✗ Red = Problem

3. **Fix Issues:**
   - Missing files? Create them
   - Database error? Check credentials
   - Function not found? Check loading order

4. **Verify Fix:**
   - Run diagnostics.php again
   - Should see all ✓ checks

---

## ⚠️ Critical Configuration

These MUST be correct in `config/constants.php`:

```php
// Database must be created and running
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ahilya_typing');

// Application name
define('APP_NAME', 'Ahilya Student Desk');

// Base URL
define('BASE_URL', 'http://localhost/ahilya-typing/');
```

---

## 🚀 Quick Recovery Steps

If project is broken:

1. **Run diagnostics.php**
   ```
   http://localhost/ahilya-typing/diagnostics.php
   ```

2. **Check which file is failing**
   - Look for ✗ marks

3. **Fix the issue:**
   - If file missing: Create it
   - If syntax error: Check file syntax
   - If config issue: Update constants.php

4. **Verify fix:**
   - Run diagnostics.php again
   - All checks should be ✓

5. **If still broken:**
   - Check PHP error logs: `php_error.log` (XAMPP)
   - Check MySQL is running
   - Verify file permissions

---

## 📋 Step by Step Fix

### Step 1: Access Diagnostics
Open browser:
```
http://localhost/ahilya-typing/diagnostics.php
```

### Step 2: Check Results
Note which items show ✗ (failures)

### Step 3: Fix Issues

**If "Database connected failed":**
- Start MySQL (XAMPP)
- Check `config/constants.php` credentials

**If "File missing":**
- Recreate the file from documentation
- Verify file location

**If "Function not found":**
- Verify `includes/init.php` exists
- Check header.php loads `init.php`

### Step 4: Reload Diagnostics
Refresh: `http://localhost/ahilya-typing/diagnostics.php`

### Step 5: Test Main Site
If diagnostics shows all ✓:
```
http://localhost/ahilya-typing/index.php
```

---

## 🆘 Still Not Working?

1. **Check PHP Error Log:**
   - XAMPP logs: `apache/logs/error.log`
   - MySQL logs: Check XAMPP console

2. **Check Browser Console:**
   - F12 → Console tab
   - Look for errors

3. **Check File Permissions:**
   - All files should be readable
   - `logs/` directory should be writable

4. **Verify PHP Installed Correctly:**
   - Run: `php -v` in command line
   - Should show version 7.4+

---

**This guide should help you fix any setup issues!**

If you need more help, use `diagnostics.php` to identify the specific problem.
