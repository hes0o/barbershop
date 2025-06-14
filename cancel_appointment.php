<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Only allow logged-in customers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST with JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['appointment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
    exit;
}

$appointment_id = intval($input['appointment_id']);
$user_id = $_SESSION['user_id'];

$db = new Database();
$appointment = $db->getAppointmentById($appointment_id);

if (!$appointment || $appointment['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Appointment not found or not yours']);
    exit;
}

// Cancel the appointment
$success = $db->updateAppointmentStatus($appointment_id, 'cancelled');
if ($success) {
    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment']);
} 