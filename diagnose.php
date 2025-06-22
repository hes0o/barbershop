<?php
echo "<h2>🔍 System Diagnosis</h2>";

// Check PHP version
echo "<h3>PHP Information</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

// Check if config file exists
echo "<h3>File Check</h3>";
$config_file = __DIR__ . '/includes/config.php';
if (file_exists($config_file)) {
    echo "✅ config.php exists at: " . $config_file . "<br>";
    
    // Check if we can include it
    try {
        include_once $config_file;
        echo "✅ config.php can be included<br>";
        
        // Check if constants are defined
        if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
            echo "✅ Database constants are defined<br>";
            echo "DB_HOST: " . DB_HOST . "<br>";
            echo "DB_USER: " . DB_USER . "<br>";
            echo "DB_NAME: " . DB_NAME . "<br>";
        } else {
            echo "❌ Database constants are NOT defined<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error including config.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ config.php does NOT exist at: " . $config_file . "<br>";
}

// Check other important files
echo "<h3>Important Files Check</h3>";
$files_to_check = [
    'includes/db.php',
    'admin/dashboard.php',
    'barber/dashboard.php',
    'index.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test database connection with current config
echo "<h3>Database Connection Test</h3>";
if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
        } else {
            echo "✅ Database connection successful!<br>";
            echo "Server info: " . $conn->server_info . "<br>";
            
            // Check tables
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
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Cannot test database - constants not defined<br>";
}

// Check if Database class exists
echo "<h3>Database Class Check</h3>";
if (file_exists(__DIR__ . '/includes/db.php')) {
    try {
        include_once __DIR__ . '/includes/db.php';
        if (class_exists('Database')) {
            echo "✅ Database class exists<br>";
            
            // Try to instantiate it
            try {
                $db = new Database();
                echo "✅ Database class can be instantiated<br>";
            } catch (Exception $e) {
                echo "❌ Error creating Database instance: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Database class does NOT exist<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error including db.php: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db.php file missing<br>";
}

// Check session
echo "<h3>Session Check</h3>";
if (session_status() === PHP_SESSION_NONE) {
    echo "Session not started<br>";
} else {
    echo "Session is active<br>";
}

echo "<h3>Current Directory</h3>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?> 