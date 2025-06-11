<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

try {
    session_start();
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../includes/db.php';

    // Ensure we're sending JSON response
    header('Content-Type: application/json');

    // Check if user is logged in and is a barber
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
        throw new Exception('Unauthorized access');
    }

    $db = new Database();
    $barber = $db->getSingleBarber();

    if (!$barber) {
        throw new Exception('Barber not found');
    }

    // Get the weekly schedule
    $schedule = $db->getBarberWeeklySchedule($barber['id']);

    // If no schedule exists, return default values
    if (empty($schedule)) {
        $schedule = [
            'monday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
            'thursday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
            'friday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
            'saturday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
            'sunday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'unavailable']
        ];
    }

    // Clear any output buffer
    ob_clean();

    // Send success response
    echo json_encode([
        'success' => true,
        'schedule' => $schedule
    ]);

} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log the error
    error_log("Error getting weekly schedule: " . $e->getMessage());
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush(); 