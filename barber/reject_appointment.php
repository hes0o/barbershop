<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
    exit;
}

$appointment_id = intval($input['appointment_id']);
$barber_user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    
    // Get the barber ID for the logged-in user
    $barber_id = $db->getBarberIdByUserId($barber_user_id);
    if (!$barber_id) {
        echo json_encode(['success' => false, 'message' => 'Barber profile not found']);
        exit;
    }
    
    // Get the appointment details
    $appointment = $db->getAppointmentById($appointment_id);
    if (!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    // Verify this appointment belongs to this barber
    if ($appointment['barber_id'] != $barber_id) {
        echo json_encode(['success' => false, 'message' => 'This appointment does not belong to you']);
        exit;
    }
    
    // Check if appointment is pending
    if ($appointment['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Appointment is not pending approval']);
        exit;
    }
    
    // Reject the appointment
    $success = $db->updateAppointmentStatus($appointment_id, 'rejected');
    if ($success) {
        // Log the activity
        $db->logActivity($barber_user_id, 'reject_appointment', "Rejected appointment ID: $appointment_id");
        
        echo json_encode(['success' => true, 'message' => 'Appointment rejected successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reject appointment']);
    }
} catch (Exception $e) {
    error_log("Error in reject_appointment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while rejecting the appointment']);
} 