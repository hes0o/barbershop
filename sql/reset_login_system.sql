-- Drop existing tables
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS barber_availability;
DROP TABLE IF EXISTS working_hours;
DROP TABLE IF EXISTS barbers;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS users;

-- Create users table with improved structure
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'barber', 'customer') NOT NULL,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create login_attempts table with improved structure
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME NOT NULL,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_email_time (email, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create barbers table
CREATE TABLE barbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    experience_years INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create working_hours table
CREATE TABLE working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NOT NULL,
    open_time TIME NOT NULL,
    close_time TIME NOT NULL,
    is_working BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create barber_availability table
CREATE TABLE barber_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barber_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    barber_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (barber_id) REFERENCES barbers(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
INSERT INTO users (username, email, password, role, status) 
VALUES ('admin', 'admin@barbershop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert default barber user
INSERT INTO users (username, email, password, role, status) 
VALUES ('barber', 'barber@barbershop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber', 'active');

-- Insert barber record
INSERT INTO barbers (user_id, bio, experience_years) 
SELECT id, 'Professional barber with 5 years of experience', 5 
FROM users WHERE email = 'barber@barbershop.com';

-- Insert default working hours
INSERT INTO working_hours (day_of_week, open_time, close_time, is_working) VALUES
(1, '09:00:00', '19:00:00', 1), -- Monday
(2, '09:00:00', '19:00:00', 1), -- Tuesday
(3, '09:00:00', '19:00:00', 1), -- Wednesday
(4, '09:00:00', '19:00:00', 1), -- Thursday
(5, '09:00:00', '19:00:00', 1), -- Friday
(6, '09:00:00', '17:00:00', 1), -- Saturday
(7, '00:00:00', '00:00:00', 0); -- Sunday (closed)

-- Insert default services
INSERT INTO services (name, description, price, duration) VALUES
('Classic Haircut', 'Traditional men\'s haircut with scissors and clippers', 25.00, 30),
('Beard Trim', 'Professional beard shaping and trimming', 15.00, 20),
('Hot Towel Shave', 'Classic straight razor shave with hot towel treatment', 30.00, 45),
('Hair & Beard Combo', 'Complete haircut and beard trim package', 35.00, 45),
('Kids Haircut', 'Haircut for children under 12', 20.00, 25); 