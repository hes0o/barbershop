<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Log the raw input for debugging
    $raw_input = file_get_contents('php://input');
    error_log("Raw input: " . $raw_input);

    // Basic validation
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('Please log in as a customer to book appointments');
    }

    // Get and validate input
    $data = json_decode($raw_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Log the decoded data
    error_log("Decoded data: " . print_r($data, true));

    // Validate required fields
    $required_fields = ['service_id', 'date', 'time'];
    $missing_fields = array_filter($required_fields, function($field) use ($data) {
        return !isset($data[$field]) || $data[$field] === '';
    });

    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }

    // Validate and sanitize input
    $service_id = filter_var($data['service_id'], FILTER_VALIDATE_INT);
    if ($service_id === false) {
        throw new Exception('Invalid service selection');
    }

    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        throw new Exception('Invalid date format. Expected YYYY-MM-DD');
    }

    // Validate time format (HH:MM)
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
        throw new Exception('Invalid time format. Expected HH:MM');
    }

    // Validate date is not in the past
    $appointment_date = strtotime($data['date']);
    $today = strtotime('today');
    if ($appointment_date < $today) {
        throw new Exception('Cannot book appointments in the past');
    }

    // Create appointment
    $db = new Database();
    error_log("Database connection created");
    
    // Get barber first to avoid potential null reference
    $barber = $db->getSingleBarber();
    error_log("Barber data: " . print_r($barber, true));
    
    if (!$barber) {
        throw new Exception('No barber available at this time');
    }

    // Validate service exists
    $service = $db->getServiceById($service_id);
    error_log("Service data: " . print_r($service, true));
    
    if (!$service) {
        throw new Exception('Selected service is not available');
    }

    // Check if barber is available at this time
    error_log("Checking barber availability for date: {$data['date']}, time: {$data['time']}");
    $is_available = $db->isBarberAvailable($barber['id'], $data['date'], $data['time']);
    error_log("Barber availability result: " . ($is_available ? 'true' : 'false'));
    
    if (!$is_available) {
        throw new Exception('Selected time slot is not available');
    }

    // Create the appointment
    error_log("Attempting to create appointment with data: " . print_r([
        'user_id' => $_SESSION['user_id'],
        'barber_id' => $barber['id'],
        'service_id' => $service_id,
        'date' => $data['date'],
        'time' => $data['time']
    ], true));
    
    $result = $db->createAppointment(
        $_SESSION['user_id'],
        $barber['id'],
        $service_id,
        $data['date'],
        $data['time']
    );

    if (!$result) {
        throw new Exception('Unable to book appointment. Please try again.');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully'
    ]);

} catch (Exception $e) {
    error_log("Booking error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("System error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'A system error occurred. Please try again.'
    ]);
} 