<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate date parameter
if (!isset($_GET['date'])) {
    echo json_encode(['success' => false, 'message' => 'Date parameter is required']);
    exit;
}

$date = $_GET['date'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

$db = new Database();

// Get the active barber
$barber = $db->getSingleBarber();
if (!$barber) {
    echo json_encode(['success' => false, 'message' => 'No barber available']);
    exit;
}

// Get barber's weekly schedule
$weekly_schedule = $db->getBarberWeeklySchedule($barber['id']);
$day_of_week = strtolower(date('l', strtotime($date)));

// Check if barber is available on this day
if (!isset($weekly_schedule[$day_of_week]) || $weekly_schedule[$day_of_week]['status'] !== 'available') {
    echo json_encode(['success' => false, 'message' => 'Barber is not available on this day']);
    exit;
}

// Get start and end hours from the schedule
$start_hour = (int)date('G', strtotime($weekly_schedule[$day_of_week]['start']));
$end_hour = (int)date('G', strtotime($weekly_schedule[$day_of_week]['end']));

// Get existing appointments for this date
$existing_appointments = $db->getBarberAppointments($barber['id'], 'all', $date);

// Generate available time slots
$time_slots = [];
$current_hour = $start_hour;

while ($current_hour < $end_hour) {
    $time_slot = sprintf('%02d:00', $current_hour);
    $is_available = true;

    // Check if this time slot overlaps with any existing appointments
    foreach ($existing_appointments as $appointment) {
        $appointment_hour = (int)date('G', strtotime($appointment['appointment_time']));
        // Block the entire hour for each appointment
        if ($current_hour === $appointment_hour) {
            $is_available = false;
            break;
        }
    }

    if ($is_available) {
        $time_slots[] = $time_slot;
    }

    $current_hour++;
}

// Filter out past times for today
if ($date === date('Y-m-d')) {
    $current_hour = (int)date('G');
    $time_slots = array_filter($time_slots, function($time) use ($current_hour) {
        $slot_hour = (int)date('G', strtotime($time));
        return $slot_hour > $current_hour;
    });
}

echo json_encode([
    'success' => true,
    'times' => $time_slots
]); 