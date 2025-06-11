<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Diagnostic Report</h2>";

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/db.php';

    echo "<h3>1. Database Connection Test</h3>";
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo "✓ Database connection successful<br>";
    echo "Current database: " . $conn->database . "<br>";

    echo "<h3>2. Database User Permissions</h3>";
    $result = $conn->query("SHOW GRANTS");
    if ($result) {
        while ($row = $result->fetch_array()) {
            echo htmlspecialchars($row[0]) . "<br>";
        }
    } else {
        echo "Could not check permissions: " . $conn->error . "<br>";
    }

    echo "<h3>3. Existing Tables</h3>";
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "Tables in database:<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    } else {
        echo "Error listing tables: " . $conn->error . "<br>";
    }

    echo "<h3>4. Barber Table Check</h3>";
    $result = $conn->query("SHOW TABLES LIKE 'barbers'");
    if ($result && $result->num_rows > 0) {
        echo "✓ Barbers table exists<br>";
        $result = $conn->query("SELECT COUNT(*) as count FROM barbers");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Number of barbers: " . $row['count'] . "<br>";
        }
    } else {
        echo "✗ Barbers table does not exist!<br>";
    }

    echo "<h3>5. Test Table Creation</h3>";
    $test_table = "test_table_" . time();
    $sql = "CREATE TABLE `$test_table` (id INT)";
    if ($conn->query($sql)) {
        echo "✓ Successfully created test table<br>";
        $conn->query("DROP TABLE `$test_table`");
        echo "✓ Successfully dropped test table<br>";
    } else {
        echo "✗ Could not create test table: " . $conn->error . "<br>";
    }

    echo "<h3>6. Database Character Set</h3>";
    $result = $conn->query("SHOW VARIABLES LIKE 'character_set_database'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Character set: " . $row['Value'] . "<br>";
    }

    echo "<h3>7. Database Collation</h3>";
    $result = $conn->query("SHOW VARIABLES LIKE 'collation_database'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Collation: " . $row['Value'] . "<br>";
    }

    echo "<h3>8. MySQL Version</h3>";
    $result = $conn->query("SELECT VERSION() as version");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "MySQL Version: " . $row['version'] . "<br>";
    }

} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?> 