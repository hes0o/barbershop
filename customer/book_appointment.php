<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Disable error display but enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Basic validation
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('Please log in as a customer to book appointments');
    }

    // Get and validate input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['service_id']) || !isset($data['date']) || !isset($data['time'])) {
        throw new Exception('Invalid booking data');
    }

    // Create appointment
    $db = new Database();
    
    // Get barber first to avoid potential null reference
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('No barber available at this time');
    }

    // Validate service exists
    $service = $db->getServiceById($data['service_id']);
    if (!$service) {
        throw new Exception('Selected service is not available');
    }

    // Check if barber is available at this time
    if (!$db->isBarberAvailable($barber['id'], $data['date'], $data['time'])) {
        throw new Exception('Selected time slot is not available');
    }

    // Create the appointment
    $result = $db->createAppointment(
        $_SESSION['user_id'],
        $barber['id'],
        $data['service_id'],
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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("System error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'A system error occurred. Please try again.'
    ]);
} 