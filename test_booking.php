<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/schedule_sync.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

class BookingTester {
    private $db;
    private $scheduleSync;
    private $testData;

    public function __construct() {
        $this->db = new Database();
        $this->scheduleSync = new ScheduleSync();
        $this->initializeTestData();
    }

    private function initializeTestData() {
        // Get a test barber
        $barber = $this->db->getSingleBarber();
        if (!$barber) {
            throw new Exception("No barber found for testing");
        }

        // Get all services
        $services = $this->db->getAllServices();
        if (empty($services)) {
            throw new Exception("No services found for testing");
        }

        // Get available dates
        $available_dates = $this->db->getAvailableDates($barber['id']);
        if (empty($available_dates)) {
            throw new Exception("No available dates found for testing");
        }

        $this->testData = [
            'barber' => $barber,
            'services' => $services,
            'date' => $available_dates[0]
        ];
    }

    public function testDatabaseConnection() {
        echo "<h3>Testing Database Connection</h3>";
        try {
            $conn = $this->db->getConnection();
            echo "Database connection successful<br>";
            
            // Test appointments table
            $result = $conn->query("SELECT COUNT(*) as count FROM appointments");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "Total appointments in database: " . $row['count'] . "<br>";
            }
            
            // Test barber_schedule table
            $result = $conn->query("SELECT COUNT(*) as count FROM barber_schedule");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "Total schedule entries: " . $row['count'] . "<br>";
            }

