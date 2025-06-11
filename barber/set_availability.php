<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['date']) || !isset($data['start_time']) || !isset($data['end_time'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$db = new Database();
$barber_id = $db->getBarberIdByUserId($_SESSION['user_id']);

if (!$barber_id) {
    echo json_encode(['success' => false, 'message' => 'Barber not found']);
    exit;
}

// Set availability
$success = $db->setBarberAvailability(
    $barber_id,
    $data['date'],
    $data['start_time'],
    $data['end_time'],
    $data['is_available'] ?? true
);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Availability set successfully' : 'Failed to set availability'
]); 