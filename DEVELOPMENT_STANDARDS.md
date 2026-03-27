# Professional Development Standards

## Code Organization & Structure

### File Organization
- Keep files focused on a single responsibility
- Use clear, descriptive filenames
- Group related files in directories
- Follow the established directory structure

### Function Organization
1. **Helper Functions** - `includes/helpers.php`
2. **Class Methods** - In dedicated class files
3. **Page Logic** - At the top of page files after includes
4. **Presentation** - HTML/Template code at the bottom

### Code Comments

```php
<?php
// ================================================
// SECTION HEADER (for major sections)
// ================================================

/**
 * Function or class description
 * Explain what it does and why
 * 
 * @param string $param Parameter description
 * @return string Return value description
 */
function doSomething($param) {
    // Single-line comment for complex logic
    
    // Break into multiple lines for clarity
    $result = performOperation();
    
    return $result;
}
```

---

## Naming Conventions

### Files
```
// Page files
page-name.php
account-login.php
user-dashboard.php

// Class files
UserValidator.php
DatabaseConnection.php
ThemeConfig.php

// Include/utility files
helpers.php
functions.php
```

### Functions
```php
// Action verbs
function getUserData() { }
function validateEmail() { }
function sendNotification() { }
function isUserLoggedIn() { }
function hasPermission() { }
```

### Variables
```php
// Descriptive and searchable names
$userEmail = 'user@example.com';
$studentId = 123;
$isActive = true;
$maxAttempts = 5;
$errorMessage = 'Invalid input';
```

### Constants
```php
// UPPER_SNAKE_CASE
define('DB_HOST', 'localhost');
define('APP_NAME', 'Ahilya Typing');
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
```

### CSS Classes
```css
/* Use kebab-case */
.btn-primary { }
.form-control { }
.nav-menu { }
.card-header { }
.text-muted { }
```

---

## HTML Best Practices

### Semantic HTML
```html
<!-- Good - Semantic -->
<main>
    <section id="content">
        <article>
            <h1>Title</h1>
            <p>Content</p>
        </article>
    </section>
</main>

<!-- Avoid - Non-semantic -->
<div id="content">
    <div class="article">
        <div class="heading">Title</div>
        <div class="text">Content</div>
    </div>
</div>
```

### Accessibility
```html
<!-- Include alt text for images -->
<img src="image.png" alt="Descriptive text">

<!-- Use proper heading hierarchy -->
<h1>Page Title</h1>
<h2>Section Title</h2>
<h3>Subsection</h3>

<!-- Use labels for form inputs -->
<label for="email">Email:</label>
<input type="email" id="email" name="email">

<!-- Use role attributes when needed -->
<nav role="navigation">
<main role="main">
<footer role="contentinfo">
```

---

## CSS Best Practices

### Organization
```css
/* 1. Root Variables */
:root {
    --primary: #0f8bff;
}

/* 2. Reset/Base Styles */
* { box-sizing: border-box; }
body { font-family: 'Lato', sans-serif; }

/* 3. Component Classes */
.btn { }
.card { }
.nav { }

/* 4. State/Modifier Classes */
.btn.active { }
.card.disabled { }

/* 5. Responsive Queries */
@media (max-width: 768px) { }
```

### Use CSS Variables
```css
/* Good */
.button {
    background-color: var(--primary);
    color: var(--text-primary);
    padding: var(--spacing-md);
}

/* Avoid */
.button {
    background-color: #0f8bff;
    color: #e0e0e0;
    padding: 1rem;
}
```

### Avoid Inline Styles
```html
<!-- Avoid -->
<div style="background: #0d0d0d; color: #e0e0e0;">Content</div>

<!-- Prefer -->
<div class="card">Content</div>

<!-- In CSS -->
.card {
    background: var(--bg-secondary);
    color: var(--text-primary);
}
```

---

## PHP Best Practices

### Input Validation
```php
<?php
// Always validate and sanitize
$email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';

if (!isValidEmail($email)) {
    echo errorAlert('Invalid email format');
    exit;
}

// Use provided helper functions
$name = getSafePost('name');
$id = getSafeGet('id', 0);
```

### Database Operations
```php
<?php
// Always use prepared statements
$stmt = $conn->prepare("SELECT * FROM students WHERE email = ?");
if (!$stmt) {
    logError("Prepare failed: " . $conn->error);
    echo errorAlert('Database error');
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Process row
}

$stmt->close();
```

