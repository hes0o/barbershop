<?php
// Test database connection
require_once 'includes/config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";
        
        // Test a simple query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p>✅ Users table accessible. Total users: " . $row['count'] . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Users table query failed: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Current Configuration:</h3>";
echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
echo "<p><strong>User:</strong> " . DB_USER . "</p>";
echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
echo "<p><strong>Password:</strong> " . (strlen(DB_PASS) > 0 ? "Set (" . strlen(DB_PASS) . " characters)" : "Not set") . "</p>";
?> 