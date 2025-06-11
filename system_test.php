<?php
// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config.php';
require_once 'includes/db.php';

// Start session for testing
session_start();

// Function to log test results
function test_result($test_name, $result, $message = '') {
    $status = $result ? '✓ PASS' : '✗ FAIL';
    $color = $result ? 'success' : 'danger';
    echo "<div class='alert alert-{$color}'>";
    echo "<strong>{$test_name}:</strong> {$status}<br>";
    if ($message) {
        echo "<small>{$message}</small>";
    }
    echo "</div>";
}

// Function to log debug information
function debug_log($message, $data = null) {
    echo "<div class='alert alert-info'>";
    echo "<strong>Debug:</strong> {$message}<br>";
    if ($data !== null) {
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
    echo "</div>";
}

// HTML Header
echo "<!DOCTYPE html>
<html>
<head>
    <title>System Test Results</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        .alert { margin-bottom: 10px; }
        pre { margin: 5px 0; }
    </style>
</head>
<body>
    <h1>System Test Results</h1>";

try {
    // 1. Test PHP Configuration
    echo "<h2>1. PHP Configuration Test</h2>";
    test_result("PHP Version", version_compare(PHP_VERSION, '7.4.0', '>='), "Current version: " . PHP_VERSION);
    test_result("Error Reporting", error_reporting() === E_ALL, "Error reporting level: " . error_reporting());
    test_result("Display Errors", ini_get('display_errors') === '1', "Display errors setting: " . ini_get('display_errors'));
    test_result("Session Support", session_status() === PHP_SESSION_ACTIVE, "Session status: " . session_status());

    // 2. Test Database Connection
    echo "<h2>2. Database Connection Test</h2>";
    $db = new Database();
    test_result("Database Connection", $db->getConnection() !== null, "Connection established successfully");

    // 3. Test Database Tables
    echo "<h2>3. Database Tables Test</h2>";
    $required_tables = ['users', 'services', 'barbers', 'working_hours', 'barber_availability', 'appointments'];
    $connection = $db->getConnection();
    
    foreach ($required_tables as $table) {
        $result = $connection->query("SHOW TABLES LIKE '$table'");
        test_result("Table '$table' exists", $result->num_rows > 0);
    }

    // 4. Test Users Table Structure
    echo "<h2>4. Users Table Structure Test</h2>";
    $result = $connection->query("DESCRIBE users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row['Type'];
    }
    
    $required_columns = [
        'id' => 'int',
        'username' => 'varchar',
        'email' => 'varchar',
        'password' => 'varchar',
        'role' => 'varchar',
        'phone' => 'varchar'
    ];

    foreach ($required_columns as $column => $type) {
        $exists = isset($columns[$column]);
        $correct_type = $exists && strpos($columns[$column], $type) !== false;
        test_result("Column '$column'", $exists && $correct_type, 
            $exists ? "Type: " . $columns[$column] : "Column not found");
    }

    // 5. Test Default Data
    echo "<h2>5. Default Data Test</h2>";
    
    // Check admin user
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND role = 'admin'");
    $admin_email = 'admin@barbershop.com';
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $stmt->bind_result($admin_id);
    $admin_exists = $stmt->fetch();
    $stmt->close();
    test_result("Admin user exists", $admin_exists, "Admin email: $admin_email");

    // Check default barber
    $stmt = $connection->prepare("SELECT id FROM users WHERE email = ? AND role = 'barber'");
    $barber_email = 'barber@barbershop.com';
    $stmt->bind_param("s", $barber_email);
    $stmt->execute();
    $stmt->bind_result($barber_id);
    $barber_exists = $stmt->fetch();
    $stmt->close();
    test_result("Default barber exists", $barber_exists, "Barber email: $barber_email");

    // Check services
    $result = $connection->query("SELECT COUNT(*) as count FROM services");
    $row = $result->fetch_assoc();
    test_result("Default services exist", $row['count'] > 0, "Number of services: " . $row['count']);

    // 6. Test Database Functions
    echo "<h2>6. Database Functions Test</h2>";
    
    // Test user creation
    $test_user = [
        'username' => 'testuser_' . time(),
        'email' => 'test_' . time() . '@test.com',
        'password' => 'testpass123',
        'role' => 'customer',
        'phone' => '1234567890'
    ];
    
    $user_id = $db->createUser($test_user);
    test_result("User creation", $user_id !== false, "Created user ID: " . $user_id);

    // Test user retrieval
    $retrieved_user = $db->getUserByEmail($test_user['email']);
    test_result("User retrieval", $retrieved_user !== null, 
        $retrieved_user ? "Retrieved user: " . $retrieved_user['username'] : "User not found");

    // Test password verification
    $password_verified = password_verify($test_user['password'], $retrieved_user['password']);
    test_result("Password verification", $password_verified, "Password verification test");

    // Clean up test user
    $connection->query("DELETE FROM users WHERE id = $user_id");
    test_result("Test user cleanup", true, "Removed test user");

    // 7. Test Session Configuration
    echo "<h2>7. Session Configuration Test</h2>";
    test_result("Session cookie parameters", 
        session_get_cookie_params()['httponly'] === true, 
        "Session cookie is HTTP-only");
    test_result("Session save path", 
        is_writable(session_save_path()), 
        "Session save path: " . session_save_path());

    // 8. System Information
    echo "<h2>8. System Information</h2>";
    echo "<div class='alert alert-info'>";
    echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
    echo "<strong>MySQL Version:</strong> " . $connection->server_info . "<br>";
    echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
    echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    echo "<strong>Current Directory:</strong> " . getcwd() . "<br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Error:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

echo "</body></html>";
?> 