<?php
/**
 * ============================================
 * Database Connection Handler
 * ============================================
 * 
 * Professional database connectivity
 * Error handling and security
 */

require_once __DIR__ . '/constants.php';

class DatabaseConnection {
    private static $instance = null;
    private $connection = null;
    private $lastError = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * Get singleton instance
     * 
     * @return DatabaseConnection Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     * 
     * @return void
     */
    private function connect() {
        try {
            $this->connection = @mysqli_connect(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            if (!$this->connection) {
                throw new Exception("Database Connection Failed: " . mysqli_connect_error());
            }

            // Set charset
            if (!mysqli_set_charset($this->connection, DB_CHARSET)) {
                throw new Exception("Failed to set database charset");
            }

        } catch (Exception $e) {
            logError($e->getMessage(), 'DATABASE');
            handleError("Database connection error", 500);
        }
    }

    /**
     * Get database connection object
     * 
     * @return mysqli Connection instance
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Execute query with error handling
     * 
     * @param string $query SQL query
     * @return mysqli_result|bool Query result
     */
    public function executeQuery($query) {
        try {
            $result = $this->connection->query($query);
            
            if (!$result) {
                throw new Exception("Query Failed: " . $this->connection->error);
            }

            return $result;

        } catch (Exception $e) {
            logError($e->getMessage(), 'QUERY');
            return false;
        }
    }

    /**
     * Prepare statement for safe queries
     * 
     * @param string $query SQL query
     * @return mysqli_stmt|bool Prepared statement
     */
    public function prepare($query) {
        try {
            $stmt = $this->connection->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare Failed: " . $this->connection->error);
            }

            return $stmt;

        } catch (Exception $e) {
            logError($e->getMessage(), 'PREPARE');
            return false;
        }
    }

    /**
     * Get last error
     * 
     * @return string Last error message
     */
    public function getLastError() {
        return $this->connection->error;
    }

    /**
     * Close database connection
     * 
     * @return void
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Create global database connection variable for backward compatibility
$conn = DatabaseConnection::getInstance()->getConnection();

// Ensure connection is closed on script exit
register_shutdown_function(function() {
    DatabaseConnection::getInstance()->close();
});

?>
