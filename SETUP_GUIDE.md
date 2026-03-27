# System Setup & Error Resolution Guide

## 🎯 What to Do If Project Won't Run

### STEP 1: Check Diagnostics (Takes 1 minute)

1. Open browser
2. Go to: `http://localhost/ahilya-typing/diagnostics.php`
3. Read the results
4. Fix any failures (see STEP 2)

### STEP 2: Fix Common Issues

**Issue: Database Connection Failed**
- [ ] MySQL is running (check XAMPP)
- [ ] Database exists: `ahilya_typing`
- [ ] Credentials correct in `config/constants.php`:
  ```php
  DB_HOST = 'localhost'
  DB_USER = 'root'
  DB_PASS = ''
  DB_NAME = 'ahilya_typing'
  ```

**Issue: File Missing**
- Files that MUST exist:
  - `config/constants.php`
  - `config/database.php`
  - `config/DatabaseConnection.php`
  - `config/ThemeConfig.php`
  - `includes/init.php` (NEW FILE)
  - `includes/helpers.php`
  - `includes/header.php`
  - `includes/footer.php`

**Issue: Function Not Found**
- Check `includes/header.php` has this at the very top:
  ```php
  <?php
  require_once __DIR__ . '/init.php';
  ?>
  ```

**Issue: Parse Error / Syntax Error**
- Check each file has proper PHP tags:
  ```php
  <?php
  // Content here
  ?>
  ```

### STEP 3: Test Page Load

Try to access: `http://localhost/ahilya-typing/index.php`

- **Works?** ✓ Problem solved!
- **Still broken?** Go to STEP 4

### STEP 4: Check Error Logs

1. **PHP Error Log** (XAMPP):
   - Location: `xampp/apache/logs/error.log`
   - Look for: "Fatal error" or "Parse error"

2. **Browser Console**:
   - Press F12
   - Go to "Console" tab
   - Look for red errors

3. **XAMPP Console**:
   - Check if MySQL is running
   - Check if Apache is running

### STEP 5: Get Specific Help

If diagnostics shows what's wrong:
- Syntax errors → Check file syntax
- Missing file → Recreate file from documentation
- Database error → Start MySQL, check credentials
- Undefined function → Check init.php is loaded

---

## 📦 Fresh Installation (If All Else Fails)

### Backup Current Files
```bash
copy ahilya-typing ahilya-typing-backup
```

### Delete Problem Files
```bash
Delete these (they will be recreated or exist):
- config/constants.php ✓ Keep
- config/database.php ✓ Keep
- includes/header.php ✓ Keep
- includes/footer.php ✓ Keep
- includes/*.php (others) - OK to backup
```

### Recreate init.php (Critical File)
Create: `includes/init.php`

```php
<?php
if (defined('APP_INITIALIZED')) {
    return;
}
define('APP_INITIALIZED', true);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../config/ThemeConfig.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
```

### Verify Basic Requirements
- [ ] PHP 7.4+ installed
- [ ] MySQL running
- [ ] Database `ahilya_typing` exists
- [ ] xampp/htdocs/ahilya-typing/ exists

### Test Again
```
http://localhost/ahilya-typing/diagnostics.php
```

---

## 🔧 Manual File Verification

### Verify config/constants.php
Should contain:
```php
<?php
define('DB_HOST', 'localhost');
define('APP_NAME', 'Ahilya Student Desk');
// ... more defines
?>
```

### Verify config/database.php
Should contain:
```php
<?php
require_once __DIR__ . '/constants.php';
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Database Connection Failed");
}
?>
```

### Verify includes/header.php
Should start with:
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    require_once __DIR__ . '/init.php';
    ?>
```

### Verify includes/init.php
Should load in this order:
1. constants.php
2. database.php
3. helpers.php
4. ThemeConfig.php
5. Start session

---

## 🚨 Emergency Recovery

If nothing else works:

### Option 1: Revert to Backup
```bash
rmdir ahilya-typing
rename ahilya-typing-backup ahilya-typing
```

### Option 2: Delete & Restore
1. Delete `ahilya-typing` folder
2. Get backup from last working version
3. Copy all files back

### Option 3: Get Help
Include these details:
- Error from diagnostics.php
- PHP version: `<?php echo phpversion(); ?>`
- Error log content
- Which files are missing

---

## ✅ Success Checklist

- [ ] diagnostics.php shows all ✓
- [ ] index.php loads without errors
- [ ] Can see "Master Your Typing Skills" page
- [ ] Navigation bar appears
- [ ] Dark theme is visible
- [ ] No red error messages
- [ ] No JavaScript console errors (F12)

---

## 📞 Quick Reference

| Issue | Solution |
|-------|----------|
| Nothing works | Run diagnostics.php |
| Database failed | Start MySQL, check credentials |
| File missing | Recreate from documentation |
| Function not found | Check init.php loaded |
| Syntax error | Check <?php ?> tags |
| Page blank | Check PHP error log |
| Styles wrong | Check assets/css/style.css exists |
| Dark theme broken | Check constants loaded |

---

## 🔗 File Dependencies

```
index.php
  └─ includes/header.php
      └─ includes/init.php
          ├─ config/constants.php
          ├─ config/database.php
          ├─ includes/helpers.php
          ├─ config/ThemeConfig.php
          └─ session start

  └─ includes/footer.php ✓
```

Every page should:
1. Include `includes/header.php`
2. Include `includes/footer.php`
3. Header.php automatically loads everything else

---

## 📝 Notes for Developers

- Don't delete `includes/init.php` !
- Always include header.php before content
- Constants must be in constants.php
- Helper functions in helpers.php
- Theme config in config/ThemeConfig.php

---

**Last Updated:** March 2026

✅ **This guide should solve your problem!**

If issues persist, use `diagnostics.php` to identify the specific problem.
