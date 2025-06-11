<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit;
}

// Get customer ID from request
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

if (!$customer_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid customer ID']);
    exit;
}

try {
    $db = new Database();
    
    // Get customer details
    $customer = $db->getCustomerById($customer_id);
    
    if (!$customer) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
        exit;
    }
    
    // Get customer's appointment history
    $history = $db->getCustomerAppointmentHistory($customer_id);
    
    // Format the history data
    $formatted_history = array_map(function($apt) {
        return [
            'id' => $apt['id'],
            'date' => date('M d, Y', strtotime($apt['appointment_date'])),
            'time' => date('g:i A', strtotime($apt['appointment_time'])),
            'service_name' => $apt['service_name'],
            'duration' => $apt['duration'],
            'status' => $apt['status'],
            'status_color' => getStatusColor($apt['status']),
            'notes' => $apt['notes']
        ];
    }, $history);
    
    // Return the data
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'customer' => [
            'name' => $customer['name'],
            'phone' => $customer['phone'],
            'email' => $customer['email']
        ],
        'history' => $formatted_history
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Helper function to get status color
function getStatusColor($status) {
    switch ($status) {
        case 'completed':
            return 'success';
        case 'confirmed':
            return 'primary';
        case 'pending':
            return 'warning';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
} 