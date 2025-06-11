<?php
require_once 'config.php';
require_once 'includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Check if appointments table exists
    $result = $conn->query("SHOW TABLES LIKE 'appointments'");
    if ($result->num_rows === 0) {
        echo "<h2>Appointments Table Does Not Exist!</h2>";
        echo "<p>Here's the SQL to create it:</p>";
        echo "<pre>";
        echo "CREATE TABLE IF NOT EXISTS appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    barber_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (barber_id) REFERENCES barbers(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);";
        echo "</pre>";
        exit;
    }

    // Check appointments table structure
    $result = $conn->query("DESCRIBE appointments");
    if (!$result) {
        throw new Exception("Error checking appointments table: " . $conn->error);
    }

    echo "<h2>Appointments Table Structure</h2>";
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";

    // Check if there are any appointments
    $result = $conn->query("SELECT COUNT(*) as count FROM appointments");
    if (!$result) {
        throw new Exception("Error counting appointments: " . $conn->error);
    }
    $count = $result->fetch_assoc()['count'];
    echo "<h2>Total Appointments: $count</h2>";

    // Check foreign key constraints
    echo "<h2>Foreign Key Constraints</h2>";
    $result = $conn->query("
        SELECT 
            TABLE_NAME,
            COLUMN_NAME,
            CONSTRAINT_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE
            REFERENCED_TABLE_SCHEMA = '" . DB_NAME . "'
            AND TABLE_NAME = 'appointments'
    ");

    if (!$result) {
        throw new Exception("Error checking foreign keys: " . $conn->error);
    }

    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";

    // Check indexes
    echo "<h2>Indexes</h2>";
    $result = $conn->query("SHOW INDEX FROM appointments");
    if (!$result) {
        throw new Exception("Error checking indexes: " . $conn->error);
    }

    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";

    // Check related tables
    echo "<h2>Related Tables Check</h2>";
    $tables = ['users', 'barbers', 'services'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if (!$result) {
            echo "<p>Error checking $table table: " . $conn->error . "</p>";
            continue;
        }
        $count = $result->fetch_assoc()['count'];
        echo "<p>Total records in $table: $count</p>";
    }

} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
} 