CREATE TABLE IF NOT EXISTS student_password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME DEFAULT NULL,
    requested_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_password_resets_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_password_reset_token_hash (token_hash),
    KEY idx_student_password_reset_student (student_id),
    KEY idx_student_password_reset_expires (expires_at)
);
