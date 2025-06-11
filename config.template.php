<?php
// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1); // Enable for HTTPS
    ini_set('session.cookie_samesite', 'Strict');
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');

// Application configuration
define('BASE_URL', 'http://your-domain.com');
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
?> 