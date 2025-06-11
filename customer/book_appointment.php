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
    // Check if user is logged in and is a customer
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('Unauthorized access');
    }

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    if (!isset($data['service_id']) || !isset($data['date']) || !isset($data['time'])) {
        throw new Exception('Missing required fields');
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        throw new Exception('Invalid date format');
    }

    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
        throw new Exception('Invalid time format');
    }

    $db = new Database();

    // Get customer info - FIXED: Use the logged-in user's ID
    $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();

    if (!$customer) {
        throw new Exception('Customer not found');
    }

    // Get barber info
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('No barber available');
    }

    // Get service info
    $service = $db->getServiceById($data['service_id']);
    if (!$service) {
        throw new Exception('Service not found');
    }

    // Check if the date is in the past
    if (strtotime($data['date']) < strtotime('today')) {
        throw new Exception('Cannot book appointments in the past');
    }

    // Check if the barber is available on this day
    $weekly_schedule = $db->getBarberWeeklySchedule($barber['id']);
    $day_of_week = strtolower(date('l', strtotime($data['date'])));

    if (!isset($weekly_schedule[$day_of_week]) || $weekly_schedule[$day_of_week]['status'] !== 'available') {
        throw new Exception('Barber is not available on this day');
    }

    // Check if the time slot is within barber's working hours
    $start_hour = (int)date('G', strtotime($weekly_schedule[$day_of_week]['start']));
    $end_hour = (int)date('G', strtotime($weekly_schedule[$day_of_week]['end']));
    $booking_hour = (int)date('G', strtotime($data['time']));

    if ($booking_hour < $start_hour || $booking_hour >= $end_hour) {
        throw new Exception('Selected time is outside barber\'s working hours');
    }

    // Check if the time slot is already booked
    $existing_appointments = $db->getBarberAppointments($barber['id'], 'all', $data['date']);
    foreach ($existing_appointments as $appointment) {
        $appointment_hour = (int)date('G', strtotime($appointment['appointment_time']));
        $booking_hour = (int)date('G', strtotime($data['time']));
        
        // Check if the booking hour is already taken
        if ($booking_hour === $appointment_hour) {
            throw new Exception('This time slot is already booked');
        }
    }

    // Check if customer already has an appointment at this time
    $customer_appointments = $db->getAppointmentsByCustomer($_SESSION['user_id']); // FIXED: Use session user_id
    foreach ($customer_appointments as $appointment) {
        if ($appointment['appointment_date'] === $data['date'] && 
            $appointment['appointment_time'] === $data['time']) {
            throw new Exception('You already have an appointment at this time');
        }
    }

    // Create the appointment - FIXED: Use session user_id
    $success = $db->createAppointment(
        $_SESSION['user_id'],
        $barber['id'],
        $data['service_id'],
        $data['date'],
        $data['time']
    );

    if (!$success) {
        throw new Exception('Failed to create appointment');
    }

    // Clear any output and send success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in book_appointment.php: " . $e->getMessage());
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