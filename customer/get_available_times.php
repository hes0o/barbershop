<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/schedule_sync.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get the date from the request
$date = isset($_GET['date']) ? $_GET['date'] : null;

if (!$date) {
    echo json_encode(['success' => false, 'error' => 'No date provided']);
    exit;
}

try {
    $db = new Database();
    $scheduleSync = new ScheduleSync();
    
    // Get the active barber
    $barber = $db->getSingleBarber();
    
    if (!$barber) {
        error_log("No active barber found in get_available_times.php");
        echo json_encode([
            'success' => false, 
            'error' => 'No active barber available. Please contact the administrator.'
        ]);
        exit;
    }
    
    // Get available time slots
    $time_slots = $scheduleSync->getAvailableTimeSlots($barber['id'], $date);
    
    // Sort time slots
    sort($time_slots);
    
    if (empty($time_slots)) {
        error_log("No available time slots found for barber {$barber['id']} on date {$date}");
        echo json_encode([
            'success' => true,
            'times' => [],
            'message' => 'No available time slots for this date'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'times' => $time_slots
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_available_times.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching available times'
    ]);
} 