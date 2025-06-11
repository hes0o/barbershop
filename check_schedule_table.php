<?php
require_once 'config.php';
require_once 'includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Drop the existing table
    echo "Dropping existing barber_schedule table...\n";
    $conn->query("DROP TABLE IF EXISTS `barber_schedule`");

    // Create the table with correct structure
    echo "Creating new barber_schedule table...\n";
    $sql = "CREATE TABLE `barber_schedule` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `barber_id` int(11) NOT NULL,
        `day_of_week` varchar(10) NOT NULL,
        `start_time` time NOT NULL,
        `end_time` time NOT NULL,
        `status` enum('available','unavailable') NOT NULL DEFAULT 'available',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `barber_day` (`barber_id`, `day_of_week`),
        KEY `barber_id` (`barber_id`),
        CONSTRAINT `barber_schedule_ibfk_1` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating table: " . $conn->error);
    }

    echo "Table created successfully!\n";

    // Verify the table structure
    $result = $conn->query("DESCRIBE barber_schedule");
    echo "\nTable structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 