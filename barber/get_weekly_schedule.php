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
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $db = new Database();
    $barber = $db->getSingleBarber();

    if (!$barber) {
        echo json_encode(['success' => false, 'message' => 'Barber not found']);
        exit;
    }

    // Get the weekly schedule
    $schedule = $db->getBarberWeeklySchedule($barber['id']);

    // If no schedule exists, return default values with correct keys
    if (empty($schedule)) {
        $schedule = [
            'monday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'available'],
            'tuesday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'available'],
            'wednesday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'available'],
            'thursday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'available'],
            'friday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'available'],
            'saturday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'available'],
            'sunday' => ['start_time' => '09:00', 'end_time' => '17:00', 'status' => 'unavailable']
        ];
    } else {
        // Ensure all keys are 'start_time' and 'end_time' for consistency
        foreach ($schedule as $day => $info) {
            if (isset($info['start'])) {
                $schedule[$day]['start_time'] = $info['start'];
                unset($schedule[$day]['start']);
            }
            if (isset($info['end'])) {
                $schedule[$day]['end_time'] = $info['end'];
                unset($schedule[$day]['end']);
            }
        }
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