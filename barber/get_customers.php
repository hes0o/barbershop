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

try {
    $db = new Database();
    
    // Get barber's customers with their statistics
    $customers = $db->getBarberCustomers($_SESSION['user_id']);
    
    // Format the customer data
    $formatted_customers = array_map(function($customer) {
        return [
            'id' => $customer['id'],
            'name' => $customer['name'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
            'join_date' => date('M d, Y', strtotime($customer['created_at'])),
            'last_visit' => $customer['last_visit'] ? date('M d, Y', strtotime($customer['last_visit'])) : null,
            'total_visits' => $customer['total_visits'],
            'favorite_service' => $customer['favorite_service']
        ];
    }, $customers);
    
    // Return the data
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'customers' => $formatted_customers
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 