<?php
// session_start(); // Removed - handled in config.php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/email_service.php';

header('Content-Type: application/json');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate required fields
if (!isset($_POST['date']) || !isset($_POST['time']) || !isset($_POST['service_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$date = $_POST['date'];
$time = $_POST['time'];
$service_id = $_POST['service_id'];
$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    
    // Get the active barber
    $barber = $db->getSingleBarber();
    if (!$barber) {
        echo json_encode(['success' => false, 'message' => 'No barber available']);
        exit;
    }

    // Check if the time slot is still available
    if (!$db->isBarberAvailable($barber['id'], $date, $time)) {
        echo json_encode(['success' => false, 'message' => 'This time slot is no longer available']);
        exit;
    }

    // Create the appointment
    $appointment_id = $db->createAppointment($user_id, $barber['id'], $service_id, $date, $time);
    
    if ($appointment_id) {
        // Send notification email to barber
        $emailService = new EmailService();
        $emailResult = $emailService->sendBarberNotification($appointment_id);
        
        if (!$emailResult) {
            error_log("Failed to send barber notification for appointment ID: $appointment_id");
            // Don't fail the booking if email fails, just log it
        }

        echo json_encode(['success' => true, 'message' => 'Appointment request sent successfully. The barber will review and confirm your appointment.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment']);
    }
} catch (Exception $e) {
    error_log("Error in book_appointment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while booking the appointment']);
}
