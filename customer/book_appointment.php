<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Disable error display but enable logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');

try {
    // Basic validation
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
        throw new Exception('Please log in as a customer to book appointments');
    }

    // Get and validate input
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !isset($data['service_id']) || !isset($data['date']) || !isset($data['time'])) {
        throw new Exception('Invalid booking data');
    }

    // Create appointment
    $db = new Database();
    $result = $db->createAppointment(
        $_SESSION['user_id'],
        $db->getSingleBarber()['id'],
        $data['service_id'],
        $data['date'],
        $data['time']
    );

    if (!$result) {
        throw new Exception('Unable to book appointment. Please try again.');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 