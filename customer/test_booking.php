<?php
// test_booking.php - Enhanced backend test for booking logic

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

function output($label, $value) {
    echo "<pre><b>$label:</b> ";
    print_r($value);
    echo "</pre>\n";
}

echo "<h2>Booking Backend Test (Detailed)</h2>";

try {
    // 0. Show session data
    output('Session Data', $_SESSION);

    // 1. Test DB connection
    $db = new Database();
    $conn = $db->getConnection();
    output('Database Connection', $conn ? 'Connected' : 'Not Connected');
    output('DB Host', DB_HOST);
    output('DB User', DB_USER);
    output('DB Name', DB_NAME);

    // 2. Show row counts for all relevant tables
    $tables = ['users', 'barbers', 'services', 'appointments', 'barber_availability', 'working_hours'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $result ? $result->fetch_assoc()['count'] : 'ERROR';
        output("Row count in $table", $count);
    }

    // 3. Get first customer
    $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($user_id, $username, $role);
    if (!$stmt->fetch()) {
        throw new Exception('No customer found in database.');
    }
    $stmt->close();
    output('Test Customer', [ 'id' => $user_id, 'username' => $username, 'role' => $role ]);

    // 4. Get first service
    $stmt = $conn->prepare("SELECT id, name FROM services LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($service_id, $service_name);
    if (!$stmt->fetch()) {
        throw new Exception('No service found in database.');
    }
    $stmt->close();
    output('Test Service', [ 'id' => $service_id, 'name' => $service_name ]);

    // 5. Get first barber
    $stmt = $conn->prepare("SELECT b.id, u.username FROM barbers b JOIN users u ON b.user_id = u.id LIMIT 1");
    $stmt->execute();
    $stmt->bind_result($barber_id, $barber_name);
    if (!$stmt->fetch()) {
        throw new Exception('No barber found in database.');
    }
    $stmt->close();
    output('Test Barber', [ 'id' => $barber_id, 'name' => $barber_name ]);

    // 6. Use today's date and 10:00 as time
    $date = date('Y-m-d');
    $time = '10:00';
    output('Test Date/Time', [ 'date' => $date, 'time' => $time ]);

    // 7. Check service exists
    $service = $db->getServiceById($service_id);
    output('Service Exists', $service ? $service : 'No');
    if (!$service) throw new Exception('Service does not exist.');

    // 8. Check barber availability
    $is_available = $db->isBarberAvailable($barber_id, $date, $time);
    output('Barber Available', $is_available ? 'Yes' : 'No');
    
    // 9. Show barber availability details
    $availability = $db->getBarberAvailability($barber_id, $date);
    output('Barber Availability Details', $availability);
    if (!$is_available) throw new Exception('Barber is not available at this time.');

    // 10. Attempt to create appointment
    $result = $db->createAppointment($user_id, $barber_id, $service_id, $date, $time);
    output('Appointment Creation Result', $result ? 'Success' : 'Failed');
    if (!$result) throw new Exception('Failed to create appointment. Check PHP error log for details.');

    echo '<h3 style="color:green;">Test Passed: Appointment booked successfully!</h3>';

} catch (Exception $e) {
    echo '<h3 style="color:red;">Test Failed: ' . htmlspecialchars($e->getMessage()) . '</h3>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
