<?php
echo "<h2>🔍 Finding Your Database Credentials</h2>";
echo "<p><strong>Username:</strong> shawacom_hassan (confirmed)</p>";

// Test combinations with the known username
$test_combinations = [
    // Known username with different passwords and database names
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'shawacom_barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'shawacom_barbershop'],
    
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'shawacom_barber'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'shawacom_barbershop'],
    
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'shawacom_barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'shawacom_barbershop'],
    
    ['localhost', 'shawacom_hassan', 'Chawa981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Chawa981', 'shawacom_barber'],
    ['localhost', 'shawacom_hassan', 'Chawa981', 'shawacom_barbershop'],
    
    ['localhost', 'shawacom_hassan', 'Hassan981', 'shawacom_Barber'],
    ['localhost', 'shawacom_hassan', 'Hassan981', 'shawacom_barber'],
    ['localhost', 'shawacom_hassan', 'Hassan981', 'shawacom_barbershop'],
    
    // Try with domain-based database names
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'customprojects_barber'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'customprojects_barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'customprojects_barber'],
    
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'customprojects_barbershop'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'customprojects_barbershop'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'customprojects_barbershop'],
    
    // Simple database names
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'barber'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'barber'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'barber'],
    
    ['localhost', 'shawacom_hassan', 'Hassan@Chawa981', 'barbershop'],
    ['localhost', 'shawacom_hassan', 'Hes0o@981', 'barbershop'],
    ['localhost', 'shawacom_hassan', 'Hassan@981', 'barbershop'],
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
    echo "Since we know the username is 'shawacom_hassan', you need to:<br>";
    echo "1. Log into your hosting control panel (cPanel, Plesk, etc.)<br>";
    echo "2. Go to 'MySQL Databases' or 'Databases' section<br>";
    echo "3. Find the password for user 'shawacom_hassan'<br>";
    echo "4. Find the correct database name<br>";
    echo "5. Update your includes/config.php with the correct password and database name<br>";
    echo "</div>";
}
?> 