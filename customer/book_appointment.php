<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/error_handler.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Start output buffering to catch any PHP errors
ob_start();

try {
    // Log session data
    error_log("Session data: " . print_r($_SESSION, true));

    // Check if user is logged in and is a customer
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('Unauthorized access - User not logged in or not a customer');
    }

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    error_log("Received raw JSON data: " . $json);
    
    $data = json_decode($json, true);
    error_log("Decoded request data: " . print_r($data, true));

    if (!$data) {
        throw new Exception('Invalid request data: ' . json_last_error_msg());
    }

    // Validate required fields
    $required_fields = ['service_id', 'date', 'time'];
    $missing_fields = array_filter($required_fields, function($field) use ($data) {
        return !isset($data[$field]);
    });

    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        throw new Exception('Invalid date format. Expected YYYY-MM-DD, got: ' . $data['date']);
    }

    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
        throw new Exception('Invalid time format. Expected HH:MM, got: ' . $data['time']);
    }

    $db = new Database();
    error_log("Database connection established");

    // Get customer info
    $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    if (!$stmt) {
        throw new Exception('Database error preparing customer query: ' . $db->getConnection()->error);
    }

    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception('Database error executing customer query: ' . $stmt->error);
    }

    $customer = $stmt->get_result()->fetch_assoc();
    error_log("Customer data: " . print_r($customer, true));

    if (!$customer) {
        throw new Exception('Customer not found. Please log out and log in again.');
    }

    // Get barber info
    $barber = $db->getSingleBarber();
    error_log("Barber data: " . print_r($barber, true));

    if (!$barber) {
        throw new Exception('No barber available at this time. Please try again later.');
    }

    // Get service info
    $service = $db->getServiceById($data['service_id']);
    error_log("Service data: " . print_r($service, true));

    if (!$service) {
        throw new Exception('Selected service is not available. Please choose a different service.');
    }

    // Check if the date is in the past
    if (strtotime($data['date']) < strtotime('today')) {
        throw new Exception('Cannot book appointments in the past. Please select a future date.');
    }

    // Check if the barber is available on this day
    $weekly_schedule = $db->getBarberWeeklySchedule($barber['id']);
    error_log("Weekly schedule: " . print_r($weekly_schedule, true));
    
    $day_of_week = strtolower(date('l', strtotime($data['date'])));
    error_log("Day of week: " . $day_of_week);

    if (!isset($weekly_schedule[$day_of_week]) || $weekly_schedule[$day_of_week]['status'] !== 'available') {
        throw new Exception('Barber is not available on ' . ucfirst($day_of_week) . '. Please select a different day.');
    }

    // Check if the time slot is within barber's working hours
    $start_hour = (int)date('G', strtotime($weekly_schedule[$day_of_week]['start']));
    $end_hour = (int)date('G', strtotime($weekly_schedule[$day_of_week]['end']));
    $booking_hour = (int)date('G', strtotime($data['time']));
    error_log("Time check - Start: $start_hour, End: $end_hour, Booking: $booking_hour");

    if ($booking_hour < $start_hour || $booking_hour >= $end_hour) {
        throw new Exception(sprintf(
            'Selected time is outside barber\'s working hours. Available hours: %02d:00 - %02d:00',
            $start_hour,
            $end_hour
        ));
    }

    // Check if the time slot is already booked
    $existing_appointments = $db->getBarberAppointments($barber['id'], 'all', $data['date']);
    error_log("Existing appointments: " . print_r($existing_appointments, true));
    
    foreach ($existing_appointments as $appointment) {
        $appointment_hour = (int)date('G', strtotime($appointment['appointment_time']));
        $booking_hour = (int)date('G', strtotime($data['time']));
        
        if ($booking_hour === $appointment_hour) {
            throw new Exception('This time slot is already booked. Please select a different time.');
        }
    }

    // Check if customer already has an appointment at this time
    $customer_appointments = $db->getAppointmentsByCustomer($_SESSION['user_id']);
    error_log("Customer appointments: " . print_r($customer_appointments, true));
    
    foreach ($customer_appointments as $appointment) {
        if ($appointment['appointment_date'] === $data['date'] && 
            $appointment['appointment_time'] === $data['time']) {
            throw new Exception('You already have an appointment at this time. Please select a different time.');
        }
    }

    // Create the appointment
    error_log("Attempting to create appointment with data: " . print_r([
        'user_id' => $_SESSION['user_id'],
        'barber_id' => $barber['id'],
        'service_id' => $data['service_id'],
        'date' => $data['date'],
        'time' => $data['time']
    ], true));

    $success = $db->createAppointment(
        $_SESSION['user_id'],
        $barber['id'],
        $data['service_id'],
        $data['date'],
        $data['time']
    );

    if (!$success) {
        throw new Exception('Failed to create appointment. Please try again.');
    }

    error_log("Appointment created successfully");

    // Clear any output and send success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully'
    ]);

} catch (Exception $e) {
    // Log the error using our error handler
    $error = ErrorHandler::handleException($e);
    error_log("Error in book_appointment.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output and send error response
    ob_clean();
    http_response_code(400);
    echo json_encode(ErrorHandler::formatErrorResponse($error));
}

// End output buffering
ob_end_flush(); 