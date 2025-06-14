<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$day = isset($_GET['day']) ? strtolower($_GET['day']) : '';
if (empty($day)) {
    echo json_encode([]);
    exit;
}

$db = new Database();

// Get the active barber
$barber = $db->getSingleBarber();
if (!$barber) {
    echo json_encode([]);
    exit;
}

$today = new DateTime();
$dates_found = 0;
$max_dates = 4;
$available_dates = [];
$current = clone $today;

while ($dates_found < $max_dates) {
    if (strtolower($current->format('l')) === $day) {
        $date_str = $current->format('Y-m-d');
        $available_dates[] = $date_str;
        $dates_found++;
    }
    $current->modify('+1 day');
}

echo json_encode($available_dates); 