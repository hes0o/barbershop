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
            
            if (!$stmt) {
                throw new Exception("Error preparing service query: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param("i", $service_id);
            $stmt->execute();
            $stmt->bind_result($duration);
            
            if (!$stmt->fetch()) {
                throw new Exception("Service not found");
            }
            
            $stmt->close();

            // Get barber's schedule for this day
            $day_of_week = strtolower(date('l', strtotime($date)));
            $stmt = $this->db->getConnection()->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            
            if (!$stmt) {
                throw new Exception("Error preparing schedule query: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $stmt->bind_result($start_time, $end_time, $status);
            
            if (!$stmt->fetch()) {
                throw new Exception("No schedule found for this day");
            }
            
            $stmt->close();

            // If barber is unavailable, return false
            if ($status === 'unavailable') {
                return false;
            }

            // Check if time is within working hours
            $booking_time = strtotime($time);
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $booking_end = $booking_time + ($duration * 60);

            if ($booking_time < $start_timestamp || $booking_end > $end_timestamp) {
                return false;
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
                    -- New booking starts during an existing appointment
                    (a.appointment_time <= ? AND DATE_ADD(a.appointment_time, INTERVAL s.duration MINUTE) > ?)
                    OR
                    -- New booking ends during an existing appointment
                    (a.appointment_time < DATE_ADD(?, INTERVAL ? MINUTE) AND a.appointment_time >= ?)
                    OR
                    -- New booking completely contains an existing appointment
                    (a.appointment_time >= ? AND a.appointment_time < DATE_ADD(?, INTERVAL ? MINUTE))
                )
            ");
            
            if (!$stmt) {
                throw new Exception("Error preparing overlap check query: " . $this->db->getConnection()->error);
            }
            
            // Convert times to timestamps for comparison
            $booking_start = strtotime($time);
            $booking_end = $booking_start + ($duration * 60);
            
            $stmt->bind_param("issssisssi", 
                $barber_id, 
                $date, 
                $time, 
                $time, 
                $time, 
                $duration, 
                $time,
                $time,
                $time,
                $duration
            );
            
            $stmt->execute();
            $stmt->bind_result($existing_time, $existing_duration);
            
            if ($stmt->fetch()) {
                $stmt->close();
                return false;
            }
            
            $stmt->close();
            return true;

        } catch (Exception $e) {
            error_log("Error in validateBookingTime: " . $e->getMessage());
            throw $e;
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
            
            if (!$stmt) {
                throw new Exception("Error preparing schedule query: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $stmt->bind_result($start_time, $end_time, $status);
            
            if (!$stmt->fetch()) {
                throw new Exception("No schedule found for this day");
            }
            
            $stmt->close();

            // If barber is unavailable, return empty array
            if ($status === 'unavailable') {
                return [];
            }

            // Get existing appointments for this date
            $stmt = $this->db->getConnection()->prepare("
                SELECT a.appointment_time, s.duration
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                WHERE a.barber_id = ? 
                AND a.appointment_date = ?
                AND a.status != 'cancelled'
            ");
            
            if (!$stmt) {
                throw new Exception("Error preparing appointments query: " . $this->db->getConnection()->error);
            }
            
            $stmt->bind_param("is", $barber_id, $date);
            $stmt->execute();
            $stmt->bind_result($appointment_time, $duration);
            
            $booked_slots = [];
            while ($stmt->fetch()) {
                $start = strtotime($appointment_time);
                $end = $start + ($duration * 60);
                $booked_slots[] = [
                    'start' => $start,
                    'end' => $end
                ];
            }
            
            $stmt->close();

            // Generate available time slots
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $interval = 30 * 60; // 30 minutes in seconds
            $available_times = [];

            // Filter out past times for today
            $now = time();
            if ($date === date('Y-m-d')) {
                $start_timestamp = max($start_timestamp, $now + 3600); // Add 1 hour buffer
            }

            for ($time = $start_timestamp; $time < $end_timestamp; $time += $interval) {
                $is_available = true;
                $slot_end = $time + $interval;

                // Check if this slot overlaps with any booked appointments
                foreach ($booked_slots as $booked) {
                    if (($time >= $booked['start'] && $time < $booked['end']) ||
                        ($slot_end > $booked['start'] && $slot_end <= $booked['end']) ||
                        ($time <= $booked['start'] && $slot_end >= $booked['end'])) {
                        $is_available = false;
                        break;
                    }
                }

                if ($is_available) {
                    $available_times[] = date('H:i', $time);
                }
            }

            return $available_times;

        } catch (Exception $e) {
            error_log("Error in getAvailableTimeSlots: " . $e->getMessage());
            throw $e;
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