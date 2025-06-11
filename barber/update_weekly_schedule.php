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

    // Get and validate schedule data
    $schedule = $_POST['schedule'] ?? [];
    if (empty($schedule)) {
        throw new Exception('No schedule data provided');
    }

    // Validate each day's schedule
    $valid_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $valid_statuses = ['available', 'unavailable'];

    foreach ($schedule as $day => $data) {
        if (!in_array($day, $valid_days)) {
            throw new Exception("Invalid day: $day");
        }

        if (!isset($data['start']) || !isset($data['end']) || !isset($data['status'])) {
            throw new Exception("Missing required fields for $day");
        }

        // Validate time format
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['start']) || 
            !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['end'])) {
            throw new Exception("Invalid time format for $day");
        }

        // Validate end time is after start time
        if (strtotime($data['end']) <= strtotime($data['start'])) {
            throw new Exception("End time must be after start time for $day");
        }

        // Validate status
        if (!in_array($data['status'], $valid_statuses)) {
            throw new Exception("Invalid status for $day");
        }
    }

    // Begin transaction
    $db->getConnection()->begin_transaction();

    try {
        // Delete existing schedule
        $stmt = $db->getConnection()->prepare("DELETE FROM barber_schedule WHERE barber_id = ?");
        $stmt->bind_param("i", $barber['id']);
        $stmt->execute();

        // Insert new schedule
        $stmt = $db->getConnection()->prepare("
            INSERT INTO barber_schedule (barber_id, day_of_week, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($schedule as $day => $data) {
            $stmt->bind_param("issss", 
                $barber['id'],
                $day,
                $data['start'],
                $data['end'],
                $data['status']
            );
            $stmt->execute();
        }

        // Commit transaction
        $db->getConnection()->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Schedule updated successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->getConnection()->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error updating schedule: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 