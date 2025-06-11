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
    
    // First, try to drop the table if it exists (to ensure clean creation)
    $drop_sql = "DROP TABLE IF EXISTS `barber_schedules`";
    if ($conn->query($drop_sql) === TRUE) {
        echo "Any existing barber_schedules table was dropped<br>";
    } else {
        echo "Warning: Could not drop existing table: " . $conn->error . "<br>";
    }
    
    // Create barber_schedules table with explicit backticks
    $sql = "CREATE TABLE `barber_schedules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `barber_id` INT NOT NULL,
        `day_of_week` VARCHAR(10) NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `status` ENUM('available', 'unavailable') NOT NULL DEFAULT 'available',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`barber_id`) REFERENCES `barbers`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_barber_day` (`barber_id`, `day_of_week`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    echo "Attempting to create table...<br>";
    
    if ($conn->query($sql) === TRUE) {
        echo "Table barber_schedules created successfully<br>";
        
        // Verify table was created
        $check_table = $conn->query("SHOW TABLES LIKE 'barber_schedules'");
        if ($check_table && $check_table->num_rows > 0) {
            echo "Table verification successful<br>";
            
            // Get all barbers
            $barbers = $db->getAllBarbers();
            echo "Found " . count($barbers) . " barbers<br>";
            
            if (empty($barbers)) {
                echo "Warning: No barbers found in the system<br>";
                exit;
            }
            
            // Default schedule for each barber
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $default_start = '09:00:00';
            $default_end = '17:00:00';
            
            // Prepare insert statement
            $insert_sql = "INSERT INTO `barber_schedules` (`barber_id`, `day_of_week`, `start_time`, `end_time`, `status`) VALUES (?, ?, ?, ?, 'available')";
            $stmt = $conn->prepare($insert_sql);
            
            if ($stmt === false) {
                die("Error preparing insert statement: " . $conn->error . "<br>SQL: " . $insert_sql);
            }
            
            $success_count = 0;
            foreach ($barbers as $barber) {
                echo "Creating schedule for barber ID: " . $barber['id'] . "<br>";
                foreach ($days as $day) {
                    $stmt->bind_param("isss", $barber['id'], $day, $default_start, $default_end);
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        echo "Error creating schedule for barber {$barber['id']} on $day: " . $stmt->error . "<br>";
                    }
                }
            }
            
            echo "Successfully created $success_count schedule entries<br>";
            
        } else {
            echo "Error: Table was not created successfully<br>";
            if ($check_table === false) {
                echo "Error checking table: " . $conn->error . "<br>";
            }
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

// Verify the table exists and show its structure
try {
    $result = $conn->query("DESCRIBE `barber_schedules`");
    if ($result) {
        echo "<br>Table structure:<br>";
        echo "<pre>";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "<br>Could not verify table structure: " . $conn->error . "<br>";
    }
} catch (Exception $e) {
    echo "<br>Error verifying table structure: " . $e->getMessage() . "<br>";
}
?> 