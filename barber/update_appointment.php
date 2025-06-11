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
    // Check if user is logged in and is a barber
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
        throw new Exception('Unauthorized access');
    }

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    if (!isset($data['appointment_id']) || !isset($data['status'])) {
        throw new Exception('Missing required fields');
    }

    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($data['status'], $valid_statuses)) {
        throw new Exception('Invalid status');
    }

    $db = new Database();

    // Update the appointment status
    $success = $db->updateAppointmentStatus($data['appointment_id'], $data['status']);

    if (!$success) {
        throw new Exception('Failed to update appointment status');
    }

    // Clear any output and send success response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Appointment status updated successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in update_appointment.php: " . $e->getMessage());
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