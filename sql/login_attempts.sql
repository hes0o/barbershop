CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    attempt_time DATETIME NOT NULL,
    INDEX idx_identifier_time (identifier, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 