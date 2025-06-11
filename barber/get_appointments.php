<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    die(json_encode(['error' => 'Unauthorized']));
}

$db = new Database();
$barber = $db->getSingleBarber();

$view = $_GET['view'] ?? 'today';
$date = $_GET['date'] ?? date('Y-m-d');

try {
    $appointments = [];
    
    switch ($view) {
        case 'today':
            $appointments = $db->getAppointmentsByBarber($barber['id']);
            $appointments = array_filter($appointments, function($apt) use ($date) {
                return $apt['appointment_date'] === $date;
            });
            break;
            
        case 'upcoming':
            $appointments = $db->getAppointmentsByBarber($barber['id']);
            $appointments = array_filter($appointments, function($apt) use ($date) {
                return $apt['appointment_date'] > $date && 
                       strtotime($apt['appointment_date']) <= strtotime('+7 days');
            });
            break;
            
        case 'all':
            $appointments = $db->getAppointmentsByBarber($barber['id']);
            break;
    }
    
    // Sort appointments by date and time
    usort($appointments, function($a, $b) {
        return strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) - 
               strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
    });
    
    // Format appointments for display
    $formatted_appointments = array_map(function($apt) {
        return [
            'id' => $apt['id'],
            'date' => date('M d, Y', strtotime($apt['appointment_date'])),
            'time' => date('g:i A', strtotime($apt['appointment_time'])),
            'customer_name' => htmlspecialchars($apt['customer_name']),
            'customer_phone' => htmlspecialchars($apt['customer_phone'] ?? 'No phone'),
            'service_name' => htmlspecialchars($apt['service_name']),
            'duration' => $apt['duration'],
            'status' => $apt['status'],
            'status_color' => getStatusColor($apt['status'])
        ];
    }, $appointments);
    
    echo json_encode([
        'success' => true,
        'appointments' => $formatted_appointments
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'primary';
        case 'completed':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
} 