<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

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
    )";

    if ($conn->query($sql) === TRUE) {
        echo "Table barber_schedules created successfully<br>";
        
        // Get all barbers
        $barbers = $db->getAllBarbers();
        
        // Default schedule for each barber
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $default_start = '09:00:00';
        $default_end = '17:00:00';
        
        // Prepare insert statement
        $stmt = $conn->prepare("INSERT IGNORE INTO barber_schedules (barber_id, day_of_week, start_time, end_time, status) VALUES (?, ?, ?, ?, 'available')");
        
        foreach ($barbers as $barber) {
            foreach ($days as $day) {
                $stmt->bind_param("isss", $barber['id'], $day, $default_start, $default_end);
                $stmt->execute();
            }
        }
        
        echo "Default schedules created for all barbers<br>";
        
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?> 