<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a barber
requireLogin();
if ($_SESSION['role'] !== 'barber') {
    die(json_encode(['error' => 'Unauthorized']));
}

$date = $_GET['date'] ?? date('Y-m-d');
if (!validateDate($date)) {
    die(json_encode(['error' => 'Invalid date format']));
}

$db = new Database();
$barber_id = $db->getBarberIdByUserId($_SESSION['user_id']);

try {
    $appointments = $db->getBarberAppointments($barber_id, 'all', $date);
    echo json_encode($appointments);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 