            // Test services table
            $result = $conn->query("SELECT COUNT(*) as count FROM services");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "Total services: " . $row['count'] . "<br>";
            }
        } catch (Exception $e) {
            echo "Database connection error: " . $e->getMessage() . "<br>";
        }
    }

    public function testBarberSchedule() {
        echo "<h3>Testing Barber Schedule</h3>";
        $day_of_week = strtolower(date('l', strtotime($this->testData['date'])));
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT start_time, end_time, status
            FROM barber_schedule
            WHERE barber_id = ? AND day_of_week = ?
        ");
        
        $stmt->bind_param("is", $this->testData['barber']['id'], $day_of_week);
        $stmt->execute();
        $stmt->bind_result($start_time, $end_time, $status);
        
        if ($stmt->fetch()) {
            echo "Barber schedule found:<br>";
            echo "Start time: $start_time<br>";
            echo "End time: $end_time<br>";
            echo "Status: $status<br>";
            
            // Calculate total working hours
            $start = strtotime($start_time);
            $end = strtotime($end_time);
            $hours = ($end - $start) / 3600;
            echo "Total working hours: " . number_format($hours, 1) . " hours<br>";
        } else {
            echo "No schedule found for this day<br>";
        }
        $stmt->close();
    }

    public function testAvailableTimes() {
        echo "<h3>Testing Available Times</h3>";
        try {
            $times = $this->scheduleSync->getAvailableTimeSlots(
                $this->testData['barber']['id'],
                $this->testData['date']
            );
            
            echo "Available times for {$this->testData['date']}:<br>";
            if (!empty($times)) {
                echo "Total available slots: " . count($times) . "<br>";
                foreach ($times as $time) {
                    echo "- $time<br>";
                }
            } else {
                echo "No available times found<br>";
            }
        } catch (Exception $e) {
            echo "Error getting available times: " . $e->getMessage() . "<br>";
        }
    }

    public function testServiceDurations() {
        echo "<h3>Testing Service Durations</h3>";
        echo "Available services and their durations:<br>";
        foreach ($this->testData['services'] as $service) {
            echo "- {$service['name']}: {$service['duration']} minutes<br>";
        }
    }

    public function testBookingValidation() {
        echo "<h3>Testing Booking Validation</h3>";
        
        // Test different times
        $test_times = ["10:00", "10:30", "11:00", "11:30"];
        $service = $this->testData['services'][0];
        
        echo "Testing bookings for service: {$service['name']} ({$service['duration']} minutes)<br>";
        foreach ($test_times as $time) {
            try {
                $isValid = $this->scheduleSync->validateBookingTime(
                    $this->testData['barber']['id'],
                    $this->testData['date'],
                    $time,
                    $service['id']
                );
                
                echo "Time slot $time: " . ($isValid ? "Available" : "Not Available") . "<br>";
            } catch (Exception $e) {
                echo "Error validating $time: " . $e->getMessage() . "<br>";
            }
        }
    }

    public function testOverlappingBookings() {
        echo "<h3>Testing Overlapping Bookings</h3>";
        
        // Get the first available time
        $times = $this->scheduleSync->getAvailableTimeSlots(
            $this->testData['barber']['id'],
            $this->testData['date']
        );
        
        if (!empty($times)) {
            $first_time = $times[0];
            $service = $this->testData['services'][0];
            
            echo "Testing overlapping bookings for time: $first_time<br>";
            echo "Service duration: {$service['duration']} minutes<br>";
            
            // Create a test booking
            $conn = $this->db->getConnection();
            $conn->begin_transaction();
            
            try {
                // Insert test booking
                $stmt = $conn->prepare("
                    INSERT INTO appointments 
                    (user_id, barber_id, service_id, appointment_date, appointment_time, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");
                
                $user_id = 1; // Test user ID
                $status = 'pending';
                
                $stmt->bind_param("iiiss", 
                    $user_id,
                    $this->testData['barber']['id'],
                    $service['id'],
                    $this->testData['date'],
                    $first_time
                );
                
                $stmt->execute();
                $booking_id = $conn->insert_id;
                
                echo "Created test booking at $first_time<br>";
                
                // Test booking at the same time
                $isValid = $this->scheduleSync->validateBookingTime(
                    $this->testData['barber']['id'],
                    $this->testData['date'],
                    $first_time,
                    $service['id']
                );
                
                echo "First booking at $first_time: " . ($isValid ? "Available" : "Not Available") . "<br>";
                
                // Test booking 15 minutes after
                $next_time = date('H:i', strtotime($first_time . ' +15 minutes'));
                $isValid = $this->scheduleSync->validateBookingTime(
                    $this->testData['barber']['id'],
                    $this->testData['date'],
                    $next_time,
                    $service['id']
                );
                
                echo "Second booking at $next_time: " . ($isValid ? "Available" : "Not Available") . "<br>";
                
                // Test booking 30 minutes after
                $next_time = date('H:i', strtotime($first_time . ' +30 minutes'));
                $isValid = $this->scheduleSync->validateBookingTime(
                    $this->testData['barber']['id'],
                    $this->testData['date'],
                    $next_time,
                    $service['id']
                );
                
                echo "Third booking at $next_time: " . ($isValid ? "Available" : "Not Available") . "<br>";
                
                // Clean up - delete test booking
                $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                echo "Error during test: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "No available times to test overlapping bookings<br>";
        }
    }

    public function testPastTimes() {
        echo "<h3>Testing Past Times</h3>";
        
        // Test booking for a past time today
        $past_time = date('H:i', strtotime('-2 hours'));
        $service = $this->testData['services'][0];
        
        try {
            $isValid = $this->scheduleSync->validateBookingTime(
                $this->testData['barber']['id'],
                date('Y-m-d'),
                $past_time,
                $service['id']
            );
            
            echo "Past time booking ($past_time): " . ($isValid ? "Available" : "Not Available") . "<br>";
        } catch (Exception $e) {
            echo "Error testing past time: " . $e->getMessage() . "<br>";
        }
    }

    public function renderBookingSimulator() {
        echo "<h2>Booking Simulator</h2>";
        echo "<div class='booking-simulator'>";
        
        // Get available dates
        $available_dates = $this->db->getAvailableDates($this->testData['barber']['id']);
        
        echo "<form method='post' action='' class='booking-form'>";
        echo "<input type='hidden' name='action' value='simulate_booking'>";
        
        // Date Selection
        echo "<div class='form-group'>";
        echo "<label>Select Date:</label>";
        echo "<select name='booking_date' required>";
        foreach ($available_dates as $date) {
            echo "<option value='$date'>" . date('D, M j, Y', strtotime($date)) . "</option>";
        }
        echo "</select>";
        echo "</div>";
        
        // Service Selection
        echo "<div class='form-group'>";
        echo "<label>Select Service:</label>";
        echo "<select name='service_id' required>";
        foreach ($this->testData['services'] as $service) {
            echo "<option value='{$service['id']}'>{$service['name']} ({$service['duration']} min)</option>";
        }
        echo "</select>";
        echo "</div>";
        
        // Time Selection
        echo "<div class='form-group'>";
        echo "<label>Select Time:</label>";
        echo "<select name='booking_time' required>";
        echo "<option value=''>Select a date first</option>";
        echo "</select>";
        echo "</div>";
        
        echo "<button type='submit' class='btn btn-primary'>Simulate Booking</button>";
        echo "</form>";
        
        // Display current bookings
        $this->displayCurrentBookings();
        
        echo "</div>";
        
        // Add JavaScript for dynamic time slot loading
        echo "<script>
        document.querySelector('select[name=booking_date]').addEventListener('change', function() {
            const date = this.value;
            const timeSelect = document.querySelector('select[name=booking_time]');
            timeSelect.innerHTML = '<option value=\"\">Loading times...</option>';
            
            fetch('get_available_times.php?date=' + date)
                .then(response => response.json())
                .then(data => {
                    timeSelect.innerHTML = '<option value=\"\">Select a time</option>';
                    if (data.success && data.times.length > 0) {
                        data.times.forEach(time => {
                            const option = document.createElement('option');
                            option.value = time;
                            option.textContent = time;
                            timeSelect.appendChild(option);
                        });
                    } else {
                        timeSelect.innerHTML = '<option value=\"\">No available times</option>';
                    }
                })
                .catch(error => {
                    timeSelect.innerHTML = '<option value=\"\">Error loading times</option>';
                });
        });
        </script>";
        
        // Add CSS for the simulator
        echo "<style>
        .booking-simulator {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .booking-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-weight: 500;
            color: #333;
        }
        .form-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .current-bookings {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .booking-item {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        </style>";
    }

    private function displayCurrentBookings() {
        echo "<div class='current-bookings'>";
        echo "<h3>Current Bookings</h3>";
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT a.*, s.name as service_name, s.duration
            FROM appointments a
            JOIN services s ON a.service_id = s.id
            WHERE a.barber_id = ?
            AND a.appointment_date >= CURDATE()
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
        ");
        
        $stmt->bind_param("i", $this->testData['barber']['id']);
        $stmt->execute();
        $stmt->bind_result(
            $id, $user_id, $barber_id, $service_id, $date, 
            $time, $status, $notes, $created_at, 
            $service_name, $duration
        );
        
        $hasBookings = false;
        while ($stmt->fetch()) {
            $hasBookings = true;
            echo "<div class='booking-item'>";
            echo "<strong>Date:</strong> " . date('D, M j, Y', strtotime($date)) . "<br>";
            echo "<strong>Time:</strong> $time<br>";
            echo "<strong>Service:</strong> $service_name ($duration min)<br>";
            echo "<strong>Status:</strong> $status<br>";
            echo "</div>";
        }
        
        if (!$hasBookings) {
            echo "<p>No current bookings found.</p>";
        }
        
        $stmt->close();
        echo "</div>";
    }

    public function handleBookingSimulation() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'simulate_booking') {
            $date = $_POST['booking_date'];
            $time = $_POST['booking_time'];
            $service_id = $_POST['service_id'];
            
            try {
                // Validate the booking
                $isValid = $this->scheduleSync->validateBookingTime(
                    $this->testData['barber']['id'],
                    $date,
                    $time,
                    $service_id
                );
                
                if ($isValid) {
                    // Create the booking
                    $conn = $this->db->getConnection();
                    $stmt = $conn->prepare("
                        INSERT INTO appointments 
                        (user_id, barber_id, service_id, appointment_date, appointment_time, status) 
                        VALUES (?, ?, ?, ?, ?, 'pending')
                    ");
                    
                    $user_id = 1; // Test user ID
                    $stmt->bind_param("iiiss", 
                        $user_id,
                        $this->testData['barber']['id'],
                        $service_id,
                        $date,
                        $time
                    );
                    
                    if ($stmt->execute()) {
                        echo "<div class='alert alert-success'>Booking created successfully!</div>";
                    } else {
                        echo "<div class='alert alert-danger'>Error creating booking: " . $stmt->error . "</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger'>This time slot is not available.</div>";
                }
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }

    public function runAllTests() {
        echo "<h2>Running Booking System Tests</h2>";
        echo "<pre>";
        
        $this->testDatabaseConnection();
        echo "\n";
        
        $this->testBarberSchedule();
        echo "\n";
        
        $this->testServiceDurations();
        echo "\n";
        
        $this->testAvailableTimes();
        echo "\n";
        
        $this->testBookingValidation();
        echo "\n";
        
        $this->testOverlappingBookings();
        echo "\n";
        
        $this->testPastTimes();
        
        echo "</pre>";
    }
}

// Run the tests
try {
    $tester = new BookingTester();
    
    // Handle booking simulation
    $tester->handleBookingSimulation();
    
    // Display the booking simulator
    $tester->renderBookingSimulator();
    
    // Run the automated tests
    $tester->runAllTests();
} catch (Exception $e) {
    echo "<h2>Error Running Tests</h2>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?> 