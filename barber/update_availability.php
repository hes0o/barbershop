<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Prevent any output before JSON response
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON content type
header('Content-Type: application/json');

try {
    // Check if user is logged in and is a barber
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
        throw new Exception('Unauthorized access');
    }

    // Get the barber's ID
    $db = new Database();
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('Barber not found');
    }

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    $required_fields = ['date', 'is_available', 'start_time', 'end_time'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        throw new Exception('Invalid date format');
    }

    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start_time']) ||
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['end_time'])) {
        throw new Exception('Invalid time format');
    }

    // Convert boolean to integer for database
    $is_available = $data['is_available'] ? 1 : 0;

    // Update availability in database
    $success = $db->setBarberAvailability(
        $barber['id'],
        $data['date'],
        $data['start_time'],
        $data['end_time'],
        $is_available
    );

    if (!$success) {
        throw new Exception('Failed to update availability');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Availability updated successfully'
    ]);

} catch (Exception $e) {
    // Log the error
    error_log('Error in update_availability.php: ' . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Clear any output buffer
ob_end_flush(); 