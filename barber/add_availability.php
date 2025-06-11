<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Ensure we're sending JSON response
header('Content-Type: application/json');

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = new Database();
    $barber = $db->getSingleBarber();

    // Validate and sanitize inputs
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (!$date || !$start_time || !$end_time) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid date format']);
        exit;
    }

    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid time format']);
        exit;
    }

    // Validate date is not in the past
    if (strtotime($date) < strtotime('today')) {
        echo json_encode(['success' => false, 'message' => 'Cannot set availability for past dates']);
        exit;
    }

    // Validate end time is after start time
    if (strtotime($end_time) <= strtotime($start_time)) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time']);
        exit;
    }

    // Validate status
    $valid_statuses = ['available', 'unavailable', 'break'];
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Check for existing appointments in this time slot
    $existing_appointments = $db->getAppointmentsInTimeSlot($barber['id'], $date, $start_time, $end_time);
    if (!empty($existing_appointments)) {
        echo json_encode(['success' => false, 'message' => 'There are existing appointments in this time slot']);
        exit;
    }

    // Add the availability override
    $success = $db->addBarberAvailabilityOverride(
        $barber['id'],
        $date,
        $start_time,
        $end_time,
        $status
    );

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Time slot added successfully'
        ]);
    } else {
        throw new Exception('Failed to add time slot');
    }
} catch (Exception $e) {
    error_log("Error adding availability: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding the time slot'
    ]);
} 