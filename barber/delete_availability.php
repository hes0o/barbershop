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

if (!isset($data['date'])) {
    echo json_encode(['success' => false, 'message' => 'Date is required']);
    exit;
}

$db = new Database();
$barber_id = $db->getBarberIdByUserId($_SESSION['user_id']);

if (!$barber_id) {
    echo json_encode(['success' => false, 'message' => 'Barber not found']);
    exit;
}

// Delete availability by setting is_available to false
$success = $db->setBarberAvailability(
    $barber_id,
    $data['date'],
    '00:00',
    '00:00',
    false
);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Availability deleted successfully' : 'Failed to delete availability'
]); 