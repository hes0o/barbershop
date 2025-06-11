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

echo "<h1>Booking System Test</h1>";

// Test 1: Session Check
echo "<h2>Test 1: Session Check</h2>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Test 2: Database Connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "Database connection successful<br>";
    
    // Test database tables
    $tables = ['users', 'barbers', 'services', 'appointments', 'barber_schedules', 'working_hours'];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Table '$table' exists with {$row['count']} rows<br>";
        } else {
            echo "Error checking table '$table': " . $conn->error . "<br>";
        }
    }
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Get First Customer
echo "<h2>Test 3: Get First Customer</h2>";
try {
    $result = $conn->query("SELECT * FROM users WHERE role = 'customer' LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        echo "Found customer: <pre>" . print_r($customer, true) . "</pre>";
        $_SESSION['user_id'] = $customer['id'];
        $_SESSION['role'] = 'customer';
    } else {
        echo "No customer found in database<br>";
    }
} catch (Exception $e) {
    echo "Error getting customer: " . $e->getMessage() . "<br>";
}

// Test 4: Get First Service
echo "<h2>Test 4: Get First Service</h2>";
try {
    $result = $conn->query("SELECT * FROM services LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $service = $result->fetch_assoc();
        echo "Found service: <pre>" . print_r($service, true) . "</pre>";
    } else {
        echo "No service found in database<br>";
    }
} catch (Exception $e) {
    echo "Error getting service: " . $e->getMessage() . "<br>";
}

// Test 5: Get First Barber
echo "<h2>Test 5: Get First Barber</h2>";
try {
    $result = $conn->query("
        SELECT b.*, u.username 
        FROM barbers b 
        JOIN users u ON b.user_id = u.id 
        LIMIT 1
    ");
    if ($result && $result->num_rows > 0) {
        $barber = $result->fetch_assoc();
        echo "Found barber: <pre>" . print_r($barber, true) . "</pre>";
    } else {
        echo "No barber found in database<br>";
    }
} catch (Exception $e) {
    echo "Error getting barber: " . $e->getMessage() . "<br>";
}

// Test 6: Check Barber Schedule
echo "<h2>Test 6: Check Barber Schedule</h2>";
try {
    if (isset($barber['id'])) {
        $result = $conn->query("
            SELECT * FROM barber_schedules 
            WHERE barber_id = {$barber['id']}
        ");
        if ($result) {
            echo "Barber schedule: <pre>";
            while ($row = $result->fetch_assoc()) {
                print_r($row);
            }
            echo "</pre>";
        } else {
            echo "No schedule found for barber<br>";
        }
    }
} catch (Exception $e) {
    echo "Error checking barber schedule: " . $e->getMessage() . "<br>";
}

// Test 7: Check Working Hours
echo "<h2>Test 7: Check Working Hours</h2>";
try {
    $result = $conn->query("SELECT * FROM working_hours");
    if ($result) {
        echo "Working hours: <pre>";
        while ($row = $result->fetch_assoc()) {
            print_r($row);
        }
        echo "</pre>";
    } else {
        echo "No working hours found<br>";
    }
} catch (Exception $e) {
    echo "Error checking working hours: " . $e->getMessage() . "<br>";
}

// Test 8: Test Barber Availability
echo "<h2>Test 8: Test Barber Availability</h2>";
try {
    if (isset($barber['id'])) {
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));
        echo "Testing availability for tomorrow ($tomorrow)<br>";
        
        // Test each hour
        for ($hour = 9; $hour <= 17; $hour++) {
            $time = sprintf('%02d:00', $hour);
            $is_available = $db->isBarberAvailable($barber['id'], $tomorrow, $time);
            echo "Time $time: " . ($is_available ? "Available" : "Not Available") . "<br>";
        }
    }
} catch (Exception $e) {
    echo "Error testing barber availability: " . $e->getMessage() . "<br>";
}

// Test 9: Test Create Appointment
echo "<h2>Test 9: Test Create Appointment</h2>";
try {
    if (isset($customer['id']) && isset($barber['id']) && isset($service['id'])) {
        $tomorrow = date('Y-m-d', strtotime('tomorrow'));
        $time = '10:00';
        
        echo "Attempting to create test appointment:<br>";
        echo "Customer ID: {$customer['id']}<br>";
        echo "Barber ID: {$barber['id']}<br>";
        echo "Service ID: {$service['id']}<br>";
        echo "Date: $tomorrow<br>";
        echo "Time: $time<br>";
        
        $result = $db->createAppointment(
            $customer['id'],
            $barber['id'],
            $service['id'],
            $tomorrow,
            $time
        );
        
        if ($result) {
            echo "Test appointment created successfully!<br>";
        } else {
            echo "Failed to create test appointment. Database error: " . $conn->error . "<br>";
        }
    } else {
        echo "Missing required data for test appointment<br>";
    }
} catch (Exception $e) {
    echo "Error creating test appointment: " . $e->getMessage() . "<br>";
}

// Test 10: Check Available Dates
echo "<h2>Test 10: Check Available Dates</h2>";
try {
    if (isset($barber['id'])) {
        $available_dates = $db->getAvailableDates($barber['id']);
        echo "Available dates for next 30 days:<br>";
        echo "<pre>" . print_r($available_dates, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "Error checking available dates: " . $e->getMessage() . "<br>";
}

// Test 11: Verify Barber Schedules Data
echo "<h3>Test 11: Verify Barber Schedules Data</h3>";
$stmt = $db->getConnection()->prepare("SELECT * FROM barber_schedules WHERE barber_id = ?");
$stmt->bind_param("i", $barber['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<div style='color: green;'>✓ Found " . $result->num_rows . " schedule entries</div>";
    echo "<table border='1' style='margin: 10px 0;'>";
    echo "<tr><th>Day</th><th>Start Time</th><th>End Time</th><th>Status</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['day_of_week']) . "</td>";
        echo "<td>" . htmlspecialchars($row['start_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['end_time']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div style='color: red;'>✗ No schedule entries found</div>";
}
$stmt->close();

// Add a form to test the booking process
echo "<h2>Test Booking Form</h2>";
echo "<form method='post' action='book_appointment.php'>";
echo "<input type='hidden' name='test_mode' value='1'>";
echo "<input type='hidden' name='service_id' value='" . ($service['id'] ?? '') . "'>";
echo "<input type='hidden' name='date' value='" . date('Y-m-d', strtotime('tomorrow')) . "'>";
echo "<input type='hidden' name='time' value='10:00'>";
echo "<button type='submit'>Test Direct Booking</button>";
echo "</form>";

// Add a link to view the error log
echo "<h2>Error Log</h2>";
echo "<a href='view_error_log.php' target='_blank'>View PHP Error Log</a>";
