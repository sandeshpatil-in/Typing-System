<?php
/**
 * ============================================
 * User Validator Class
 * ============================================
 * 
 * Handles user validation, authentication
 * and authorization operations
 */

class UserValidator {
    
    private $conn;
    private $errors = [];

    /**
     * Constructor
     * 
     * @param mysqli $connection Database connection
     */
    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Validate login credentials
     * 
     * @param string $email User email
     * @param string $password User password
     * @param string $userType 'student' or 'admin'
     * @return array|false User data or false
     */
    public function validateLogin($email, $password, $userType = 'student') {
        $email = sanitizeInput($email);
        
        // Email validation
        if (!isValidEmail($email)) {
            $this->errors[] = "Invalid email format";
            return false;
        }

        // Table selection based on user type
        $table = ($userType === 'admin') ? 'admins' : 'students';
        $query = ($userType === 'admin') 
            ? "SELECT * FROM {$table} WHERE username = ?"
            : "SELECT * FROM {$table} WHERE email = ?";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            $this->errors[] = "Database error";
            return false;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $this->errors[] = "User not found";
            return false;
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password
        if ($userType === 'admin') {
            // Use password_verify for admin (hashed passwords)
            if (!password_verify($password, $user['password'])) {
                $this->errors[] = "Invalid password";
                return false;
            }
        } else {
            // Direct comparison for students (if plaintext)
            if ($user['password'] !== $password) {
                $this->errors[] = "Invalid password";
                return false;
            }
        }

        return $user;
    }

    /**
     * Validate registration data
     * 
     * @param array $data User registration data
     * @return bool True if valid
     */
    public function validateRegistration($data) {
        $this->errors = [];

        // Validate required fields
        $required = ['name', 'email', 'password', 'confirm_password'];
        if (!validateRequired($required, $data)) {
            $this->errors[] = "All fields are required";
            return false;
        }

        // Name validation
        if (strlen($data['name']) < 3) {
            $this->errors[] = "Name must be at least 3 characters";
            return false;
        }

        if (strlen($data['name']) > 100) {
            $this->errors[] = "Name cannot exceed 100 characters";
            return false;
        }

        // Email validation
        if (!isValidEmail($data['email'])) {
            $this->errors[] = "Invalid email format";
            return false;
        }

        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            $this->errors[] = "Email already registered";
            return false;
        }

        // Password validation
        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $this->errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
            return false;
        }

        // Confirm password
        if ($data['password'] !== $data['confirm_password']) {
            $this->errors[] = "Passwords do not match";
            return false;
        }

        return true;
    }

    /**
     * Check if email already exists
     * 
     * @param string $email Email to check
     * @return bool True if exists
     */
    private function emailExists($email) {
        $email = sanitizeInput($email);
        $stmt = $this->conn->prepare("SELECT id FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->num_rows > 0;
    }

    /**
     * Validate student account status
     * 
     * @param array $user User data
     * @return bool True if account is valid
     */
    public function validateStudentStatus($user) {
        // Check if account is activated
        if ($user['status'] == 0) {
            $this->errors[] = "Your account has not been activated by admin";
            return false;
        }

        // Check if subscription expired
        if (strtotime($user['expiry_date']) < time()) {
            $this->errors[] = "Your subscription has expired. Please renew your plan.";
            return false;
        }

        return true;
    }

    /**
     * Get validation errors
     * 
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get first error message
     * 
     * @return string First error message
     */
    public function getErrorMessage() {
        return !empty($this->errors) ? reset($this->errors) : '';
    }

    /**
     * Register new student
     * 
     * @param array $data Student data
     * @return bool True on success
     */
    public function registerStudent($data) {
        if (!$this->validateRegistration($data)) {
            return false;
        }

        $name = sanitizeInput($data['name']);
        $email = sanitizeInput($data['email']);
        $password = sanitizeInput($data['password']);

        $stmt = $this->conn->prepare(
            "INSERT INTO students (name, email, password, status, created_at) 
             VALUES (?, ?, ?, 0, NOW())"
        );

        if (!$stmt) {
            $this->errors[] = "Database error";
            return false;
        }

        $stmt->bind_param("sss", $name, $email, $password);
        
        if (!$stmt->execute()) {
            $this->errors[] = "Registration failed";
            $stmt->close();
            return false;
        }

        $stmt->close();
        return true;
    }

    /**
     * Validate password strength
     * 
     * @param string $password Password to validate
     * @return bool True if password is strong
     */
    public static function isPasswordStrong($password) {
        // At least one uppercase, one lowercase, one number
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        
        return $hasUpper && $hasLower && $hasNumber;
    }
}

?>
