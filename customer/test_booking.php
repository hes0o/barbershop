<?php
// test_booking.php - Standalone backend test for booking logic

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

function output($label, $value) {
    echo "<pre><b>$label:</b> ";
    print_r($value);
    echo "</pre>\n";
}

echo "<h2>Booking Backend Test</h2>";

try {
    $db = new Database();

    // 1. Get first customer
    $stmt = $db->getConnection()->prepare("SELECT id, username FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($user_id, $username);
    if (!$stmt->fetch()) {
        throw new Exception('No customer found in database.');
    }
    $stmt->close();
    output('Test Customer', [ 'id' => $user_id, 'username' => $username ]);

    // 2. Get first service
    $stmt = $db->getConnection()->prepare("SELECT id, name FROM services LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($service_id, $service_name);
    if (!$stmt->fetch()) {
        throw new Exception('No service found in database.');
    }
    $stmt->close();
    output('Test Service', [ 'id' => $service_id, 'name' => $service_name ]);

    // 3. Get first barber
    $stmt = $db->getConnection()->prepare("SELECT b.id, u.username FROM barbers b JOIN users u ON b.user_id = u.id LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($barber_id, $barber_name);
    if (!$stmt->fetch()) {
        throw new Exception('No barber found in database.');
    }
    $stmt->close();
    output('Test Barber', [ 'id' => $barber_id, 'name' => $barber_name ]);

    // 4. Use today's date and 10:00 as time
    $date = date('Y-m-d');
    $time = '10:00';
    output('Test Date/Time', [ 'date' => $date, 'time' => $time ]);

    // 5. Check service exists
    $service = $db->getServiceById($service_id);
    output('Service Exists', $service ? 'Yes' : 'No');
    if (!$service) throw new Exception('Service does not exist.');

    // 6. Check barber availability
    $is_available = $db->isBarberAvailable($barber_id, $date, $time);
    output('Barber Available', $is_available ? 'Yes' : 'No');
    if (!$is_available) throw new Exception('Barber is not available at this time.');

    // 7. Attempt to create appointment
    $result = $db->createAppointment($user_id, $barber_id, $service_id, $date, $time);
    output('Appointment Creation Result', $result ? 'Success' : 'Failed');
    if (!$result) throw new Exception('Failed to create appointment. Check PHP error log for details.');

    echo '<h3 style="color:green;">Test Passed: Appointment booked successfully!</h3>';

} catch (Exception $e) {
    echo '<h3 style="color:red;">Test Failed: ' . htmlspecialchars($e->getMessage()) . '</h3>';
} 