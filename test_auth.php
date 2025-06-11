<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Authentication System</h2>";
echo "<pre>";

function debug_log($message, $data = null) {
    echo "\n[DEBUG] " . $message;
    if ($data !== null) {
        echo "\nData: " . print_r($data, true);
    }
    echo "\n";
}

try {
    debug_log("Starting database connection test");
    $db = new Database();
    debug_log("Database connection successful");
    
    // Test user credentials
    $test_user = [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Test123!',
        'role' => 'customer',
        'phone' => '1234567890'
    ];
    
    echo "\n<h3>1. Testing User Registration</h3>";
    
    // Check if user already exists
    debug_log("Checking if user exists by email", $test_user['email']);
    $existing_user = $db->getUserByEmail($test_user['email']);
    debug_log("getUserByEmail result", $existing_user);
    
    if ($existing_user) {
        echo "Test user already exists. Skipping registration.\n";
    } else {
        debug_log("Creating new test user", $test_user);
        // Create test user
        $result = $db->createUser(
            $test_user['username'],
            $test_user['email'],
            $test_user['password'],
            $test_user['role'],
            $test_user['phone']
        );
        
        debug_log("createUser result", $result);
        
        if ($result) {
            echo "✓ Test user created successfully\n";
        } else {
            echo "✗ Failed to create test user\n";
        }
    }
    
    echo "\n<h3>2. Testing User Authentication</h3>";
    
    // Test correct credentials
    echo "Testing with correct credentials:\n";
    debug_log("Attempting authentication with correct credentials", [
        'email' => $test_user['email'],
        'role' => $test_user['role']
    ]);
    
    $auth_result = $db->authenticateUser($test_user['email'], $test_user['password'], $test_user['role']);
    debug_log("Authentication result", $auth_result);
    
    if ($auth_result) {
        echo "✓ Authentication successful with correct credentials\n";
        echo "User data: " . print_r($auth_result, true) . "\n";
    } else {
        echo "✗ Authentication failed with correct credentials\n";
    }
    
    // Test incorrect password
    echo "\nTesting with incorrect password:\n";
    debug_log("Attempting authentication with incorrect password");
    $auth_result = $db->authenticateUser($test_user['email'], 'wrongpassword', $test_user['role']);
    debug_log("Authentication result with wrong password", $auth_result);
    
    if ($auth_result) {
        echo "✗ Authentication succeeded with incorrect password (this is wrong!)\n";
    } else {
        echo "✓ Authentication correctly failed with incorrect password\n";
    }
    
    // Test incorrect email
    echo "\nTesting with incorrect email:\n";
    debug_log("Attempting authentication with incorrect email");
    $auth_result = $db->authenticateUser('wrong@email.com', $test_user['password'], $test_user['role']);
    debug_log("Authentication result with wrong email", $auth_result);
    
    if ($auth_result) {
        echo "✗ Authentication succeeded with incorrect email (this is wrong!)\n";
    } else {
        echo "✓ Authentication correctly failed with incorrect email\n";
    }
    
    // Test incorrect role
    echo "\nTesting with incorrect role:\n";
    debug_log("Attempting authentication with incorrect role");
    $auth_result = $db->authenticateUser($test_user['email'], $test_user['password'], 'barber');
    debug_log("Authentication result with wrong role", $auth_result);
    
    if ($auth_result) {
        echo "✗ Authentication succeeded with incorrect role (this is wrong!)\n";
    } else {
        echo "✓ Authentication correctly failed with incorrect role\n";
    }
    
    echo "\n<h3>3. Testing User Retrieval</h3>";
    
    // Test getUserByEmail
    echo "Testing getUserByEmail:\n";
    debug_log("Attempting to get user by email", $test_user['email']);
    $user = $db->getUserByEmail($test_user['email']);
    debug_log("getUserByEmail result", $user);
    
    if ($user) {
        echo "✓ Successfully retrieved user by email\n";
        echo "User data: " . print_r($user, true) . "\n";
    } else {
        echo "✗ Failed to retrieve user by email\n";
    }
    
    // Test getUserByUsername
    echo "\nTesting getUserByUsername:\n";
    debug_log("Attempting to get user by username", $test_user['username']);
    $user = $db->getUserByUsername($test_user['username']);
    debug_log("getUserByUsername result", $user);
    
    if ($user) {
        echo "✓ Successfully retrieved user by username\n";
        echo "User data: " . print_r($user, true) . "\n";
    } else {
        echo "✗ Failed to retrieve user by username\n";
    }
    
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Additional error information
    echo "\nPHP Version: " . PHP_VERSION . "\n";
    echo "MySQL Version: " . $db->getConnection()->server_info . "\n";
    echo "Error reporting level: " . error_reporting() . "\n";
    echo "Display errors: " . ini_get('display_errors') . "\n";
    echo "Log errors: " . ini_get('log_errors') . "\n";
    echo "Error log: " . ini_get('error_log') . "\n";
}

echo "</pre>";
?> 