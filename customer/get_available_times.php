<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$date = isset($_GET['date']) ? $_GET['date'] : '';
if (empty($date)) {
    echo json_encode([]);
    exit;
}

$db = new Database();
$barber = $db->getSingleBarber();

if (!$barber) {
    echo json_encode([]);
    exit;
}

$available_times = $db->getAvailableTimeSlots($barber['id'], $date);
echo json_encode($available_times); 