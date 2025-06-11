<?php
require_once 'config.php';
require_once 'includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Authentication System Test</h2>";
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

try {
    $db = new Database();
    
    // 1. Test Database Connection
    echo "\n=== Database Connection Test ===\n";
    test_result("Database Connection", $db->getConnection() !== null);
    
    // 2. Test User Creation
    echo "\n=== User Creation Test ===\n";
    $test_user = [
        'username' => 'testuser_' . time(),
        'email' => 'test_' . time() . '@example.com',
        'password' => 'Test123!',
        'role' => 'customer',
        'phone' => '1234567890'
    ];
    
    $create_result = $db->createUser(
        $test_user['username'],
        $test_user['email'],
        $test_user['password'],
        $test_user['role'],
        $test_user['phone']
    );
    
    test_result("User Creation", $create_result, "Created test user: " . $test_user['email']);
    
    // 3. Test User Retrieval
    echo "\n=== User Retrieval Test ===\n";
    $user = $db->getUserByEmail($test_user['email']);
    test_result("User Retrieval by Email", $user !== false, "Found user: " . print_r($user, true));
    
    // 4. Test Authentication
    echo "\n=== Authentication Test ===\n";
    
    // Test correct credentials
    $auth_result = $db->authenticateUser($test_user['email'], $test_user['password'], $test_user['role']);
    test_result("Authentication with Correct Credentials", $auth_result !== false, 
        $auth_result ? "Authenticated user: " . print_r($auth_result, true) : "Authentication failed");
    
    // Test wrong password
    $wrong_pass = $db->authenticateUser($test_user['email'], 'WrongPassword123!', $test_user['role']);
    test_result("Authentication with Wrong Password", $wrong_pass === false, "Correctly rejected wrong password");
    
    // Test wrong role
    $wrong_role = $db->authenticateUser($test_user['email'], $test_user['password'], 'barber');
    test_result("Authentication with Wrong Role", $wrong_role === false, "Correctly rejected wrong role");
    
    // Test non-existent user
    $non_existent = $db->authenticateUser('nonexistent@example.com', 'password123', 'customer');
    test_result("Authentication with Non-existent User", $non_existent === false, "Correctly rejected non-existent user");
    
    // 5. Test Session Handling
    echo "\n=== Session Handling Test ===\n";
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    
    test_result("Session Variables Set", 
        isset($_SESSION['user_id']) && 
        isset($_SESSION['username']) && 
        isset($_SESSION['email']) && 
        isset($_SESSION['role']),
        "Session data: " . print_r($_SESSION, true)
    );
    
    // 6. Clean up test user
    echo "\n=== Cleanup Test ===\n";
    $stmt = $db->getConnection()->prepare("DELETE FROM users WHERE email = ?");
    $stmt->bind_param("s", $test_user['email']);
    $cleanup_result = $stmt->execute();
    test_result("Test User Cleanup", $cleanup_result, "Removed test user");
    
    // 7. Test Default Users
    echo "\n=== Default Users Test ===\n";
    
    // Test admin user
    $admin = $db->getUserByEmail('admin@barbershop.com');
    test_result("Default Admin User", $admin !== false, "Admin user exists");
    
    // Test default barber
    $barber = $db->getUserByEmail('barber@barbershop.com');
    test_result("Default Barber User", $barber !== false, "Default barber exists");
    
    // 8. System Information
    echo "\n=== System Information ===\n";
    echo "PHP Version: " . PHP_VERSION . "\n";
    echo "MySQL Version: " . $db->getConnection()->server_info . "\n";
    echo "Session Save Path: " . session_save_path() . "\n";
    echo "Session Status: " . session_status() . "\n";
    
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?> 