### Error Handling
```php
<?php
try {
    // Attempt operation
    if (!validateData($data)) {
        throw new Exception("Invalid data");
    }
    
    $result = saveToDatabase($data);
    
    if (!$result) {
        throw new Exception("Save failed");
    }
    
    echo successAlert("Operation successful");
    
} catch (Exception $e) {
    logError($e->getMessage(), 'ERROR');
    echo errorAlert("An error occurred: " . htmlspecialchars($e->getMessage()));
}
```

### Session Management
```php
<?php
// Always check authentication before sensitive operations
if (!isStudentLoggedIn()) {
    redirectToLogin('student');
}

if (!isAdminLoggedIn()) {
    redirectToLogin('admin');
}

// Get user ID safely
$userId = getStudentId();
$adminId = getAdminId();
```

---

## Security Best Practices

### Input Validation
```php
<?php
// Validate email
if (!isValidEmail($email)) {
    // Handle error
}

// Validate required fields
if (!validateRequired(['name', 'email'], $_POST)) {
    // Handle error
}

// Validate string length
if (strlen($input) < 3 || strlen($input) > 100) {
    // Handle error
}
```

### Output Escaping
```php
<?php
// Always escape user input before output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// Use alert functions
echo successAlert($message);
echo errorAlert($message);
echo warningAlert($message);
```

### Password Security
```php
<?php
// Hash passwords (for new code)
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// Verify hashed passwords
if (password_verify($password, $hashedPassword)) {
    // Password is correct
}

// Check password strength
if (UserValidator::isPasswordStrong($password)) {
    // Password meets requirements
}
```

### SQL Injection Prevention
```php
<?php
// GOOD - Use prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// AVOID - Direct query construction
$query = "SELECT * FROM users WHERE id = " . $_GET['id'];
$result = $conn->query($query);
```

---

## Testing Checklist

### Functionality
- [ ] All form submissions work correctly
- [ ] Authentication flows are secure
- [ ] Database operations succeed
- [ ] Error handling displays proper messages
- [ ] Redirects work as expected

### Security
- [ ] SQL injection attempts are prevented
- [ ] XSS vulnerability is mitigated
- [ ] CSRF tokens are in place
- [ ] Session handling is secure
- [ ] Passwords are properly hashed

### Responsiveness
- [ ] Mobile (< 576px)
- [ ] Tablet (576px - 992px)
- [ ] Desktop (> 992px)
- [ ] All images are responsive

### Performance
- [ ] Page load time < 3 seconds
- [ ] CSS and JS are minified
- [ ] Images are optimized
- [ ] Database queries are efficient

### Accessibility
- [ ] Keyboard navigation works
- [ ] Color contrast is adequate
- [ ] Alt text on images
- [ ] Proper heading hierarchy

---

## Git Commit Messages

### Format
```
[TYPE] Brief description (50 chars max)

Longer explanation of what was done
and why it was necessary.

Fixes #123
```

### Types
- `[FEAT]` - New feature
- `[FIX]` - Bug fix
- `[DOCS]` - Documentation
- `[STYLE]` - Code style (no logic change)
- `[REFACTOR]` - Code restructuring
- `[PERF]` - Performance improvement
- `[SECURITY]` - Security fix

### Examples
```
[FEAT] Add user profile page

[FIX] Correct login validation error

[DOCS] Update installation instructions

[SECURITY] Add input validation to form
```

---

## Performance Guidelines

### Optimization
- Minimize HTTP requests
- Use CSS variables instead of inline styles
- Lazy load images
- Cache static assets
- Use CDN for external libraries

### Database
- Use indexes on frequently queried columns
- Avoid N+1 queries
- Use prepared statements
- Cache query results when appropriate

### Code
- Remove unused code
- Combine similar functions
- Use constants for repeated values
- Avoid deeply nested conditions

---

## Documentation

### README Requirements
- Project purpose
- Installation instructions
- Configuration steps
- Usage examples
- Troubleshooting guide

### Code Documentation
- Function descriptions
- Parameter explanations
- Return value documentation
- Usage examples for complex functions

### API Documentation
- Endpoint descriptions
- Parameter requirements
- Response formats
- Error codes

---

## Version Control

### Branch Naming
```
feature/user-authentication
feature/typing-test-ui
bugfix/login-validation
docs/installation-guide
security/password-hashing
```

### Commit Frequency
- Commit frequently (every logical unit)
- Keep commits focused and small
- Write meaningful messages
- Never commit incomplete work

---

## Code Review Checklist

- [ ] Code follows standards
- [ ] No hardcoded values
- [ ] Proper error handling
- [ ] Security considerations addressed
- [ ] Comments explain complex logic
- [ ] Tests pass
- [ ] No console errors/warnings
- [ ] Performance is acceptable
- [ ] Accessibility is maintained
- [ ] Documentation updated

---

**Last Updated:** March 2026
