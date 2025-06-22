<?php
/**
 * Test Connection Script
 * 
 * This script tests the database connection and basic setup to help debug issues.
 */

echo "<h2>🔧 System Test & Debug</h2>";

// Test 1: Check if config file exists and loads
echo "<h3>1. Config File Test</h3>";
if (file_exists(__DIR__ . '/includes/config.php')) {
    echo "<p style='color: green;'>✓ Config file exists</p>";
    
    // Try to include it
    try {
        require_once __DIR__ . '/includes/config.php';
        echo "<p style='color: green;'>✓ Config file loaded successfully</p>";
        echo "<p>Database: " . DB_NAME . " on " . DB_HOST . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error loading config: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Config file not found</p>";
}

// Test 2: Database Connection
echo "<h3>2. Database Connection Test</h3>";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>✗ Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Database connected successfully</p>";
        
        // Test a simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>✓ Users table accessible: " . $row['count'] . " users found</p>";
        } else {
            echo "<p style='color: red;'>✗ Users table query failed: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection error: " . $e->getMessage() . "</p>";
}

// Test 3: Check if required tables exist
echo "<h3>3. Database Tables Test</h3>";
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    $requiredTables = ['users', 'barbers', 'services', 'appointments', 'settings'];
    $existingTables = [];
    
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $existingTables[] = $row[0];
    }
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "<p style='color: green;'>✓ Table exists: $table</p>";
        } else {
            echo "<p style='color: red;'>✗ Missing table: $table</p>";
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Table check error: " . $e->getMessage() . "</p>";
}

// Test 4: Check file permissions
echo "<h3>4. File Permissions Test</h3>";
$testDirs = ['includes', 'admin', 'barber'];
foreach ($testDirs as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            echo "<p style='color: green;'>✓ Directory readable: $dir</p>";
        } else {
            echo "<p style='color: red;'>✗ Directory not readable: $dir</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Directory not found: $dir</p>";
    }
}

// Test 5: Check PHP version and extensions
echo "<h3>5. PHP Environment Test</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>MySQL Extension: " . (extension_loaded('mysqli') ? '✓ Loaded' : '✗ Not loaded') . "</p>";
echo "<p>Session Extension: " . (extension_loaded('session') ? '✓ Loaded' : '✗ Not loaded') . "</p>";

// Test 6: Test session functionality
echo "<h3>6. Session Test</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✓ Sessions working</p>";
    $_SESSION['test'] = 'test_value';
    echo "<p>Session test value set: " . $_SESSION['test'] . "</p>";
} else {
    echo "<p style='color: red;'>✗ Sessions not working</p>";
}

// Test 7: Check for common error files
echo "<h3>7. Error Log Check</h3>";
$errorLogs = ['error_log', 'php_errors.log', '../error_log'];
foreach ($errorLogs as $log) {
    if (file_exists($log)) {
        echo "<p style='color: orange;'>⚠ Error log found: $log</p>";
        $size = filesize($log);
        echo "<p>Log size: " . number_format($size) . " bytes</p>";
        if ($size > 0) {
            echo "<p><a href='$log' target='_blank'>View error log</a></p>";
        }
    }
}

echo "<hr>";
echo "<h3>🔧 Quick Fixes to Try:</h3>";
echo "<ol>";
echo "<li><strong>Clear browser cache</strong> and try again</li>";
echo "<li><strong>Check your hosting control panel</strong> for any PHP errors</li>";
echo "<li><strong>Try accessing with www</strong>: www.customprojects.shawa.com.tr</li>";
echo "<li><strong>Check if .htaccess</strong> is blocking access</li>";
echo "<li><strong>Contact your hosting provider</strong> if database connection fails</li>";
echo "</ol>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>📞 Need Help?</h4>";
echo "<p>If pages still don't load after these tests, please share:</p>";
echo "<ul>";
echo "<li>The exact error message you see</li>";
echo "<li>Results from this test script</li>";
echo "<li>Your hosting provider name</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #555; margin-top: 20px; }
hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
</style> 