<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db.php';

// Set JSON content type
header('Content-Type: application/json');

// Start output buffering
ob_start();

try {
    // Check if user is logged in and is a barber
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
        throw new Exception('Unauthorized access');
    }

    // Get barber info
    $db = new Database();
    $barber = $db->getSingleBarber();

    if (!$barber) {
        throw new Exception('Barber not found');
    }

    // Check if schedule already exists
    $existingSchedule = $db->getBarberWeeklySchedule($barber['id']);
    if ($existingSchedule && !empty($existingSchedule)) {
        // Schedule already exists, return success
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Schedule already initialized'
        ]);
        exit;
    }

    // Default schedule
    $defaultSchedule = [
        'monday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
        'thursday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
        'friday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
        'saturday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'available'],
        'sunday' => ['start' => '09:00', 'end' => '17:00', 'status' => 'unavailable']
    ];

    // Insert default schedule
    if ($db->updateBarberWeeklySchedule($barber['id'], $defaultSchedule)) {
        // Clear output buffer
        ob_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Schedule initialized successfully'
        ]);
    } else {
        throw new Exception('Failed to initialize schedule');
    }

} catch (Exception $e) {
    // Clear output buffer
    ob_clean();
    
    // Log the error
    error_log("Error initializing schedule: " . $e->getMessage());
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush(); 