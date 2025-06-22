<?php
// Database configuration for live hosting
define('DB_HOST', 'localhost');
define('DB_USER', 'shawacom_barber'); // Your actual database username
define('DB_PASS', 'Hes0o@981'); // Your actual database password
define('DB_NAME', 'shawacom_barber'); // Your actual database name

// Application configuration
define('SITE_NAME', 'BladeX Barbershop');
define('SITE_URL', 'https://customprojects.shawa.com.tr/barbershop');
define('ADMIN_EMAIL', 'bladex@customprojects.shawa.com.tr'); // Your admin email

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Error reporting - Set to 0 in production
error_reporting(0);
ini_set('display_errors', 0);

// Time zone
date_default_timezone_set('Europe/Istanbul');

// Security
define('HASH_COST', 12); // For password hashing
define('TOKEN_EXPIRY', 3600); // 1 hour in seconds

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
} 