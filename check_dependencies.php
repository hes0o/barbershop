<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 Checking Dependencies</h2>";

// List of required files for the system to work
$requiredFiles = [
    'includes/config.php',
    'includes/db.php',
    'admin/dashboard.php',
    'barber/dashboard.php',
    'index.php',
    'login.php',
    'logout.php',
    'css/style.css'
];

echo "<h3>📁 Required Files Check</h3>";
$missingFiles = [];
$existingFiles = [];

foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "✅ $file exists<br>";
        $existingFiles[] = $file;
    } else {
        echo "❌ $file missing<br>";
        $missingFiles[] = $file;
    }
}

// Check Database class methods
echo "<h3>🔧 Database Class Check</h3>";
if (file_exists(__DIR__ . '/includes/db.php')) {
    try {
        require_once __DIR__ . '/includes/config.php';
        require_once __DIR__ . '/includes/db.php';
        
        if (class_exists('Database')) {
            echo "✅ Database class exists<br>";
            
            $db = new Database();
            echo "✅ Database class instantiated<br>";
            
            // Check required methods
            $requiredMethods = [
                'getAllCustomers',
                'getAllBarbers', 
                'getAllServices',
                'getMaintenanceMode',
                'setMaintenanceMode',
                'logActivity',
                'getSingleBarber',
                'getBarberAppointments',
                'getBarberWeeklySchedule',
                'getAvailableTimeSlots'
            ];
            
            foreach ($requiredMethods as $method) {
                if (method_exists($db, $method)) {
                    echo "✅ Method $method() exists<br>";
                } else {
                    echo "❌ Method $method() missing<br>";
                }
            }
            
        } else {
            echo "❌ Database class not found<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ db.php file missing<br>";
}

// Check for common issues
echo "<h3>🔍 Common Issues Check</h3>";

// Check if BASE_URL is defined
if (defined('BASE_URL')) {
    echo "✅ BASE_URL is defined: " . BASE_URL . "<br>";
} else {
    echo "❌ BASE_URL is not defined<br>";
}

// Check session configuration
echo "<h3>🔐 Session Check</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    echo "✅ Session started<br>";
} else {
    echo "✅ Session already active<br>";
}

// Check if we can write to session
$_SESSION['test'] = 'test_value';
if (isset($_SESSION['test'])) {
    echo "✅ Session writing works<br>";
    unset($_SESSION['test']);
} else {
    echo "❌ Session writing failed<br>";
}

// Check file permissions
echo "<h3>📄 File Permissions Check</h3>";
$criticalFiles = [
    'includes/config.php',
    'includes/db.php',
    'admin/dashboard.php',
    'barber/dashboard.php'
];

foreach ($criticalFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $perms = fileperms($fullPath);
        $perms_octal = substr(sprintf('%o', $perms), -4);
        $isReadable = is_readable($fullPath);
        $isExecutable = is_executable($fullPath);
        
        echo "📄 $file: $perms_octal (readable: " . ($isReadable ? 'yes' : 'no') . ", executable: " . ($isExecutable ? 'yes' : 'no') . ")<br>";
        
        if (!$isReadable) {
            echo "⚠️ Warning: $file is not readable<br>";
        }
    }
}

// Test database connection
echo "<h3>🗄️ Database Connection Test</h3>";
if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
        } else {
            echo "✅ Database connection successful<br>";
            
            // Check if required tables exist
            $requiredTables = ['users', 'services', 'appointments', 'barbers', 'working_hours', 'settings'];
            $existingTables = [];
            
            $result = $conn->query("SHOW TABLES");
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $existingTables[] = $row[0];
                }
            }
            
            echo "📊 Tables found: " . implode(', ', $existingTables) . "<br>";
            
            foreach ($requiredTables as $table) {
                if (in_array($table, $existingTables)) {
                    echo "✅ Table '$table' exists<br>";
                } else {
                    echo "❌ Table '$table' missing<br>";
                }
            }
            
            $conn->close();
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Database constants not defined<br>";
}

// Check for .htaccess issues
echo "<h3>📝 .htaccess Check</h3>";
$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✅ .htaccess file exists<br>";
    $htaccessContent = file_get_contents($htaccessFile);
    
    // Check for common .htaccess issues
    if (strpos($htaccessContent, 'RewriteEngine On') !== false) {
        echo "✅ RewriteEngine is enabled<br>";
    } else {
        echo "⚠️ RewriteEngine not found in .htaccess<br>";
    }
    
    if (strpos($htaccessContent, 'ErrorDocument 500') !== false) {
        echo "✅ Custom 500 error page configured<br>";
    } else {
        echo "⚠️ No custom 500 error page configured<br>";
    }
} else {
    echo "⚠️ No .htaccess file found<br>";
}

// Summary
echo "<hr><h3>📊 Summary</h3>";
echo "<strong>Missing files:</strong> " . count($missingFiles) . "<br>";
echo "<strong>Existing files:</strong> " . count($existingFiles) . "<br>";

if (!empty($missingFiles)) {
    echo "<h4>🚨 Missing Files:</h4>";
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    foreach ($missingFiles as $file) {
        echo "• $file<br>";
    }
    echo "</div>";
}

echo "<h4>🔧 Next Steps:</h4>";
if (!empty($missingFiles)) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
    echo "1. Create missing files<br>";
    echo "2. Check file permissions<br>";
    echo "3. Ensure all dependencies are installed<br>";
    echo "</div>";
} else {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
    echo "✅ All required files exist!<br>";
    echo "The 500 error might be due to:<br>";
    echo "• PHP syntax errors in files<br>";
    echo "• Server configuration issues<br>";
    echo "• Missing PHP extensions<br>";
    echo "• .htaccess configuration<br>";
    echo "</div>";
}
?> 