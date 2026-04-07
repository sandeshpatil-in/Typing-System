CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    contact_number VARCHAR(20) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT 0,
    plan_start_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    razorpay_order_id VARCHAR(100) DEFAULT NULL,
    razorpay_payment_id VARCHAR(100) DEFAULT NULL,
    razorpay_signature VARCHAR(255) DEFAULT NULL,
    payment_status ENUM('created','paid','failed') NOT NULL DEFAULT 'created',
    payment_method ENUM('razorpay','hand_cash') DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_plans_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_plans_student_status (student_id, payment_status),
    UNIQUE KEY uq_razorpay_order (razorpay_order_id)
);

CREATE TABLE IF NOT EXISTS test_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT NULL,
    guest_session_id VARCHAR(64) DEFAULT NULL,
    language VARCHAR(50) NOT NULL,
    exam_type VARCHAR(50) NOT NULL,
    paragraph_id INT DEFAULT NULL,
    time_limit_seconds INT NOT NULL,
    wpm DECIMAL(8,2) NOT NULL DEFAULT 0,
    accuracy DECIMAL(5,2) NOT NULL DEFAULT 0,
    typed_words INT NOT NULL DEFAULT 0,
    access_type ENUM('guest','paid') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attempts_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL,
    INDEX idx_attempts_student (student_id),
    INDEX idx_attempts_guest (guest_session_id)
);
