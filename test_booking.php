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
    $tester->runAllTests();
} catch (Exception $e) {
    echo "<h2>Error Running Tests</h2>";
    echo "<pre>";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?> 