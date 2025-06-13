<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/schedule_sync.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    // Get date from request
    if (!isset($_GET['date'])) {
        throw new Exception('Date is required');
    }

    $date = $_GET['date'];
    
    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        throw new Exception('Invalid date format');
    }

    // Get the day of week
    $day_of_week = strtolower(date('l', strtotime($date)));

    $db = new Database();
    
    // Get the active barber first
    $barber = $db->getSingleBarber();
    if (!$barber) {
        throw new Exception('No barber available');
    }
    
    // Get barber's schedule for this day
    $stmt = $db->getConnection()->prepare("
        SELECT start_time, end_time, status
        FROM barber_schedule
        WHERE barber_id = ? AND day_of_week = ?
        LIMIT 1
    ");
    
    if (!$stmt) {
        throw new Exception("Error preparing schedule query: " . $db->getConnection()->error);
    }
    
    $stmt->bind_param("is", $barber['id'], $day_of_week);
    $stmt->execute();
    $stmt->bind_result($start_time, $end_time, $status);
    
    if (!$stmt->fetch()) {
        throw new Exception("No schedule found for this day");
    }
    
    $stmt->close();

    // If barber is unavailable, return empty array
    if ($status === 'unavailable') {
        echo json_encode(['success' => true, 'times' => []]);
        exit;
    }

    // Get existing appointments for this date and barber
    $stmt = $db->getConnection()->prepare("
        SELECT a.appointment_time, s.duration
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        WHERE a.appointment_date = ? 
        AND a.barber_id = ?
        AND a.status != 'cancelled'
    ");
    
    if (!$stmt) {
        throw new Exception("Error preparing appointments query: " . $db->getConnection()->error);
    }
    
    $stmt->bind_param("si", $date, $barber['id']);
    $stmt->execute();
    $stmt->bind_result($appointment_time, $duration);
    
    $booked_slots = [];
    while ($stmt->fetch()) {
        $start = strtotime($appointment_time);
        $end = $start + ($duration * 60); // Convert duration to seconds
        $booked_slots[] = [
            'start' => $start,
            'end' => $end
        ];
    }
    
    $stmt->close();

    // Generate available time slots
    $start_timestamp = strtotime($start_time);
    $end_timestamp = strtotime($end_time);
    $interval = 30 * 60; // 30 minutes in seconds
    $available_times = [];

    // Filter out past times for today
    $now = time();
    if ($date === date('Y-m-d')) {
        $start_timestamp = max($start_timestamp, $now + 3600); // Add 1 hour buffer
    }

    for ($time = $start_timestamp; $time < $end_timestamp; $time += $interval) {
        $is_available = true;
        $slot_end = $time + $interval;

        // Check if this slot overlaps with any booked appointments
        foreach ($booked_slots as $booked) {
            if (($time >= $booked['start'] && $time < $booked['end']) ||
                ($slot_end > $booked['start'] && $slot_end <= $booked['end']) ||
                ($time <= $booked['start'] && $slot_end >= $booked['end'])) {
                $is_available = false;
                break;
            }
        }

        if ($is_available) {
            $available_times[] = date('H:i', $time);
        }
    }

    echo json_encode([
        'success' => true,
        'times' => $available_times
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 