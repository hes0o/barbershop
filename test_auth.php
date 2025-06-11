<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

// Enable error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Authentication System</h2>";

try {
    $db = new Database();
    
    // Test user credentials
    $test_user = [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Test123!',
        'role' => 'customer',
        'phone' => '1234567890'
    ];
    
    echo "<h3>1. Testing User Registration</h3>";
    
    // Check if user already exists
    $existing_user = $db->getUserByEmail($test_user['email']);
    if ($existing_user) {
        echo "Test user already exists. Skipping registration.<br>";
    } else {
        // Create test user
        $result = $db->createUser(
            $test_user['username'],
            $test_user['email'],
            $test_user['password'],
            $test_user['role'],
            $test_user['phone']
        );
        
        if ($result) {
            echo "✓ Test user created successfully<br>";
        } else {
            echo "✗ Failed to create test user<br>";
        }
    }
    
    echo "<h3>2. Testing User Authentication</h3>";
    
    // Test correct credentials
    echo "Testing with correct credentials:<br>";
    $auth_result = $db->authenticateUser($test_user['email'], $test_user['password'], $test_user['role']);
    if ($auth_result) {
        echo "✓ Authentication successful with correct credentials<br>";
        echo "User data: " . print_r($auth_result, true) . "<br>";
    } else {
        echo "✗ Authentication failed with correct credentials<br>";
    }
    
    // Test incorrect password
    echo "<br>Testing with incorrect password:<br>";
    $auth_result = $db->authenticateUser($test_user['email'], 'wrongpassword', $test_user['role']);
    if ($auth_result) {
        echo "✗ Authentication succeeded with incorrect password (this is wrong!)<br>";
    } else {
        echo "✓ Authentication correctly failed with incorrect password<br>";
    }
    
    // Test incorrect email
    echo "<br>Testing with incorrect email:<br>";
    $auth_result = $db->authenticateUser('wrong@email.com', $test_user['password'], $test_user['role']);
    if ($auth_result) {
        echo "✗ Authentication succeeded with incorrect email (this is wrong!)<br>";
    } else {
        echo "✓ Authentication correctly failed with incorrect email<br>";
    }
    
    // Test incorrect role
    echo "<br>Testing with incorrect role:<br>";
    $auth_result = $db->authenticateUser($test_user['email'], $test_user['password'], 'barber');
    if ($auth_result) {
        echo "✗ Authentication succeeded with incorrect role (this is wrong!)<br>";
    } else {
        echo "✓ Authentication correctly failed with incorrect role<br>";
    }
    
    echo "<h3>3. Testing User Retrieval</h3>";
    
    // Test getUserByEmail
    echo "Testing getUserByEmail:<br>";
    $user = $db->getUserByEmail($test_user['email']);
    if ($user) {
        echo "✓ Successfully retrieved user by email<br>";
        echo "User data: " . print_r($user, true) . "<br>";
    } else {
        echo "✗ Failed to retrieve user by email<br>";
    }
    
    // Test getUserByUsername
    echo "<br>Testing getUserByUsername:<br>";
    $user = $db->getUserByUsername($test_user['username']);
    if ($user) {
        echo "✓ Successfully retrieved user by username<br>";
        echo "User data: " . print_r($user, true) . "<br>";
    } else {
        echo "✗ Failed to retrieve user by username<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?> 