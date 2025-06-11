<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
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

// Get available dates for the next 14 days
$dates = [];
$currentDate = new DateTime();
$endDate = (new DateTime())->modify('+14 days');

while ($currentDate <= $endDate) {
    // Skip past dates
    if ($currentDate < new DateTime('today')) {
        $currentDate->modify('+1 day');
        continue;
    }

    $date_str = $currentDate->format('Y-m-d');
    $day_of_week = strtolower($currentDate->format('l')); // Get day name in lowercase

    // Check if the barber is available on this day according to their weekly schedule
    if (isset($weekly_schedule[$day_of_week]) && $weekly_schedule[$day_of_week]['status'] === 'available') {
        $dates[] = [
            'date' => $date_str,
            'day_name' => $currentDate->format('l')
        ];
    }

    $currentDate->modify('+1 day');
}

echo json_encode([
    'success' => true,
    'dates' => $dates
]); 