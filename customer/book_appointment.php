<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/schedule_sync.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client
ini_set('log_errors', 1); // Log errors instead

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('Please log in to book an appointment');
    }

    // Get and validate input
    $raw_input = file_get_contents('php://input');
    $data = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid request data');
    }

    // Validate required fields
    $required_fields = ['service_id', 'date', 'time'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate and sanitize input
    $service_id = filter_var($data['service_id'], FILTER_VALIDATE_INT);
    if ($service_id === false) {
        throw new Exception('Invalid service selection');
    }

    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
        throw new Exception('Invalid date format');
    }

    // Validate time format (HH:MM)
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
        throw new Exception('Invalid time format');
    }

    // Initialize database and schedule sync
    $db = new Database();
    $scheduleSync = new ScheduleSync();

    // Get active barber
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('No barber available at this time');
    }

    // Validate the booking time
    if (!$scheduleSync->validateBookingTime($barber['id'], $data['date'], $data['time'], $service_id)) {
        throw new Exception('Selected time slot is not available');
    }

    // Get database connection
    $conn = $db->getConnection();

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (
                user_id, barber_id, service_id, 
                appointment_date, appointment_time, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param(
            "iiiss",
            $_SESSION['user_id'],
            $barber['id'],
            $service_id,
            $data['date'],
            $data['time']
        );

        if (!$stmt->execute()) {
            throw new Exception('Failed to create appointment: ' . $stmt->error);
        }

        $appointment_id = $conn->insert_id;

        // Commit transaction
        $conn->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully',
            'appointment_id' => $appointment_id
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

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
