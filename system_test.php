<?php
require_once __DIR__ . '/config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>System Diagnostic Test</h2>";
echo "<pre>";

function test_result($test_name, $result, $details = '') {
    $status = $result ? "✓ PASS" : "✗ FAIL";
    echo "\n[$status] $test_name";
    if ($details) {
        echo "\nDetails: $details";
    }
    echo "\n";
    return $result;
}

function debug_log($message, $data = null) {
    echo "\n[DEBUG] $message";
    if ($data !== null) {
        echo "\nData: " . print_r($data, true);
    }
    echo "\n";
}

try {
    // 1. Test PHP Configuration
    echo "\n=== PHP Configuration Test ===\n";
    test_result("PHP Version Check", version_compare(PHP_VERSION, '7.0.0', '>='), "Current version: " . PHP_VERSION);
    test_result("Error Reporting", error_reporting() === E_ALL, "Current level: " . error_reporting());
    test_result("Display Errors", ini_get('display_errors') === '1', "Current setting: " . ini_get('display_errors'));
    
    // 2. Test Database Connection
    echo "\n=== Database Connection Test ===\n";
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    test_result("Database Connection", !$conn->connect_error, $conn->connect_error ?: "Connected successfully");
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // 3. Test Database Tables
    echo "\n=== Database Tables Test ===\n";
    $required_tables = [
        'users',
        'services',
        'barbers',
        'working_hours',
        'barber_availability',
        'appointments'
    ];
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        test_result("Table '$table' exists", $result->num_rows > 0);
    }
    
    // 4. Test Users Table Structure
    echo "\n=== Users Table Structure Test ===\n";
    $result = $conn->query("DESCRIBE users");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = $row;
    }
    
    $required_columns = [
        'id' => ['Type' => 'int', 'Null' => 'NO'],
        'username' => ['Type' => 'varchar', 'Null' => 'NO'],
        'email' => ['Type' => 'varchar', 'Null' => 'NO'],
        'password' => ['Type' => 'varchar', 'Null' => 'NO'],
        'role' => ['Type' => 'enum', 'Null' => 'NO'],
        'phone' => ['Type' => 'varchar', 'Null' => 'YES']
    ];
    
    foreach ($required_columns as $column => $requirements) {
        $exists = isset($columns[$column]);
        $details = $exists ? "Found: " . $columns[$column]['Type'] : "Not found";
        test_result("Column '$column' exists", $exists, $details);
    }
    
    // 5. Test Default Data
    echo "\n=== Default Data Test ===\n";
    
    // Check for admin user
    $result = $conn->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
    test_result("Admin user exists", $result->num_rows > 0);
    
    // Check for default barber
    $result = $conn->query("SELECT * FROM users WHERE role = 'barber' LIMIT 1");
    test_result("Default barber exists", $result->num_rows > 0);
    
    // Check for services
    $result = $conn->query("SELECT * FROM services LIMIT 1");
    test_result("Default services exist", $result->num_rows > 0);
    
    // 6. Test Database Functions
    echo "\n=== Database Functions Test ===\n";
    
    // Test user creation
    $test_user = [
        'username' => 'testuser_' . time(),
        'email' => 'test_' . time() . '@example.com',
        'password' => 'Test123!',
        'role' => 'customer',
        'phone' => '1234567890'
    ];
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
    $hashed_password = password_hash($test_user['password'], PASSWORD_DEFAULT);
    $stmt->bind_param("sssss", 
        $test_user['username'],
        $test_user['email'],
        $hashed_password,
        $test_user['role'],
        $test_user['phone']
    );
    
    $create_result = $stmt->execute();
    test_result("User creation", $create_result, $create_result ? "Created test user" : $stmt->error);
    
    if ($create_result) {
        // Test user retrieval
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $test_user['email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        test_result("User retrieval", $user !== null, $user ? "Found user" : "User not found");
        
        // Test password verification
        if ($user) {
            $password_verify = password_verify($test_user['password'], $user['password']);
            test_result("Password verification", $password_verify, $password_verify ? "Password verified" : "Password verification failed");
        }
        
        // Clean up test user
        $stmt = $conn->prepare("DELETE FROM users WHERE email = ?");
        $stmt->bind_param("s", $test_user['email']);
        $stmt->execute();
    }
    
    // 7. Test Session Configuration
    echo "\n=== Session Configuration Test ===\n";
    test_result("Session cookie parameters", 
        ini_get('session.cookie_httponly') === '1' && 
        ini_get('session.use_only_cookies') === '1',
        "Current settings: " . 
        "httponly=" . ini_get('session.cookie_httponly') . ", " .
        "use_only_cookies=" . ini_get('session.use_only_cookies')
    );
    
    // 8. System Information
    echo "\n=== System Information ===\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "MySQL Version: " . $conn->server_info . "\n";
    echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
    echo "Current Directory: " . __DIR__ . "\n";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?> 