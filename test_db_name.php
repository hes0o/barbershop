<?php
/**
 * Test Database Name Script
 * 
 * This script tests different database names with the correct credentials.
 */

echo "<h2>🔍 Testing Database Names</h2>";

// Test different database names with the correct credentials
$testDatabases = [
    'shawacom_barber',
    'shawacom_hassan',
    'shawacom_barbershop',
    'customprojects_barber',
    'customprojects_barbershop',
    'hassan_barber',
    'hassan_barbershop'
];

$username = 'shawacom_hassan';
$password = 'Hes0o@981';
$host = 'localhost';

echo "<h3>Testing with username: $username</h3>";

foreach ($testDatabases as $dbName) {
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>Testing database: $dbName</h4>";
    
    try {
        $conn = new mysqli($host, $username, $password, $dbName);
        
        if ($conn->connect_error) {
            echo "<p style='color: red;'>✗ Connection failed: " . $conn->connect_error . "</p>";
        } else {
            echo "<p style='color: green;'>✓ Connection successful!</p>";
            
            // Test if we can query the database
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                echo "<p style='color: green;'>✓ Database accessible. Tables found: " . count($tables) . "</p>";
                if (count($tables) > 0) {
                    echo "<p><strong>Tables:</strong> " . implode(', ', $tables) . "</p>";
                }
                
                // If we found a working connection, show the config update
                echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin-top: 10px;'>";
                echo "<h5>🎉 SUCCESS! Update your config.php with:</h5>";
                echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
                echo "define('DB_HOST', '$host');\n";
                echo "define('DB_USER', '$username');\n";
                echo "define('DB_PASS', '$password');\n";
                echo "define('DB_NAME', '$dbName');";
                echo "</pre>";
                echo "</div>";
            } else {
                echo "<p style='color: orange;'>⚠ Connected but can't query tables</p>";
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

echo "<hr>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔧 Next Steps:</h4>";
echo "<ol>";
echo "<li>If one of the databases works, update your includes/config.php file</li>";
echo "<li>Test your barber dashboard: <a href='barber/dashboard.php'>barber/dashboard.php</a></li>";
echo "<li>Run the email config setup: <a href='setup_email_config.php'>setup_email_config.php</a></li>";
echo "<li>Delete this test script for security</li>";
echo "</ol>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #555; margin-top: 20px; }
h4 { color: #666; margin-top: 15px; }
h5 { color: #777; margin-top: 10px; }
hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
pre { font-family: monospace; font-size: 12px; }
</style> 