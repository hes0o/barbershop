<?php
require_once 'config.php';
require_once 'includes/db.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_GET['service_id']) || !isset($_GET['date'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$service_id = $_GET['service_id'];
$date = $_GET['date'];

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

try {
    $db = new Database();
    
    // Get service duration
    $service = $db->getServiceById($service_id);
    if (!$service) {
        echo json_encode(['error' => 'Invalid service']);
        exit;
    }
    
    // Get the first available barber
    $barber = $db->getSingleBarber();
    if (!$barber) {
        echo json_encode(['error' => 'No barbers available']);
        exit;
    }
    
    // Get working hours for the day
    $day_of_week = date('N', strtotime($date));
    $stmt = $db->getConnection()->prepare("
        SELECT * FROM working_hours 
        WHERE day_of_week = ?
    ");
    $stmt->bind_param("i", $day_of_week);
    $stmt->execute();
    $working_hours = $stmt->get_result()->fetch_assoc();
    
    if (!$working_hours) {
        echo json_encode(['error' => 'No working hours set for this day']);
        exit;
    }
    
    if (!$working_hours['is_working']) {
        echo json_encode(['error' => 'Not open on this day']);
        exit;
    }
    
    // Get barber's specific availability for this date
    $availability = $db->getBarberAvailability($barber['id'], $date);
    
    // If barber has marked themselves as unavailable for this date
    if ($availability && !$availability['is_available']) {
        echo json_encode(['error' => 'Barber is not available on this date']);
        exit;
    }
    
    // Set time range based on availability or working hours
    $start_time = $availability && $availability['is_available'] ? 
                 $availability['start_time'] : $working_hours['open_time'];
    $end_time = $availability && $availability['is_available'] ? 
               $availability['end_time'] : $working_hours['close_time'];
    
    // Get all booked appointments for this date
    $stmt = $db->getConnection()->prepare("
        SELECT a.appointment_time, s.duration
        FROM appointments a
        JOIN services s ON a.service_id = s.id
        WHERE a.barber_id = ? 
        AND a.appointment_date = ?
        AND a.status != 'cancelled'
    ");
    $stmt->bind_param("is", $barber['id'], $date);
    $stmt->execute();
    $booked_slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Create a map of booked time slots
    $booked_times = [];
    foreach ($booked_slots as $slot) {
        $start = strtotime($slot['appointment_time']);
        $end = $start + ($slot['duration'] * 60);
        $booked_times[] = [
            'start' => $start,
            'end' => $end
        ];
    }
    
    // Generate time slots
    $slots = [];
    $current_time = strtotime($start_time);
    $end_timestamp = strtotime($end_time);
    $duration = $service['duration'];
    
    while ($current_time + ($duration * 60) <= $end_timestamp) {
        $time = date('H:i:s', $current_time);
        $slot_end = $current_time + ($duration * 60);
        
        // Check if this slot overlaps with any booked slots
        $is_available = true;
        foreach ($booked_times as $booked) {
            if (
                ($current_time >= $booked['start'] && $current_time < $booked['end']) ||
                ($slot_end > $booked['start'] && $slot_end <= $booked['end']) ||
                ($current_time <= $booked['start'] && $slot_end >= $booked['end'])
            ) {
                $is_available = false;
                break;
            }
        }
        
        $slots[] = [
            'time' => $time,
            'available' => $is_available
        ];
        
        // Move to next 30-minute slot
        $current_time += 1800; // 30 minutes in seconds
    }
    
    if (empty($slots)) {
        echo json_encode(['error' => 'No available time slots for this date']);
        exit;
    }
    
    echo json_encode($slots);
    
} catch (Exception $e) {
    error_log("Error in get_available_slots.php: " . $e->getMessage());
    echo json_encode(['error' => 'An error occurred while fetching time slots']);
}
?> 