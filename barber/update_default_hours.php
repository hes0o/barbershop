<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$db = new Database();
$barber = $db->getSingleBarber();

$default_start = $_POST['default_start'] ?? '09:00';
$default_end = $_POST['default_end'] ?? '17:00';
$break_duration = $_POST['break_duration'] ?? 30;

// Update default schedule for each day
$days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
$success = true;

foreach ($days as $day) {
    if (!$db->updateBarberDefaultSchedule($barber['id'], $day, $default_start, $default_end, 'available', $break_duration)) {
        $success = false;
        break;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'message' => $success ? 'Default hours updated successfully' : 'Failed to update default hours'
]); 