<?php
// Enable error reporting to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Debugging 500 Error</h2>";

// Test basic PHP functionality
echo "<h3>Basic PHP Test</h3>";
echo "✅ PHP is working<br>";

// Test config file inclusion
echo "<h3>Config File Test</h3>";
try {
    require_once __DIR__ . '/includes/config.php';
    echo "✅ Config file loaded successfully<br>";
    
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        echo "✅ Database constants defined<br>";
    } else {
        echo "❌ Database constants missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
}

// Test database connection
echo "<h3>Database Connection Test</h3>";
try {
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
        } else {
            echo "✅ Database connection successful<br>";
            $conn->close();
        }
    } else {
        echo "❌ Cannot test database - constants not defined<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test Database class
echo "<h3>Database Class Test</h3>";
try {
    if (file_exists(__DIR__ . '/includes/db.php')) {
        require_once __DIR__ . '/includes/db.php';
        
        if (class_exists('Database')) {
            echo "✅ Database class exists<br>";
            
            try {
                $db = new Database();
                echo "✅ Database class instantiated successfully<br>";
            } catch (Exception $e) {
                echo "❌ Error creating Database instance: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Database class not found<br>";
        }
    } else {
        echo "❌ db.php file not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error loading db.php: " . $e->getMessage() . "<br>";
}

// Test session
echo "<h3>Session Test</h3>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "✅ Session started<br>";
    } else {
        echo "✅ Session already active<br>";
    }
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

// Check file permissions
echo "<h3>File Permissions Test</h3>";
$files_to_check = [
    'includes/config.php',
    'includes/db.php',
    'admin/dashboard.php',
    'barber/dashboard.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $perms = fileperms($full_path);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        echo "✅ $file exists (permissions: $perms_octal)<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

echo "<h3>PHP Info</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
?> 