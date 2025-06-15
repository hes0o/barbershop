<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Validate required fields
if (!isset($_POST['date']) || !isset($_POST['time']) || !isset($_POST['service_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$date = $_POST['date'];
$time = $_POST['time'];
$service_id = $_POST['service_id'];
$user_id = $_SESSION['user_id'];

try {
    $db = new Database();
    
    // Get the active barber
    $barber = $db->getSingleBarber();
    if (!$barber) {
        echo json_encode(['success' => false, 'message' => 'No barber available']);
        exit;
    }

    // Check if the time slot is still available
    if (!$db->isBarberAvailable($barber['id'], $date, $time)) {
        echo json_encode(['success' => false, 'message' => 'This time slot is no longer available']);
        exit;
    }

    // Create the appointment
    $appointment_id = $db->createAppointment($user_id, $barber['id'], $service_id, $date, $time);
    
    if ($appointment_id) {
        // Fetch info for emails
        require_once __DIR__ . '/../includes/email_helper.php';
        $customer = $db->getUserById($user_id);
        $service = $db->getServiceById($service_id);
        // Get admin (first admin found)
        $admin = $db->getConnection()->query("SELECT id, first_name, last_name, email FROM users WHERE role = 'admin' LIMIT 1")->fetch_assoc();

        // Prepare template loading function
        function load_email_template($template, $vars) {
            $base = file_get_contents(__DIR__ . '/../email_templates/base.html');
            $content = file_get_contents(__DIR__ . '/../email_templates/' . $template);
            foreach ($vars as $k => $v) {
                $content = str_replace('{{' . $k . '}}', $v, $content);
            }
            $html = str_replace(['{{subject}}', '{{content}}'], [$vars['subject'], $content], $base);
            return $html;
        }

        $appointment_date = date('F j, Y', strtotime($date));
        $appointment_time = date('g:i A', strtotime($time));
        $service_name = $service['name'];
        $barber_name = $barber['first_name'] . ' ' . $barber['last_name'];
        $customer_name = $customer['first_name'] . ' ' . $customer['last_name'];
        $admin_name = $admin ? ($admin['first_name'] . ' ' . $admin['last_name']) : 'Admin';

        $email = new EmailHelper();

        // 1. Email to customer
        $vars = [
            'subject' => 'Appointment Confirmation',
            'greeting' => 'Hello ' . $customer_name . ',',
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'service_name' => $service_name,
            'barber_name' => $barber_name,
            'appointment_link' => '#' // You can set a real link here
        ];
        $body = load_email_template('appointment_confirmation.html', $vars);
        $email->send($customer['email'], $vars['subject'], $body);

        // 2. Email to barber
        $vars_barber = [
            'subject' => 'New Appointment Booked',
            'greeting' => 'Hello ' . $barber_name . ',',
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'service_name' => $service_name,
            'barber_name' => $barber_name,
            'customer_name' => $customer_name,
            'appointment_link' => '#'
        ];
        $barber_body = load_email_template('appointment_confirmation.html', $vars_barber);
        $email->send($barber['email'], $vars_barber['subject'], $barber_body);

        // 3. Email to admin
        if ($admin && !empty($admin['email'])) {
            $vars_admin = [
                'subject' => 'New Appointment Notification',
                'greeting' => 'Hello ' . $admin_name . ',',
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'service_name' => $service_name,
                'barber_name' => $barber_name,
                'customer_name' => $customer_name,
                'appointment_link' => '#'
            ];
            $admin_body = load_email_template('appointment_confirmation.html', $vars_admin);
            $email->send($admin['email'], $vars_admin['subject'], $admin_body);
        }

        echo json_encode(['success' => true, 'message' => 'Appointment booked successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book appointment']);
    }
} catch (Exception $e) {
    error_log("Error in book_appointment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while booking the appointment']);
}
