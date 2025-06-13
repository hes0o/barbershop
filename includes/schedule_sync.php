<?php
require_once __DIR__ . '/db.php';

class ScheduleSync {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function validateBookingTime($barber_id, $date, $time, $service_id) {
        try {
            // Get service duration
            $stmt = $this->db->getConnection()->prepare("
                SELECT duration 
                FROM services 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $service = $result->fetch_assoc();
            
            if (!$service) {
                throw new Exception("Service not found");
            }
            
            $duration = $service['duration'];
            
            // Get barber's schedule for the day
            $day_of_week = strtolower(date('l', strtotime($date)));
            $stmt = $this->db->getConnection()->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $result = $stmt->get_result();
            $schedule = $result->fetch_assoc();
            
            if (!$schedule || $schedule['status'] === 'unavailable') {
                throw new Exception("Barber is not available on this day");
            }
            
            // Check if booking time is within barber's schedule
            $booking_time = strtotime($time);
            $end_time = strtotime($schedule['end_time']);
            $booking_end = strtotime("+$duration minutes", $booking_time);
            
            if ($booking_time < strtotime($schedule['start_time']) || $booking_end > $end_time) {
                throw new Exception("Booking time is outside barber's working hours");
            }
            
            // Check for overlapping appointments
            $stmt = $this->db->getConnection()->prepare("
                SELECT a.appointment_time, s.duration
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                WHERE a.barber_id = ? 
                AND a.appointment_date = ?
                AND a.status != 'cancelled'
                AND (
                    (a.appointment_time <= ? AND DATE_ADD(a.appointment_time, INTERVAL s.duration MINUTE) > ?)
                    OR (a.appointment_time < DATE_ADD(?, INTERVAL s.duration MINUTE) AND a.appointment_time >= ?)
                )
            ");
            
            $stmt->bind_param("isssss", $barber_id, $date, $time, $time, $time, $time);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                throw new Exception("This time slot is already booked");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error validating booking time: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getAvailableTimeSlots($barber_id, $date) {
        try {
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            // Get barber's schedule
            $stmt = $this->db->getConnection()->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $result = $stmt->get_result();
            $schedule = $result->fetch_assoc();
            
            if (!$schedule || $schedule['status'] === 'unavailable') {
                return [];
            }
            
            // Get minimum service duration
            $stmt = $this->db->getConnection()->prepare("SELECT MIN(duration) as min_duration FROM services");
            $stmt->execute();
            $result = $stmt->get_result();
            $min_duration = $result->fetch_assoc()['min_duration'] ?? 30;
            
            // Generate time slots
            $start_time = strtotime($schedule['start_time']);
            $end_time = strtotime($schedule['end_time']);
            $interval = 30 * 60; // 30 minutes in seconds
            $time_slots = [];
            
            for ($time = $start_time; $time < $end_time; $time += $interval) {
                $time_slot = date('H:i:s', $time);
                if ($this->db->isBarberAvailable($barber_id, $date, $time_slot)) {
                    $time_slots[] = $time_slot;
                }
            }
            
            return $time_slots;
        } catch (Exception $e) {
            error_log("Error getting available time slots: " . $e->getMessage());
            return [];
        }
    }
    
    public function updateBarberSchedule($barber_id, $schedule) {
        try {
            return $this->db->updateBarberWeeklySchedule($barber_id, $schedule);
        } catch (Exception $e) {
            error_log("Error updating barber schedule: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getBarberSchedule($barber_id) {
        try {
            return $this->db->getBarberWeeklySchedule($barber_id);
        } catch (Exception $e) {
            error_log("Error getting barber schedule: " . $e->getMessage());
            throw $e;
        }
    }
}
?> 