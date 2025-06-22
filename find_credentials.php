<?php
echo "<h2>🔍 Finding Your Database Credentials</h2>";

// Common credential patterns to test
$test_combinations = [
    // Original credentials
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'shawacom_barber'],
    
    // Alternative passwords
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'shawacom_barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'shawacom_barber'],
    
    // Different username patterns
    ['localhost', 'shawacom_barber', 'Hassan@Chawa981', 'shawacom_Barber'],
    ['localhost', 'shawacom_barber', 'Hassan@Chawa981', 'shawacom_barber'],
    ['localhost', 'shawacom_barber', 'Hes0o@981', 'shawacom_Barber'],
    ['localhost', 'shawacom_barber', 'Hes0o@981', 'shawacom_barber'],
    
    // Domain-based patterns
    ['localhost', 'customprojects_barber', 'Hassan@Chawa981', 'customprojects_barber'],
    ['localhost', 'customprojects_barber', 'Hes0o@981', 'customprojects_barber'],
    ['localhost', 'customprojects_barbershop', 'Hassan@Chawa981', 'customprojects_barbershop'],
    ['localhost', 'customprojects_barbershop', 'Hes0o@981', 'customprojects_barbershop'],
    
    // Simple patterns
    ['localhost', 'barber', 'Hassan@Chawa981', 'barber'],
    ['localhost', 'barber', 'Hes0o@981', 'barber'],
    ['localhost', 'barbershop', 'Hassan@Chawa981', 'barbershop'],
    ['localhost', 'barbershop', 'Hes0o@981', 'barbershop'],
];

$found_credentials = false;

foreach ($test_combinations as $index => $combo) {
    list($host, $user, $pass, $db) = $combo;
    
    echo "<br><strong>Test " . ($index + 1) . ":</strong><br>";
    echo "Host: $host | User: $user | Database: $db<br>";
    
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        
        if ($conn->connect_error) {
            echo "❌ Failed: " . $conn->connect_error . "<br>";
        } else {
            echo "✅ <strong>SUCCESS!</strong> Connection works!<br>";
            echo "Server: " . $conn->server_info . "<br>";
            
            // Check if tables exist
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                echo "Tables found: ";
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                echo implode(', ', $tables) . "<br>";
            }
            
            $conn->close();
            $found_credentials = true;
            
            echo "<br><div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
            echo "<strong>🎉 FOUND WORKING CREDENTIALS!</strong><br>";
            echo "Update your includes/config.php with:<br>";
            echo "define('DB_HOST', '$host');<br>";
            echo "define('DB_USER', '$user');<br>";
            echo "define('DB_PASS', '$pass');<br>";
            echo "define('DB_NAME', '$db');<br>";
            echo "</div>";
            
            break; // Stop testing once we find working credentials
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
}

if (!$found_credentials) {
    echo "<br><div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<strong>❌ No working credentials found</strong><br>";
    echo "You'll need to:<br>";
    echo "1. Log into your hosting control panel (cPanel, Plesk, etc.)<br>";
    echo "2. Go to 'MySQL Databases' or 'Databases' section<br>";
    echo "3. Check your database name, username, and password<br>";
    echo "4. Update your includes/config.php with the correct credentials<br>";
    echo "</div>";
}
?> 