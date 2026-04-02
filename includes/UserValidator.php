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
        $this->errors = [];
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
        $storedPassword = $user['password'] ?? '';

        if ($userType === 'admin') {
            if (!password_verify($password, $storedPassword)) {
                $this->errors[] = "Invalid password";
                return false;
            }
        } else {
            $isValidPassword = false;

            if (!empty($storedPassword) && password_get_info($storedPassword)['algo'] !== null) {
                $isValidPassword = password_verify($password, $storedPassword);
            } else {
                $isValidPassword = hash_equals((string)$storedPassword, (string)$password);

                if ($isValidPassword) {
                    $this->upgradeLegacyStudentPassword((int)$user['id'], $password);
                    $user['password'] = $this->getStoredPasswordHash((int)$user['id']) ?: $storedPassword;
                }
            }

            if (!$isValidPassword) {
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
        $required = ['name', 'email', 'password'];
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

        if (!self::isPasswordStrong($data['password'])) {
            $this->errors[] = "Password must include uppercase, lowercase, and a number";
            return false;
        }

        // Confirm password
        if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
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
        if (empty($user)) {
            $this->errors[] = "Account not found";
            return false;
        }

        $status = strtolower(trim((string) ($user['status'] ?? '')));

        if ($status !== '' && !in_array($status, ['0', '1', 'active', 'inactive'], true)) {
            $this->errors[] = "Your account is disabled";
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
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $studentStatus = function_exists('getStudentStatusValue')
            ? getStudentStatusValue($this->conn, false)
            : 0;
        $statusPlaceholder = is_int($studentStatus) ? (string) $studentStatus : "'" . $this->conn->real_escape_string($studentStatus) . "'";

        $stmt = $this->conn->prepare(
            "INSERT INTO students (name, email, password, status, created_at) 
             VALUES (?, ?, ?, {$statusPlaceholder}, NOW())"
        );

        if (!$stmt) {
            $this->errors[] = "Database error";
            return false;
        }

        $stmt->bind_param("sss", $name, $email, $passwordHash);
        
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

    /**
     * Upgrade legacy plaintext student passwords after successful login
     *
     * @param int $userId
     * @param string $password
     * @return void
     */
    private function upgradeLegacyStudentPassword($userId, $password) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE students SET password = ? WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("si", $newHash, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Read the latest stored hash for a student
     *
     * @param int $userId
     * @return string|null
     */
    private function getStoredPasswordHash($userId) {
        $stmt = $this->conn->prepare("SELECT password FROM students WHERE id = ?");

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['password'] ?? null;
    }
}

?>
