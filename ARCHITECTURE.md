# Ahilya Student Desk - Professional Software Architecture

## 📋 Table of Contents
1. [Project Structure](#project-structure)
2. [Architecture Overview](#architecture-overview)
3. [Code Standards](#code-standards)
4. [Configuration Management](#configuration-management)
5. [Security Best Practices](#security-best-practices)
6. [Development Guidelines](#development-guidelines)

---

## Project Structure

```
ahilya-typing/
├── config/
│   ├── constants.php              # Global application constants
│   ├── database.php               # Database initialization
│   ├── DatabaseConnection.php     # Database class (singleton pattern)
│   └── ThemeConfig.php           # Theme configuration class
├── includes/
│   ├── header.php                # Reusable header component
│   ├── footer.php                # Reusable footer component
│   ├── helpers.php               # Utility functions
│   ├── auth.php                  # Student authentication
│   ├── student-auth.php          # Student-specific auth
│   └── admin-auth.php            # Admin-specific auth
├── assets/
│   ├── bootstrap/                # Bootstrap framework
│   ├── css/
│   │   ├── style.css            # Global dark mode styles
│   │   └── typing.css           # Typing test specific styles
│   ├── js/
│   │   ├── typing.js            # Typing test logic
│   │   ├── timer.js             # Timer functionality
│   │   ├── result.js            # Result processing
│   │   └── remington-marathi.js # Marathi keyboard layout
│   └── images/                   # Image assets
├── account/
│   ├── login.php                # Student login page
│   ├── register.php             # Student registration
│   ├── dashboard.php            # Student dashboard
│   ├── logout.php               # Student logout
│   └── profile.php              # Student profile
├── admin/
│   ├── login.php                # Admin login page
│   ├── dashboard.php            # Admin dashboard
│   ├── students.php             # Manage students
│   ├── paragraphs.php           # Manage test paragraphs
│   ├── add-paragraph.php        # Add new paragraph
│   ├── edit-paragraph.php       # Edit paragraph
│   ├── results.php              # View test results
│   ├── logout.php               # Admin logout
│   └── activate-student.php     # Activate student accounts
├── api/
│   ├── get-paragraph.php        # API endpoint for paragraphs
│   └── save-result.php          # API endpoint for saving results
├── database/
│   └── schema.sql               # Database schema
├── logs/                         # Error logs (created automatically)
├── index.php                     # Home page
├── typing-test.php              # Main typing test
├── typing-preference.php        # Test configuration
├── result.php                   # Results page
├── about.php                    # About page
├── contact.php                  # Contact page
└── README.md                    # This file
```

---

## Architecture Overview

### 1. **Singleton Pattern (Database Connection)**
- Single database instance across the application
- Improved performance and resource management
- Located in: `config/DatabaseConnection.php`

```php
$db = DatabaseConnection::getInstance();
$conn = $db->getConnection();
```

### 2. **Configuration Management**
- Centralized constants in `config/constants.php`
- All configurations in one place
- Easy to update for different environments

### 3. **Theme-Driven Architecture**
- `ThemeConfig` class manages all theme settings
- Color schemes, typography, spacing in one location
- Easy to switch themes or customize

### 4. **Helper Functions**
- Reusable utility functions in `includes/helpers.php`
- Input sanitization and validation
- Session management
- Error handling

### 5. **Component-Based Templates**
- Reusable header and footer
- Consistent UI across all pages
- Easy maintenance and updates

---

## Code Standards

### Naming Conventions

#### **PHP Functions**
```php
// Use camelCase
function getUserData() { }
function validateInput() { }
function isUserLoggedIn() { }
```

#### **PHP Classes**
```php
// Use PascalCase
class DatabaseConnection { }
class ThemeConfig { }
class UserValidator { }
```

#### **Variables & Constants**
```php
// Variables: camelCase
$userName = "John";
$userEmail = "john@example.com";

// Constants: UPPER_SNAKE_CASE
define('DB_HOST', 'localhost');
define('APP_NAME', 'Ahilya Typing');
```

#### **CSS Classes & IDs**
```css
/* Classes: kebab-case */
.btn-primary { }
.form-control { }
.nav-link { }

/* IDs: camelCase */
#mainNavbar { }
#userMenu { }
```

### PHP Code Style

```php
<?php
/**
 * Function description
 * 
 * @param string $param Parameter description
 * @return string Return value description
 */
function exampleFunction($param) {
    // Code here
    return $result;
}

// Comments for complex logic
// Use meaningful variable names
// Keep functions small and focused
// Maximum 3-4 levels of indentation
?>
```

### HTML/CSS Style

```html
<!-- Use semantic HTML5 -->
<main role="main">
    <section id="content">
        <article>
            <h1>Title</h1>
            <p>Content</p>
        </article>
    </section>
</main>

<!-- Use CSS variables for colors -->
<div style="color: var(--text-primary);">Text</div>
```

---

## Configuration Management

### Environment-Based Configuration

The application uses a configuration hierarchy:

1. **constants.php** - Global constants
2. **ThemeConfig.php** - Theme and UI settings
3. **DatabaseConnection.php** - Database settings

### Using Configuration

```php
// Access constants
echo APP_NAME;
echo BASE_URL;
echo DB_HOST;

// Access theme colors
$colors = ThemeConfig::getColorScheme();
echo ThemeConfig::getColor('primary');

// Access typography
$typography = ThemeConfig::getTypography();
```

---

## Security Best Practices

### 1. **Input Sanitization**
```php
// Always sanitize user input
$email = sanitizeInput($_POST['email']);
$name = getSafePost('name');
```

### 2. **Prepared Statements**
```php
// Use prepared statements to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

### 3. **Session Security**
```php
// Check authentication before sensitive operations
if (!isStudentLoggedIn()) {
    redirectToLogin('student');
}

// Or for admin operations
if (!isAdminLoggedIn()) {
    redirectToLogin('admin');
}
```

### 4. **CSRF Protection**
- Implement CSRF tokens for form submissions
- Validate referrer and origin headers

### 5. **XSS Prevention**
```php
// Always escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
// Or use provided alert functions
echo successAlert("Operation successful!");
```

### 6. **Error Logging**
```php
// Log errors securely
logError("Critical error occurred", 'ERROR');
// Errors are logged to logs/error.log
```

---

## Development Guidelines

### Adding New Pages

1. **Create the page file** (e.g., `new-page.php`)
2. **Include header and footer**
```php
<?php include 'includes/header.php'; ?>

<!-- Your content here -->

<?php include 'includes/footer.php'; ?>
```

3. **Use consistent styling**
```php
<link rel="stylesheet" href="assets/css/style.css">
<style>
    /* Page-specific styles using CSS variables */
    .custom-class {
        color: var(--text-primary);
        background: var(--bg-secondary);
    }
</style>
```

### Adding New Features

1. **Create reusable helper function** in `includes/helpers.php`
2. **Add necessary constants** to `config/constants.php`
3. **Use prepared statements** for all database queries
4. **Implement proper error handling**
5. **Add code comments** for complex logic

### Database Operations

```php
// Always use prepared statements
$stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
if (!$stmt) {
    logError("Prepare failed: " . $conn->error);
    die("An error occurred.");
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Process data
}

$stmt->close();
```

### Error Handling Pattern

```php
try {
    // Attempt operation
    $result = performOperation();
    
    if (!$result) {
        throw new Exception("Operation failed");
    }
    
    echo successAlert("Success!");
    
} catch (Exception $e) {
    logError($e->getMessage(), 'ERROR');
    echo errorAlert("An error occurred: " . $e->getMessage());
}
```

---

## Best Practices Summary

✅ **DO:**
- Use helper functions for common operations
- Sanitize all user input
- Use prepared statements for queries
- Log errors and important events
- Use meaningful variable names
- Keep functions small and focused
- Use CSS variables for colors
- Comment complex logic
- Implement proper error handling
- Follow naming conventions

❌ **DON'T:**
- Hardcode configuration values
- Use inline styles (use CSS classes)
- Trust user input
- Write complex queries in page files
- Mix business logic with presentation
- Use global variables
- Duplicate code
- Ignore error handling
- Write cryptic variable names
- Leave empty catch blocks

---

## Directory Initialization

The application automatically creates required directories:
- `logs/` - For error logging

Ensure the web server has write permissions:
```bash
chmod 755 logs/
```

---

## Version Information

- **Application Version:** 1.0.0
- **PHP Version:** 7.4+
- **MySQL Version:** 5.7+
- **Bootstrap Version:** 5.3.2
- **Font:** Lato (Google Fonts)

---

## Support & Maintenance

For issues, feature requests, or improvements:
1. Check existing code patterns
2. Follow the architecture guidelines
3. Test thoroughly before deployment
4. Update documentation when adding features

---

**Last Updated:** March 2026
**Maintained by:** Development Team
