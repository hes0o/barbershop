<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/error_handler.php';

// Prevent any output before JSON response
ob_start();

// Set error handler to prevent HTML output
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler('jsonErrorHandler');

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Verify user is logged in as customer
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('User not logged in or not a customer');
    }

    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }

    // Validate required fields
    if (!isset($data['service_id']) || !isset($data['date']) || !isset($data['time'])) {
        throw new Exception('Missing required fields');
    }

    // Initialize database connection
    $db = new Database();
    
    // Get customer and barber data
    $customer = $db->getUserById($_SESSION['user_id']);
    $barber = $db->getSingleBarber();
    
    if (!$customer || !$barber) {
        throw new Exception('Customer or barber not found');
    }

    // Validate service exists
    $service = $db->getServiceById($data['service_id']);
    if (!$service) {
        throw new Exception('Service not found');
    }

    // Create the appointment
    $result = $db->createAppointment(
        $customer['id'],
        $barber['id'],
        $data['service_id'],
        $data['date'],
        $data['time']
    );

    if (!$result) {
        throw new Exception('Failed to create appointment');
    }

    // Clear any output buffer
    ob_end_clean();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Appointment booked successfully'
    ]);

} catch (Exception $e) {
    // Clear any output buffer
    ob_end_clean();
    
    // Send error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 