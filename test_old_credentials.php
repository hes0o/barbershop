<?php
// Test the old working credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'shawacom_hassan');
define('DB_PASS', 'Hassan@Chawa981');
define('DB_NAME', 'shawacom_Barber');

echo "<h2>Testing Old Working Credentials</h2>";
echo "Host: " . DB_HOST . "<br>";
echo "User: " . DB_USER . "<br>";
echo "Database: " . DB_NAME . "<br><br>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "❌ Connection failed: " . $conn->connect_error;
    } else {
        echo "✅ Connection successful!<br>";
        echo "Server info: " . $conn->server_info . "<br>";
        
        // Check if tables exist
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<br>Tables found:<br>";
            while ($row = $result->fetch_array()) {
                echo "- " . $row[0] . "<br>";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?> 