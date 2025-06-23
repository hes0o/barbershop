<?php
// test_everything.php - Comprehensive diagnostic script
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// 1. Show config values
require_once __DIR__ . '/includes/config.php';
echo "<h2>Config Values</h2>";
echo "<pre>";
echo "DB_HOST: ".DB_HOST."\n";
echo "DB_USER: ".DB_USER."\n";
echo "DB_PASS: ".(strlen(DB_PASS) ? str_repeat('*', strlen(DB_PASS)) : 'Not set')."\n";
echo "DB_NAME: ".DB_NAME."\n";
echo "</pre>";

// 2. Test database connection
require_once __DIR__ . '/includes/db.php';
echo "<h2>Database Connection</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    if ($conn->connect_error) {
        echo "<div style='color:red;'>❌ Connection failed: ".$conn->connect_error."</div>";
    } else {
        echo "<div style='color:green;'>✅ Database connection successful!</div>";
        // Test a simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<div>Users table accessible. Total users: ".$row['count']."</div>";
        } else {
            echo "<div style='color:orange;'>⚠️ Users table query failed: ".$conn->error."</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Exception: ".$e->getMessage()."</div>";
}

// 3. Test session persistence
echo "<h2>Session Test</h2>";
if (!isset($_SESSION['test'])) {
    $_SESSION['test'] = uniqid('sess_', true);
    echo "<div>Session variable set. Reload this page to test persistence.</div>";
} else {
    echo "<div style='color:green;'>Session persists! Value: ".$_SESSION['test']."</div>";
}
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// 4. Test authentication (simulate login)
require_once __DIR__ . '/includes/auth.php';
echo "<h2>Authentication Test</h2>";
$test_email = 'admin@barbershop.com';
$test_password = 'admin123'; // Change to a real password if needed
$test_role = 'admin';
$auth = new Auth();
$result = $auth->login($test_email, $test_password);
if ($result['success']) {
    echo "<div style='color:green;'>✅ Authentication successful for $test_email as $test_role</div>";
    echo "<pre>";
    print_r($result['user']);
    echo "</pre>";
} else {
    echo "<div style='color:red;'>❌ Authentication failed: ".$result['message']."</div>";
}

// 5. Test user permissions (SHOW GRANTS)
echo "<h2>Database User Permissions</h2>";
try {
    $result = $conn->query("SHOW GRANTS");
    if ($result) {
        while ($row = $result->fetch_array()) {
            echo htmlspecialchars($row[0])."<br>";
        }
    } else {
        echo "Could not check permissions: ".$conn->error."<br>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>Error: ".$e->getMessage()."</div>";
}

// 6. Test table existence
echo "<h2>Table Existence</h2>";
$tables = ['users', 'barbers', 'services', 'appointments', 'barber_schedules', 'working_hours'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<div style='color:green;'>Table '$table' exists</div>";
    } else {
        echo "<div style='color:red;'>Table '$table' does NOT exist</div>";
    }
}

echo "<hr><div>Test completed. If you see any red errors above, those are likely the cause of your issues.</div>"; 