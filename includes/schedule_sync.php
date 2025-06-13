<?php
require_once __DIR__ . '/db.php';

class ScheduleSync {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function validateBookingTime($barber_id, $date, $time, $service_id) {
        try {
            // Get service duration (default to 60 minutes for all services)
            $duration = 60;
            
            // Convert booking time to timestamp
            $booking_time = strtotime($time);
            $booking_end = strtotime("+{$duration} minutes", $booking_time);
            
            // Check if the booking time is within barber's schedule
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            $stmt = $this->db->getConnection()->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $stmt->bind_result($start_time, $end_time, $status);
            
            if (!$stmt->fetch() || $status !== 'available') {
                return false;
            }
            
            $stmt->close();
            
            // Convert schedule times to timestamps
            $schedule_start = strtotime($start_time);
            $schedule_end = strtotime($end_time);
            
            // Check if booking is within schedule
            if ($booking_time < $schedule_start || $booking_end > $schedule_end) {
                return false;
            }
            
            // Check for overlapping appointments
            $stmt = $this->db->getConnection()->prepare("
                SELECT appointment_time
                FROM appointments a
                WHERE a.barber_id = ? 
                AND a.appointment_date = ?
                AND a.status != 'cancelled'
                AND (
                    (appointment_time <= ? AND DATE_ADD(appointment_time, INTERVAL 60 MINUTE) > ?)
                    OR (appointment_time < ? AND DATE_ADD(appointment_time, INTERVAL 60 MINUTE) >= ?)
                    OR (appointment_time >= ? AND DATE_ADD(appointment_time, INTERVAL 60 MINUTE) <= ?)
                    OR (appointment_time <= ? AND DATE_ADD(appointment_time, INTERVAL 60 MINUTE) >= ?)
                )
            ");
            
            $stmt->bind_param(
                "isssssssss",
                $barber_id,
                $date,
                $time,
                $time,
                date('H:i:s', $booking_end),
                date('H:i:s', $booking_end),
                $time,
                date('H:i:s', $booking_end),
                $time,
                date('H:i:s', $booking_end)
            );
            
            $stmt->execute();
            $stmt->store_result();
            
            $has_overlap = $stmt->num_rows > 0;
            $stmt->close();
            
            return !$has_overlap;
            
        } catch (Exception $e) {
            error_log("Error validating booking time: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAvailableTimeSlots($barber_id, $date) {
        try {
            // Get the day of week
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            // Get barber's schedule for this day
            $stmt = $this->db->getConnection()->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $stmt->bind_result($start_time, $end_time, $status);
            
            if (!$stmt->fetch() || $status !== 'available') {
                return [];
            }
            
            $stmt->close();
            
            // Convert times to timestamps for easier calculation
            $start = strtotime($start_time);
            $end = strtotime($end_time);
            
            // Generate time slots in one-hour intervals
            $time_slots = [];
            $current = $start;
            
            while ($current < $end) {
                $time_slot = date('H:i', $current);
                
                // Check if this time slot is available
                if ($this->validateBookingTime($barber_id, $date, $time_slot, 1)) {
                    $time_slots[] = $time_slot;
                }
                
                // Move to next hour
                $current = strtotime('+1 hour', $current);
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