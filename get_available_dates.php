<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$db = new Database();

// Get the single barber
$barber = $db->getSingleBarber();
if (!$barber) {
    http_response_code(400);
    echo json_encode(['error' => 'No barber is currently available']);
    exit;
}

// Get working hours
$working_hours = $db->getWorkingHours();
$working_hours_map = [];
foreach ($working_hours as $hours) {
    $working_hours_map[$hours['day_of_week']] = $hours;
}

// Get available dates for the next 14 days
$available_dates = [];
$today = date('Y-m-d');
$end_date = date('Y-m-d', strtotime('+14 days'));

$current_date = $today;
while ($current_date <= $end_date) {
    $day_of_week = date('N', strtotime($current_date)); // 1 (Monday) to 7 (Sunday)
    
    // Check if the barber works on this day
    if (isset($working_hours_map[$day_of_week]) && $working_hours_map[$day_of_week]['is_working']) {
        // Check if there's any specific availability set for this date
        $availability = $db->getBarberAvailability($barber['id'], $current_date);
        
        if (!$availability || $availability['is_available']) {
            $available_dates[] = [
                'date' => $current_date,
                'day_name' => date('l', strtotime($current_date)),
                'is_today' => $current_date === $today,
                'is_tomorrow' => $current_date === date('Y-m-d', strtotime('+1 day'))
            ];
        }
    }
    
    $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
}

// Return available dates
header('Content-Type: application/json');
echo json_encode(['dates' => $available_dates]);
?> 