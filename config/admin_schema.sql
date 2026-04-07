CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create the first admin manually after generating a password hash.
-- Example:
-- INSERT INTO admins (username, password)
-- VALUES ('admin', '$2y$10$replace_this_with_a_real_password_hash');
