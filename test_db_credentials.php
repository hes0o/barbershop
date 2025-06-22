<?php
/**
 * Database Credentials Test Script
 * 
 * This script helps test different database credentials to find the correct ones.
 */

echo "<h2>🔍 Database Credentials Test</h2>";

// Common database credential variations to test
$testCredentials = [
    [
        'name' => 'Current Config',
        'host' => 'localhost',
        'user' => 'shawacom_barber',
        'pass' => 'Hes0o@981',
        'db' => 'shawacom_barber'
    ],
    [
        'name' => 'Alternative 1',
        'host' => 'localhost',
        'user' => 'shawacom_barber',
        'pass' => 'Hes0o@981',
        'db' => 'customprojects_barber'
    ],
    [
        'name' => 'Alternative 2',
        'host' => 'localhost',
        'user' => 'customprojects_barber',
        'pass' => 'Hes0o@981',
        'db' => 'customprojects_barber'
    ],
    [
        'name' => 'Alternative 3',
        'host' => 'localhost',
        'user' => 'root',
        'pass' => '',
        'db' => 'shawacom_barber'
    ]
];

echo "<h3>Testing Database Connections:</h3>";

foreach ($testCredentials as $cred) {
    echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>{$cred['name']}</h4>";
    echo "<p><strong>Host:</strong> {$cred['host']}</p>";
    echo "<p><strong>User:</strong> {$cred['user']}</p>";
    echo "<p><strong>Database:</strong> {$cred['db']}</p>";
    
    try {
        $conn = new mysqli($cred['host'], $cred['user'], $cred['pass'], $cred['db']);
        
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
echo "<h3>🔧 How to Find Your Correct Database Credentials:</h3>";
echo "<ol>";
echo "<li><strong>Log into your hosting control panel</strong> (cPanel, Plesk, etc.)</li>";
echo "<li><strong>Go to 'MySQL Databases' or 'Databases' section</strong></li>";
echo "<li><strong>Look for:</strong>";
echo "<ul>";
echo "<li>Database name (usually starts with your hosting username)</li>";
echo "<li>Database username</li>";
echo "<li>Database password</li>";
echo "<li>Database host (usually 'localhost')</li>";
echo "</ul></li>";
echo "<li><strong>Update your config.php file</strong> with the correct credentials</li>";
echo "</ol>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>📝 Common Database Naming Patterns:</h4>";
echo "<ul>";
echo "<li><strong>cPanel:</strong> username_databasename</li>";
echo "<li><strong>Plesk:</strong> username_databasename</li>";
echo "<li><strong>Shared hosting:</strong> Usually prefixed with your hosting account username</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
echo "<h4>🔐 Security Note:</h4>";
echo "<p>After finding the correct credentials, make sure to:</p>";
echo "<ul>";
echo "<li>Update your includes/config.php file</li>";
echo "<li>Delete this test script for security</li>";
echo "<li>Test your barber dashboard again</li>";
echo "</ul>";
echo "</div>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #555; margin-top: 20px; }
h4 { color: #666; margin-top: 15px; }
hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
</style> 