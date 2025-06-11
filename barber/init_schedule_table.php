<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Drop existing table if it exists
    $conn->query("DROP TABLE IF EXISTS `barber_schedule`");
    
    // Create a simpler barber_schedule table
    $sql = "CREATE TABLE `barber_schedule` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `barber_id` int(11) NOT NULL,
        `day_of_week` varchar(10) NOT NULL,
        `start_hour` int(2) NOT NULL,
        `end_hour` int(2) NOT NULL,
        `is_available` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `barber_day` (`barber_id`, `day_of_week`),
        KEY `barber_id` (`barber_id`),
        CONSTRAINT `barber_schedule_ibfk_1` FOREIGN KEY (`barber_id`) REFERENCES `barbers` (`id`) ON DELETE CASCADE,
        CHECK (`start_hour` >= 0 AND `start_hour` <= 23),
        CHECK (`end_hour` >= 0 AND `end_hour` <= 23)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$conn->query($sql)) {
        throw new Exception("Error creating table: " . $conn->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Barber schedule table created successfully with simplified structure'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error creating table: ' . $e->getMessage()
    ]);
} 