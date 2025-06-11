<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Start output buffering to catch any PHP errors
ob_start();

try {
    // Log the raw POST data
    error_log("Raw POST data: " . print_r($_POST, true));
    
    // Check if user is logged in and is a barber
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
        throw new Exception('Unauthorized access');
    }
    error_log("User authenticated: " . $_SESSION['user_id']);

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get barber ID
    $db = new Database();
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('Barber not found');
    }
    error_log("Barber found: " . print_r($barber, true));

    // Check if barber_schedule table exists, if not create it
    $conn = $db->getConnection();
    $result = $conn->query("SHOW TABLES LIKE 'barber_schedule'");
    if ($result->num_rows === 0) {
        error_log("Creating barber_schedule table...");
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
        error_log("barber_schedule table created successfully");
    }

    // Validate schedule data
    if (!isset($_POST['schedule']) || !is_array($_POST['schedule'])) {
        throw new Exception('Invalid schedule data');
    }
    error_log("Schedule data received: " . print_r($_POST['schedule'], true));

    $schedule = [];
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    // Process and validate each day's schedule
    foreach ($days as $day) {
        if (!isset($_POST['schedule'][$day])) {
            throw new Exception("Missing schedule data for $day");
        }

        $dayData = $_POST['schedule'][$day];
        error_log("Processing day $day: " . print_r($dayData, true));
        
        // Validate required fields
        if (!isset($dayData['start_time']) || !isset($dayData['end_time']) || !isset($dayData['status'])) {
            throw new Exception("Missing required fields for $day");
        }

        // Validate time format
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dayData['start_time']) || 
            !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $dayData['end_time'])) {
            throw new Exception("Invalid time format for $day");
        }

        // Validate status
        if (!in_array($dayData['status'], ['available', 'unavailable'])) {
            throw new Exception("Invalid status for $day");
        }

        $schedule[$day] = [
            'start_time' => $dayData['start_time'],
            'end_time' => $dayData['end_time'],
            'status' => $dayData['status']
        ];
    }

    error_log("Processed schedule data: " . print_r($schedule, true));

    // Update the schedule in the database
    try {
        $db->updateBarberWeeklySchedule($barber['id'], $schedule);
        
        // Clear any output and send success response
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Schedule updated successfully'
        ]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        throw new Exception('Failed to update schedule: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Error in update_schedule.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output and send error response
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush(); 