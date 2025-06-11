<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start output buffering to catch any unexpected output
ob_start();

try {
    require_once '../includes/config.php';
    require_once '../includes/db.php';

    // Set JSON content type
    header('Content-Type: application/json');

    session_start();

    // Check if user is logged in and is a barber
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
        throw new Exception('Unauthorized access');
    }

    $db = new Database();
    $barber = $db->getSingleBarber();

    if (!$barber) {
        throw new Exception('Barber not found');
    }

    // Get month parameter
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    list($year, $month) = explode('-', $month);

    // Get appointments for the month
    $appointments = $db->getBarberAppointments($barber['id'], 'all', null, '', $year, $month);

    // Get weekly schedule
    $schedule = $db->getBarberWeeklySchedule($barber['id']);

    // Clear any output buffer
    ob_clean();

    // Send success response
    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'schedule' => $schedule
    ]);

} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log the error
    error_log("Error getting calendar data: " . $e->getMessage());
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush(); 