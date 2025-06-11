<?php
// Session configuration - must be before session_start()
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Enable for HTTPS
    ini_set('session.cookie_samesite', 'Strict');
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'shawacom_hassan');
define('DB_PASS', 'Hassan@Chawa981');
define('DB_NAME', 'shawacom_Barber');

// Application configuration
define('BASE_URL', 'https://customprojects.shawa.com.tr');
define('SITE_NAME', 'BarberShop');

// Error reporting - Set to 0 in production
error_reporting(0);
ini_set('display_errors', 0);

// Time zone
date_default_timezone_set('UTC');

// Create connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("We're experiencing technical difficulties. Please try again later.");
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("We're experiencing technical difficulties. Please try again later.");
}

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'barber', 'customer') NOT NULL,
        phone VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS barbers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        bio TEXT,
        experience_years INT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS working_hours (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week INT NOT NULL,
        day_name VARCHAR(10) NOT NULL,
        open_time TIME NOT NULL,
        close_time TIME NOT NULL,
        is_working TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS barber_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barber_id INT NOT NULL,
        date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (barber_id) REFERENCES barbers(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS appointments (
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
    )"
];

foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        error_log("Error creating table: " . $conn->error);
        die("We're experiencing technical difficulties. Please try again later.");
    }
}

// Insert default admin user if not exists
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password, role) 
        VALUES ('admin', 'admin@barbershop.com', '$admin_password', 'admin')";
$conn->query($sql);

// Insert default barber if not exists
$barber_password = password_hash('barber123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (username, email, password, role) 
        VALUES ('barber', 'barber@barbershop.com', '$barber_password', 'barber')";
$conn->query($sql);

// Get the barber user ID and insert into barbers table
$result = $conn->query("SELECT id FROM users WHERE email = 'barber@barbershop.com'");
if ($barber = $result->fetch_assoc()) {
    $sql = "INSERT IGNORE INTO barbers (user_id, bio, experience_years) 
            VALUES ({$barber['id']}, 'Professional barber with 5 years of experience', 5)";
    $conn->query($sql);
}

// Insert default working hours if not exists
$result = $conn->query("SELECT COUNT(*) as count FROM working_hours");
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    $working_hours = [
        [1, 'Monday', '09:00:00', '19:00:00', 1],
        [2, 'Tuesday', '09:00:00', '19:00:00', 1],
        [3, 'Wednesday', '09:00:00', '19:00:00', 1],
        [4, 'Thursday', '09:00:00', '19:00:00', 1],
        [5, 'Friday', '09:00:00', '19:00:00', 1],
        [6, 'Saturday', '09:00:00', '17:00:00', 1],
        [7, 'Sunday', '00:00:00', '00:00:00', 0]
    ];

    $stmt = $conn->prepare("INSERT INTO working_hours (day_of_week, day_name, open_time, close_time, is_working) VALUES (?, ?, ?, ?, ?)");
    foreach ($working_hours as $hours) {
        $stmt->bind_param("isssi", $hours[0], $hours[1], $hours[2], $hours[3], $hours[4]);
        $stmt->execute();
    }
    $stmt->close();
}

// Insert default services if not exists
$result = $conn->query("SELECT COUNT(*) as count FROM services");
$count = $result->fetch_assoc()['count'];

if ($count == 0) {
    $services = [
        ['Classic Haircut', 'Traditional men\'s haircut with scissors and clippers', 25.00, 30],
        ['Beard Trim', 'Professional beard shaping and trimming', 15.00, 20],
        ['Hot Towel Shave', 'Classic straight razor shave with hot towel treatment', 30.00, 45],
        ['Hair & Beard Combo', 'Complete haircut and beard trim package', 35.00, 45],
        ['Kids Haircut', 'Haircut for children under 12', 20.00, 25]
    ];

    $stmt = $conn->prepare("INSERT INTO services (name, description, price, duration) VALUES (?, ?, ?, ?)");
    foreach ($services as $service) {
        $stmt->bind_param("ssdi", $service[0], $service[1], $service[2], $service[3]);
        $stmt->execute();
    }
    $stmt->close();
}

// Close connection
$conn->close();
?> 