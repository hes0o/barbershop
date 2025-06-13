<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schedule_sync.php';

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

    // Initialize database and get barber
    $db = new Database();
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('No barber available at this time');
    }

    // Initialize schedule sync
    $scheduleSync = new ScheduleSync();

    // Get database connection
    $conn = $db->getConnection();

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Check if the time slot is still available
        if (!$scheduleSync->validateBookingTime($barber['id'], $data['date'], $data['time'], $service_id)) {
            throw new Exception('Selected time slot is no longer available');
        }

        // Insert appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (
                user_id, barber_id, service_id, 
                appointment_date, appointment_time, 
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");

        if (!$stmt) {
            throw new Exception('Failed to prepare appointment statement: ' . $conn->error);
        }

        // Use a test user ID (1)
        $test_user_id = 1;
        
        $stmt->bind_param(
            "iiiss",
            $test_user_id,
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