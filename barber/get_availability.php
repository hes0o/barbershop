<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = new Database();
$barber_id = $db->getBarberIdByUserId($_SESSION['user_id']);

if (!$barber_id) {
    echo json_encode(['success' => false, 'message' => 'Barber not found']);
    exit;
}

// Get all availability records for the next 14 days
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+14 days'));

$availability = $db->getBarberAvailability($barber_id, $start_date, $end_date);

// Format the data for display
$formatted_availability = [];
foreach ($availability as $date => $data) {
    $formatted_availability[] = [
        'date' => $date,
        'start_time' => $data['start_time'],
        'end_time' => $data['end_time'],
        'is_available' => $data['is_available']
    ];
}

echo json_encode([
    'success' => true,
    'availability' => $formatted_availability
]); 