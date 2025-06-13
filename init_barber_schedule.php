<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

try {
    $db = new Database();
    $barber = $db->getSingleBarber();
    
    if (!$barber) {
        throw new Exception('No barber found');
    }
    
    // Default schedule for Monday to Friday
    $defaultSchedule = [
        'monday' => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'available'
        ],
        'tuesday' => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'available'
        ],
        'wednesday' => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'available'
        ],
        'thursday' => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'available'
        ],
        'friday' => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'available'
        ],
        'saturday' => [
            'start_time' => '10:00:00',
            'end_time' => '15:00:00',
            'status' => 'available'
        ],
        'sunday' => [
            'start_time' => '00:00:00',
            'end_time' => '00:00:00',
            'status' => 'unavailable'
        ]
    ];
    
    // Update the schedule
    $scheduleSync = new ScheduleSync();
    if ($scheduleSync->updateBarberSchedule($barber['id'], $defaultSchedule)) {
        echo "Barber schedule initialized successfully!\n";
    } else {
        throw new Exception('Failed to initialize barber schedule');
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?> 