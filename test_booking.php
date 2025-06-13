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

        // Get a test service
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
            'service' => $services[0],
            'date' => $available_dates[0]
        ];
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

    public function testBookingValidation() {
        echo "<h3>Testing Booking Validation</h3>";
        $test_time = "10:00"; // Example time
        
        try {
            $isValid = $this->scheduleSync->validateBookingTime(
                $this->testData['barber']['id'],
                $this->testData['date'],
                $test_time,
                $this->testData['service']['id']
            );
            
            echo "Booking validation for {$this->testData['date']} at $test_time: " . 
                 ($isValid ? "Valid" : "Invalid") . "<br>";
        } catch (Exception $e) {
            echo "Error validating booking: " . $e->getMessage() . "<br>";
        }
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
        } catch (Exception $e) {
            echo "Database connection error: " . $e->getMessage() . "<br>";
        }
    }

    public function runAllTests() {
        echo "<h2>Running Booking System Tests</h2>";
        echo "<pre>";
        
        $this->testDatabaseConnection();
        echo "\n";
        
        $this->testBarberSchedule();
        echo "\n";
        
        $this->testAvailableTimes();
        echo "\n";
        
        $this->testBookingValidation();
        
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