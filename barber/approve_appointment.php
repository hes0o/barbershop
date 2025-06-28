<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_service.php';

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
    
    // Approve the appointment
    $success = $db->updateAppointmentStatus($appointment_id, 'confirmed');
    if ($success) {
        // Send confirmation email to customer
        $emailService = new EmailService();
        $emailResult = $emailService->sendCustomerConfirmation($appointment_id);
        
        if (!$emailResult) {
            error_log("Failed to send customer confirmation for appointment ID: $appointment_id");
            // Don't fail the approval if email fails, just log it
        }
        
        // Log the activity
        $db->logActivity($barber_user_id, 'approve_appointment', "Approved appointment ID: $appointment_id");
        
        echo json_encode(['success' => true, 'message' => 'Appointment approved and confirmation email sent to customer']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to approve appointment']);
    }
} catch (Exception $e) {
    error_log("Error in approve_appointment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while approving the appointment']);
} 