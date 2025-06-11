<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/error_handler.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Get the raw POST data
    $raw_data = file_get_contents('php://input');
    $data = json_decode($raw_data, true);

    $debug_info = [
        'session' => $_SESSION,
        'raw_post_data' => $raw_data,
        'parsed_data' => $data,
        'server' => [
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'not set'
        ]
    ];

    if ($data) {
        $db = new Database();
        
        // Check customer
        $stmt = $db->getConnection()->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $debug_info['customer'] = $customer;

        // Check barber
        $barber = $db->getSingleBarber();
        $debug_info['barber'] = $barber;

        // Check service
        $service = $db->getServiceById($data['service_id']);
        $debug_info['service'] = $service;

        // Check weekly schedule
        if ($barber) {
            $weekly_schedule = $db->getBarberWeeklySchedule($barber['id']);
            $debug_info['weekly_schedule'] = $weekly_schedule;
        }

        // Check existing appointments
        if ($barber) {
            $existing_appointments = $db->getBarberAppointments($barber['id'], 'all', $data['date']);
            $debug_info['existing_appointments'] = $existing_appointments;
        }

        // Check customer appointments
        $customer_appointments = $db->getAppointmentsByCustomer($_SESSION['user_id']);
        $debug_info['customer_appointments'] = $customer_appointments;
    }

    echo json_encode([
        'success' => true,
        'debug_info' => $debug_info
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} 