<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "Starting table creation process...<br>";
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "Database connection successful<br>";
    echo "Current database: " . $conn->database . "<br>";
    
    // Check if we can query the database
    $test_query = $conn->query("SHOW TABLES");
    if ($test_query === false) {
        die("Error checking tables: " . $conn->error);
    }
    
    echo "Existing tables:<br>";
    while ($row = $test_query->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
    
    // Create barber_schedules table
    $sql = "CREATE TABLE IF NOT EXISTS barber_schedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barber_id INT NOT NULL,
        day_of_week VARCHAR(10) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
        UNIQUE KEY unique_barber_day (barber_id, day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    echo "Attempting to create table with SQL:<br>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre><br>";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table barber_schedules created successfully<br>";
        
        // Verify table was created
        $check_table = $conn->query("SHOW TABLES LIKE 'barber_schedules'");
        if ($check_table->num_rows > 0) {
            echo "Table verification successful<br>";
            
            // Get all barbers
            $barbers = $db->getAllBarbers();
            echo "Found " . count($barbers) . " barbers<br>";
            
            // Default schedule for each barber
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $default_start = '09:00:00';
            $default_end = '17:00:00';
            
            // Prepare insert statement
            $stmt = $conn->prepare("INSERT IGNORE INTO barber_schedules (barber_id, day_of_week, start_time, end_time, status) VALUES (?, ?, ?, ?, 'available')");
            
            if ($stmt === false) {
                die("Error preparing insert statement: " . $conn->error);
            }
            
            foreach ($barbers as $barber) {
                echo "Creating schedule for barber ID: " . $barber['id'] . "<br>";
                foreach ($days as $day) {
                    $stmt->bind_param("isss", $barber['id'], $day, $default_start, $default_end);
                    if (!$stmt->execute()) {
                        echo "Error creating schedule for barber {$barber['id']} on $day: " . $stmt->error . "<br>";
                    }
                }
            }
            
            echo "Default schedules created for all barbers<br>";
            
        } else {
            echo "Error: Table was not created successfully<br>";
        }
        
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
        echo "Error code: " . $conn->errno . "<br>";
        echo "SQL State: " . $conn->sqlstate . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?> 