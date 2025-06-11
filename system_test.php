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
        .test-section { margin-bottom: 30px; }
    </style>
</head>
<body>
    <h1>System Test Results</h1>";

try {
    // 1. Test PHP Configuration
    echo "<div class='test-section'>";
    echo "<h2>1. PHP Configuration Test</h2>";
    test_result("PHP Version", version_compare(PHP_VERSION, '7.4.0', '>='), "Current version: " . PHP_VERSION);
    test_result("Error Reporting", error_reporting() === E_ALL, "Error reporting level: " . error_reporting());
    test_result("Display Errors", ini_get('display_errors') === '1', "Display errors setting: " . ini_get('display_errors'));
    test_result("Session Support", session_status() === PHP_SESSION_ACTIVE, "Session status: " . session_status());
    test_result("MySQLi Extension", extension_loaded('mysqli'), "MySQLi extension loaded");
    test_result("PDO Extension", extension_loaded('pdo'), "PDO extension loaded");
    test_result("JSON Extension", extension_loaded('json'), "JSON extension loaded");
    test_result("File Upload", ini_get('file_uploads') === '1', "File uploads enabled");
    test_result("Max Upload Size", ini_get('upload_max_filesize'), "Max upload size: " . ini_get('upload_max_filesize'));
    echo "</div>";

    // 2. Test Database Connection
    echo "<div class='test-section'>";
    echo "<h2>2. Database Connection Test</h2>";
    $db = new Database();
    test_result("Database Connection", $db->getConnection() !== null, "Connection established successfully");
    test_result("Database Character Set", $db->getConnection()->character_set_name() === 'utf8mb4', 
        "Current charset: " . $db->getConnection()->character_set_name());
    test_result("Database Timezone", $db->getConnection()->query("SELECT @@time_zone")->fetch_row()[0] === 'SYSTEM', 
        "Timezone setting: " . $db->getConnection()->query("SELECT @@time_zone")->fetch_row()[0]);
    echo "</div>";

    // 3. Test Database Tables
    echo "<div class='test-section'>";
    echo "<h2>3. Database Tables Test</h2>";
    $required_tables = [
        'users' => ['id', 'username', 'email', 'password', 'role', 'phone'],
        'services' => ['id', 'name', 'price', 'duration', 'description'],
        'barbers' => ['id', 'user_id', 'bio', 'experience_years', 'status'],
        'working_hours' => ['id', 'barber_id', 'day_of_week', 'start_time', 'end_time'],
        'barber_availability' => ['id', 'barber_id', 'date', 'is_available'],
        'appointments' => ['id', 'user_id', 'barber_id', 'service_id', 'appointment_date', 'appointment_time', 'status']
    ];
    
    $connection = $db->getConnection();
    
    foreach ($required_tables as $table => $columns) {
        $result = $connection->query("SHOW TABLES LIKE '$table'");
        test_result("Table '$table' exists", $result->num_rows > 0);
        
        if ($result->num_rows > 0) {
            $table_columns = [];
            $result = $connection->query("SHOW COLUMNS FROM $table");
            while ($row = $result->fetch_assoc()) {
                $table_columns[] = $row['Field'];
            }
            
            foreach ($columns as $column) {
                test_result("Column '$column' in '$table'", in_array($column, $table_columns));
            }
        }
    }
    echo "</div>";

    // 4. Test Authentication System
    echo "<div class='test-section'>";
    echo "<h2>4. Authentication System Test</h2>";
    
    // Test user creation
    $test_username = 'testuser_' . time();
    $test_email = 'test_' . time() . '@test.com';
    $test_password = 'testpass123';
    $test_role = 'customer';
    $test_phone = '1234567890';
    
    $user_id = $db->createUser($test_username, $test_email, $test_password, $test_role, $test_phone);
    test_result("User creation", $user_id !== false, "Created user ID: " . $user_id);

    // Test user retrieval
    $retrieved_user = $db->getUserByEmail($test_email);
    test_result("User retrieval by email", $retrieved_user !== null, 
        $retrieved_user ? "Retrieved user: " . $retrieved_user['username'] : "User not found");

    // Test password verification
    $password_verified = password_verify($test_password, $retrieved_user['password']);
    test_result("Password verification", $password_verified, "Password verification test");

    // Test user update
    $new_phone = '9876543210';
    $update_result = $db->updateUser($user_id, $test_username, $test_email, $new_phone);
    test_result("User update", $update_result, "Updated user phone number");

    // Test user deletion
    $delete_result = $db->deleteUser($user_id);
    test_result("User deletion", $delete_result, "Deleted test user");

    // Test invalid login attempts
    $invalid_login = $db->authenticateUser('nonexistent@test.com', 'wrongpass', 'customer');
    test_result("Invalid login rejection", $invalid_login === false, "Invalid credentials rejected");
    echo "</div>";

    // 5. Test Appointment System
    echo "<div class='test-section'>";
    echo "<h2>5. Appointment System Test</h2>";
    
    // Create test users for appointment testing
    $customer_id = $db->createUser('test_customer', 'customer@test.com', 'testpass', 'customer', '1234567890');
    $barber_id = $db->createUser('test_barber', 'barber@test.com', 'testpass', 'barber', '9876543210');
    
    // Create test service
    $service_id = $db->createService('Test Service', 50.00, 30, 'Test service description');
    test_result("Service creation", $service_id !== false, "Created service ID: " . $service_id);

    // Test appointment creation
    $appointment_date = date('Y-m-d', strtotime('+1 day'));
    $appointment_time = '10:00:00';
    $appointment_id = $db->createAppointment($customer_id, $barber_id, $service_id, $appointment_date, $appointment_time);
    test_result("Appointment creation", $appointment_id !== false, "Created appointment ID: " . $appointment_id);

    // Test appointment retrieval
    $appointment = $db->getAppointmentById($appointment_id);
    test_result("Appointment retrieval", $appointment !== null, "Retrieved appointment details");

    // Test appointment update
    $new_status = 'confirmed';
    $update_result = $db->updateAppointmentStatus($appointment_id, $new_status);
    test_result("Appointment status update", $update_result, "Updated appointment status to: " . $new_status);

    // Test appointment cancellation
    $cancel_result = $db->cancelAppointment($appointment_id);
    test_result("Appointment cancellation", $cancel_result, "Cancelled appointment");

    // Clean up test data
    $db->deleteAppointment($appointment_id);
    $db->deleteService($service_id);
    $db->deleteUser($customer_id);
    $db->deleteUser($barber_id);
    echo "</div>";

    // 6. Test Barber System
    echo "<div class='test-section'>";
    echo "<h2>6. Barber System Test</h2>";
    
    // Create test barber
    $barber_user_id = $db->createUser('test_barber2', 'barber2@test.com', 'testpass', 'barber', '5555555555');
    $barber_profile_id = $db->createBarber($barber_user_id, 'Test Barber Bio', 5);
    test_result("Barber profile creation", $barber_profile_id !== false, "Created barber profile ID: " . $barber_profile_id);

    // Test working hours
    $working_hours_id = $db->setWorkingHours($barber_profile_id, 1, '09:00:00', '17:00:00');
    test_result("Working hours creation", $working_hours_id !== false, "Set working hours for Monday");

    // Test availability
    $availability_date = date('Y-m-d', strtotime('+1 day'));
    $availability_id = $db->setBarberAvailability($barber_profile_id, $availability_date, true);
    test_result("Availability setting", $availability_id !== false, "Set availability for: " . $availability_date);

    // Clean up test data
    $db->deleteBarber($barber_profile_id);
    $db->deleteUser($barber_user_id);
    echo "</div>";

    // 7. Test Session Configuration
    echo "<div class='test-section'>";
    echo "<h2>7. Session Configuration Test</h2>";
    test_result("Session cookie parameters", 
        session_get_cookie_params()['httponly'] === true, 
        "Session cookie is HTTP-only");
    test_result("Session save path", 
        is_writable(session_save_path()), 
        "Session save path: " . session_save_path());
    test_result("Session lifetime", 
        ini_get('session.gc_maxlifetime') >= 1440, 
        "Session lifetime: " . ini_get('session.gc_maxlifetime') . " seconds");
    echo "</div>";

    // 8. System Information
    echo "<div class='test-section'>";
    echo "<h2>8. System Information</h2>";
    echo "<div class='alert alert-info'>";
    echo "<strong>PHP Version:</strong> " . PHP_VERSION . "<br>";
    echo "<strong>MySQL Version:</strong> " . $connection->server_info . "<br>";
    echo "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
    echo "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    echo "<strong>Current Directory:</strong> " . getcwd() . "<br>";
    echo "<strong>Memory Limit:</strong> " . ini_get('memory_limit') . "<br>";
    echo "<strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds<br>";
    echo "<strong>Upload Max Filesize:</strong> " . ini_get('upload_max_filesize') . "<br>";
    echo "<strong>Post Max Size:</strong> " . ini_get('post_max_size') . "<br>";
    echo "</div>";
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