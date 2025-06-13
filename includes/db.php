<?php
require_once __DIR__ . '/../config.php';

class Database {
    private $conn;
    
    public function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // User Operations
    public function createUser($username, $email, $password, $role = 'customer', $phone = null) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("INSERT INTO users (username, email, password, role, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $phone);
        return $stmt->execute();
    }
    
    public function updateUserPassword($user_id, $hashed_password) {
        $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $user_id);
        return $stmt->execute();
    }
    
    public function createBarber($user_id, $bio = null, $experience_years = null) {
        $stmt = $this->conn->prepare("INSERT INTO barbers (user_id, bio, experience_years) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $user_id, $bio, $experience_years);
        return $stmt->execute();
    }
    
    public function getUserByEmail($email) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, phone FROM users WHERE email = ?");
            if (!$stmt) {
                error_log("Error preparing getUserByEmail statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                error_log("Error executing getUserByEmail statement: " . $stmt->error);
                return false;
            }
            
            // Bind the result variables
            $stmt->bind_result($id, $username, $db_email, $password, $role, $phone);
            
            // Fetch the result
            if ($stmt->fetch()) {
                return [
                    'id' => $id,
                    'username' => $username,
                    'email' => $db_email,
                    'password' => $password,
                    'role' => $role,
                    'phone' => $phone
                ];
            }
            
            $stmt->close();
            return false;
        } catch (Exception $e) {
            error_log("Error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserByUsername($username) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, phone FROM users WHERE username = ?");
            if (!$stmt) {
                error_log("Error preparing getUserByUsername statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("s", $username);
            if (!$stmt->execute()) {
                error_log("Error executing getUserByUsername statement: " . $stmt->error);
                return false;
            }
            
            // Bind the result variables
            $stmt->bind_result($id, $db_username, $email, $password, $role, $phone);
            
            // Fetch the result
            if ($stmt->fetch()) {
                return [
                    'id' => $id,
                    'username' => $db_username,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'phone' => $phone
                ];
            }
            
            $stmt->close();
            return false;
        } catch (Exception $e) {
            error_log("Error in getUserByUsername: " . $e->getMessage());
            return false;
        }
    }
    
    public function getUserById($id) {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, phone FROM users WHERE id = ?");
            if (!$stmt) {
                error_log("Error preparing getUserById statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                error_log("Error executing getUserById statement: " . $stmt->error);
                return false;
            }
            
            // Bind the result variables
            $stmt->bind_result($db_id, $username, $email, $password, $role, $phone);
            
            // Fetch the result
            if ($stmt->fetch()) {
                return [
                    'id' => $db_id,
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'phone' => $phone
                ];
            }
            
            $stmt->close();
            return false;
        } catch (Exception $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return false;
        }
    }
    
    // Barber Operations
    public function getSingleBarber() {
        $stmt = $this->conn->prepare("
            SELECT b.id, b.user_id, b.bio, b.experience_years, b.status, b.created_at, u.username, u.email, u.phone
            FROM barbers b
            JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active'
            LIMIT 1
        ");
        if (!$stmt) return false;
        $stmt->execute();
        $stmt->bind_result($id, $user_id, $bio, $experience_years, $status, $created_at, $username, $email, $phone);
        if ($stmt->fetch()) {
            return [
                'id' => $id,
                'user_id' => $user_id,
                'bio' => $bio,
                'experience_years' => $experience_years,
                'status' => $status,
                'created_at' => $created_at,
                'username' => $username,
                'email' => $email,
                'phone' => $phone
            ];
        }
        $stmt->close();
        return false;
    }
    
    public function getBarberById($id) {
        $stmt = $this->conn->prepare("
            SELECT b.*, u.username, u.email, u.phone 
            FROM barbers b 
            JOIN users u ON b.user_id = u.id 
            WHERE b.id = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getAllBarbers() {
        $query = "
            SELECT b.id, b.user_id, b.bio, b.experience_years, b.status, b.created_at, u.username, u.email, u.phone
            FROM barbers b
            JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active'
        ";
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            error_log("Error preparing getAllBarbers query: " . $this->conn->error);
            return [];
        }
        if (!$stmt->execute()) {
            error_log("Error executing getAllBarbers query: " . $stmt->error);
            return [];
        }
        $stmt->bind_result($id, $user_id, $bio, $experience_years, $status, $created_at, $username, $email, $phone);
        $barbers = [];
        while ($stmt->fetch()) {
            $barbers[] = [
                'id' => $id,
                'user_id' => $user_id,
                'bio' => $bio,
                'experience_years' => $experience_years,
                'status' => $status,
                'created_at' => $created_at,
                'username' => $username,
                'email' => $email,
                'phone' => $phone
            ];
        }
        $stmt->close();
        return $barbers;
    }
    
    // Service Operations
    public function getAllServices() {
        $stmt = $this->conn->prepare("SELECT id, name, description, price, duration, created_at FROM services ORDER BY price ASC");
        $stmt->execute();
        $stmt->bind_result($id, $name, $description, $price, $duration, $created_at);
        $services = [];
        while ($stmt->fetch()) {
            $services[] = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'duration' => $duration,
                'created_at' => $created_at
            ];
        }
        $stmt->close();
        return $services;
    }
    
    public function getServiceById($id) {
        $stmt = $this->conn->prepare("SELECT id, name, description, price, duration, created_at FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($id, $name, $description, $price, $duration, $created_at);
        if ($stmt->fetch()) {
            $service = [
                'id' => $id,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'duration' => $duration,
                'created_at' => $created_at
            ];
            $stmt->close();
            return $service;
        }
        $stmt->close();
        return false;
    }
    
    // Appointment Operations
    public function createAppointment($user_id, $barber_id, $service_id, $date, $time) {
        try {
            // Log the input parameters
            error_log("Creating appointment with parameters: user_id=$user_id, barber_id=$barber_id, service_id=$service_id, date=$date, time=$time");
            
            // First verify the user exists and is a customer
            $stmt = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer'");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($found_id);
            $user = null;
            if ($stmt->fetch()) {
                $user = ['id' => $found_id];
            }
            $stmt->close();
            
            if (!$user) {
                error_log("Error: User $user_id not found or not a customer");
                return false;
            }
            
            // Create the appointment
            $stmt = $this->conn->prepare("
                INSERT INTO appointments (user_id, barber_id, service_id, appointment_date, appointment_time, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            
            if ($stmt === false) {
                error_log("Error preparing appointment insert statement: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("iiiss", $user_id, $barber_id, $service_id, $date, $time);
            
            if (!$stmt->execute()) {
                error_log("Error executing appointment insert: " . $stmt->error);
                return false;
            }
            
            $appointment_id = $this->conn->insert_id;
            error_log("Appointment created successfully with ID: " . $appointment_id);
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating appointment: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAppointmentsByUser($user_id) {
        try {
            error_log("Fetching appointments for user_id: " . $user_id);
            $stmt = $this->conn->prepare("
                SELECT a.id, a.user_id, a.barber_id, a.service_id, a.appointment_date, a.appointment_time, a.status, a.notes, a.created_at, s.name as service_name, s.price, s.duration, b.id as barber_id, u.username as barber_name
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                JOIN barbers b ON a.barber_id = b.id
                JOIN users u ON b.user_id = u.id
                WHERE a.user_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
            ");
            if ($stmt === false) {
                error_log("Error preparing getAppointmentsByUser statement: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                error_log("Error executing getAppointmentsByUser: " . $stmt->error);
                return [];
            }
            $stmt->bind_result($id, $user_id, $barber_id, $service_id, $appointment_date, $appointment_time, $status, $notes, $created_at, $service_name, $price, $duration, $barber_id2, $barber_name);
            $appointments = [];
            while ($stmt->fetch()) {
                $appointments[] = [
                    'id' => $id,
                    'user_id' => $user_id,
                    'barber_id' => $barber_id,
                    'service_id' => $service_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'status' => $status,
                    'notes' => $notes,
                    'created_at' => $created_at,
                    'service_name' => $service_name,
                    'price' => $price,
                    'duration' => $duration,
                    'barber_name' => $barber_name
                ];
            }
            $stmt->close();
            error_log("Found " . count($appointments) . " appointments for user_id: " . $user_id);
            return $appointments;
        } catch (Exception $e) {
            error_log("Error in getAppointmentsByUser: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAppointmentsByBarber($barber_id) {
        try {
            error_log("Fetching appointments for barber_id: " . $barber_id);
            $stmt = $this->conn->prepare("
                SELECT a.id, a.user_id, a.barber_id, a.service_id, a.appointment_date, a.appointment_time, a.status, a.notes, a.created_at, s.name as service_name, s.price, s.duration, u.username as customer_name, u.phone as customer_phone
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                JOIN users u ON a.user_id = u.id
                WHERE a.barber_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
            ");
            if ($stmt === false) {
                error_log("Error preparing getAppointmentsByBarber statement: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("i", $barber_id);
            if (!$stmt->execute()) {
                error_log("Error executing getAppointmentsByBarber: " . $stmt->error);
                return [];
            }
            $stmt->bind_result($id, $user_id, $barber_id, $service_id, $appointment_date, $appointment_time, $status, $notes, $created_at, $service_name, $price, $duration, $customer_name, $customer_phone);
            $appointments = [];
            while ($stmt->fetch()) {
                $appointments[] = [
                    'id' => $id,
                    'user_id' => $user_id,
                    'barber_id' => $barber_id,
                    'service_id' => $service_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'status' => $status,
                    'notes' => $notes,
                    'created_at' => $created_at,
                    'service_name' => $service_name,
                    'price' => $price,
                    'duration' => $duration,
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone
                ];
            }
            $stmt->close();
            error_log("Found " . count($appointments) . " appointments for barber_id: " . $barber_id);
            error_log("Appointments data: " . print_r($appointments, true));
            return $appointments;
        } catch (Exception $e) {
            error_log("Error in getAppointmentsByBarber: " . $e->getMessage());
            return [];
        }
    }
    
    public function getBarberAppointments($barber_id, $status = 'all', $date = null, $search = '', $year = null, $month = null) {
        try {
            $query = "SELECT a.id, a.user_id, a.barber_id, a.service_id, a.appointment_date, a.appointment_time, a.status, a.notes, a.created_at, s.name as service_name, s.price, s.duration, u.username as customer_name, u.phone as customer_phone FROM appointments a JOIN services s ON a.service_id = s.id JOIN users u ON a.user_id = u.id WHERE a.barber_id = ?";
            $params = [$barber_id];
            $types = "i";
            if ($status !== 'all') {
                $query .= " AND a.status = ?";
                $params[] = $status;
                $types .= "s";
            }
            if ($date) {
                $query .= " AND a.appointment_date = ?";
                $params[] = $date;
                $types .= "s";
            }
            if ($year && $month) {
                $query .= " AND YEAR(a.appointment_date) = ? AND MONTH(a.appointment_date) = ?";
                $params[] = $year;
                $params[] = $month;
                $types .= "ii";
            }
            if ($search) {
                $search = "%$search%";
                $query .= " AND (u.username LIKE ? OR s.name LIKE ?)";
                $params[] = $search;
                $params[] = $search;
                $types .= "ss";
            }
            $query .= " ORDER BY a.appointment_date ASC, a.appointment_time ASC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                error_log("Error preparing getBarberAppointments statement: " . $this->conn->error);
                return [];
            }
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            if (!$stmt->execute()) {
                error_log("Error executing getBarberAppointments: " . $stmt->error);
                return [];
            }
            $stmt->bind_result($id, $user_id, $barber_id, $service_id, $appointment_date, $appointment_time, $status, $notes, $created_at, $service_name, $price, $duration, $customer_name, $customer_phone);
            $appointments = [];
            while ($stmt->fetch()) {
                $appointments[] = [
                    'id' => $id,
                    'user_id' => $user_id,
                    'barber_id' => $barber_id,
                    'service_id' => $service_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'status' => $status,
                    'notes' => $notes,
                    'created_at' => $created_at,
                    'service_name' => $service_name,
                    'price' => $price,
                    'duration' => $duration,
                    'customer_name' => $customer_name,
                    'customer_phone' => $customer_phone
                ];
            }
            $stmt->close();
            return $appointments;
        } catch (Exception $e) {
            error_log("Error in getBarberAppointments: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAppointmentById($appointment_id) {
        $stmt = $this->conn->prepare("SELECT id, user_id, barber_id, service_id, appointment_date, appointment_time, status, notes, created_at FROM appointments WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("i", $appointment_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        $stmt->bind_result($id, $user_id, $barber_id, $service_id, $appointment_date, $appointment_time, $status, $notes, $created_at);
        if ($stmt->fetch()) {
            $appointment = [
                'id' => $id,
                'user_id' => $user_id,
                'barber_id' => $barber_id,
                'service_id' => $service_id,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'status' => $status,
                'notes' => $notes,
                'created_at' => $created_at
            ];
            $stmt->close();
            return $appointment;
        }
        $stmt->close();
        return false;
    }
    
    public function updateAppointmentStatus($appointment_id, $status, $notes = null) {
        error_log("Updating appointment status: ID={$appointment_id}, Status={$status}, Notes={$notes}");
        $sql = "UPDATE appointments SET status = ?, notes = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing update statement: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("ssi", $status, $notes, $appointment_id);
        $result = $stmt->execute();
        if (!$result) {
            error_log("Error executing update statement: " . $stmt->error);
            return false;
        }
        error_log("Appointment status updated successfully");
        return true;
    }
    
    // Barber Availability Operations
    public function getBarberAvailability($barber_id, $date_or_year, $month = null) {
        try {
            error_log("Getting barber availability for barber_id: $barber_id, date/year: $date_or_year, month: $month");
            if ($month !== null) {
                $start_date = sprintf('%04d-%02d-01', $date_or_year, $month);
                $end_date = date('Y-m-t', strtotime($start_date));
                $query = "SELECT date, is_available, start_time, end_time FROM barber_availability WHERE barber_id = ? AND date BETWEEN ? AND ?";
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    error_log("Error preparing monthly availability query: " . $this->conn->error);
                    return [];
                }
                $stmt->bind_param("iss", $barber_id, $start_date, $end_date);
            } else {
                $query = "SELECT date, is_available, start_time, end_time FROM barber_availability WHERE barber_id = ? AND date = ?";
                $stmt = $this->conn->prepare($query);
                if (!$stmt) {
                    error_log("Error preparing single date availability query: " . $this->conn->error);
                    return [];
                }
                $stmt->bind_param("is", $barber_id, $date_or_year);
            }
            if (!$stmt->execute()) {
                error_log("Error executing availability query: " . $stmt->error);
                return [];
            }
            $stmt->bind_result($date, $is_available, $start_time, $end_time);
            $availability = [];
            while ($stmt->fetch()) {
                $availability[$date] = [
                    'is_available' => (bool)$is_available,
                    'start_time' => $start_time,
                    'end_time' => $end_time
                ];
            }
            $stmt->close();
            error_log("Retrieved availability data: " . print_r($availability, true));
            return $availability;
        } catch (Exception $e) {
            error_log("Error in getBarberAvailability: " . $e->getMessage());
            return [];
        }
    }

    public function setBarberAvailability($barber_id, $date, $start_time, $end_time, $is_available) {
        // First check if there's an existing record
        $existing = $this->getBarberAvailability($barber_id, $date);
        
        if ($existing) {
            // Update existing record
            $query = "UPDATE barber_availability SET start_time = ?, end_time = ?, is_available = ? WHERE barber_id = ? AND date = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ssiis", $start_time, $end_time, $is_available, $barber_id, $date);
        } else {
            // Insert new record
            $query = "INSERT INTO barber_availability (barber_id, date, start_time, end_time, is_available) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("isssi", $barber_id, $date, $start_time, $end_time, $is_available);
        }
        
        return $stmt->execute();
    }

    public function isBarberAvailable($barber_id, $date, $time) {
        try {
            // Get the day of the week
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            // Check if the barber has a schedule for this day
            $stmt = $this->conn->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            
            if ($stmt === false) {
                error_log("Error preparing barber schedule query: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("No schedule found for barber $barber_id on $day_of_week");
                return false;
            }
            
            $schedule = $result->fetch_assoc();
            $stmt->close();
            
            // Check if the barber is marked as unavailable
            if ($schedule['status'] === 'unavailable') {
                error_log("Barber $barber_id is marked as unavailable on $day_of_week");
                return false;
            }
            
            // Check if the requested time is within working hours
            if ($time < $schedule['start_time'] || $time > $schedule['end_time']) {
                error_log("Time $time is outside working hours ({$schedule['start_time']} - {$schedule['end_time']})");
                return false;
            }
            
            // Check for existing appointments
            $stmt = $this->conn->prepare("
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
            
            if ($stmt === false) {
                error_log("Error preparing appointment check query: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("isssss", $barber_id, $date, $time, $time, $time, $time);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                error_log("Time slot $time is already booked");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error checking barber availability: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAllCustomers() {
        $query = "SELECT DISTINCT u.id, u.username, u.email, u.phone, MAX(a.appointment_date) as last_visit, COUNT(a.id) as total_appointments FROM users u INNER JOIN appointments a ON u.id = a.user_id WHERE u.role = 'customer' GROUP BY u.id, u.username, u.email, u.phone ORDER BY u.username ASC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("SQL Error in getAllCustomers: " . $this->conn->error);
            return [];
        }
        if (!$stmt->execute()) {
            error_log("Error executing getAllCustomers: " . $stmt->error);
            return [];
        }
        $stmt->bind_result($id, $username, $email, $phone, $last_visit, $total_appointments);
        $customers = [];
        while ($stmt->fetch()) {
            $customers[] = [
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'last_visit' => $last_visit,
                'total_appointments' => $total_appointments
            ];
        }
        $stmt->close();
        return $customers;
    }
    
    public function getBarberCustomers($barber_id, $search = '') {
        $query = "SELECT DISTINCT u.id, u.username, u.email, u.phone, MAX(a.appointment_date) as last_visit, COUNT(a.id) as total_appointments FROM users u INNER JOIN appointments a ON u.id = a.user_id WHERE a.barber_id = ? AND u.role = 'customer'";
        $params = [$barber_id];
        $types = "i";
        if ($search) {
            $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "sss";
        }
        $query .= " GROUP BY u.id, u.username, u.email, u.phone ORDER BY last_visit DESC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        $stmt->bind_result($id, $username, $email, $phone, $last_visit, $total_appointments);
        $customers = [];
        while ($stmt->fetch()) {
            $customers[] = [
                'id' => $id,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'last_visit' => $last_visit,
                'total_appointments' => $total_appointments
            ];
        }
        $stmt->close();
        return $customers;
    }
    
    public function getCustomerAppointments($customer_id, $barber_id) {
        $query = "SELECT a.id, a.user_id, a.barber_id, a.service_id, a.appointment_date, a.appointment_time, a.status, a.notes, a.created_at, s.name as service_name FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.user_id = ? AND a.barber_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        $stmt->bind_param("ii", $customer_id, $barber_id);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . $stmt->error);
        }
        $stmt->bind_result($id, $user_id, $barber_id, $service_id, $appointment_date, $appointment_time, $status, $notes, $created_at, $service_name);
        $appointments = [];
        while ($stmt->fetch()) {
            $appointments[] = [
                'id' => $id,
                'user_id' => $user_id,
                'barber_id' => $barber_id,
                'service_id' => $service_id,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'status' => $status,
                'notes' => $notes,
                'created_at' => $created_at,
                'service_name' => $service_name
            ];
        }
        $stmt->close();
        return $appointments;
    }
    
    public function getBarberIdByUserId($user_id) {
        try {
            error_log("Getting barber ID for user_id: " . $user_id);
            $stmt = $this->conn->prepare("SELECT id FROM barbers WHERE user_id = ?");
            if ($stmt === false) {
                error_log("Error preparing getBarberIdByUserId statement: " . $this->conn->error);
                return null;
            }
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                error_log("Error executing getBarberIdByUserId: " . $stmt->error);
                return null;
            }
            $stmt->bind_result($id);
            if ($stmt->fetch()) {
                error_log("Found barber ID: " . $id . " for user_id: " . $user_id);
                $stmt->close();
                return $id;
            } else {
                error_log("No barber found for user_id: " . $user_id);
                $stmt->close();
                return null;
            }
        } catch (Exception $e) {
            error_log("Error in getBarberIdByUserId: " . $e->getMessage());
            return null;
        }
    }
    
    public function hasAppointmentThisWeek($user_id) {
        try {
            $start_of_week = date('Y-m-d', strtotime('monday this week'));
            $end_of_week = date('Y-m-d', strtotime('sunday this week'));
            error_log("Checking appointments for user $user_id between $start_of_week and $end_of_week");
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ? AND appointment_date BETWEEN ? AND ? AND status != 'cancelled'");
            if ($stmt === false) {
                error_log("Error preparing hasAppointmentThisWeek statement: " . $this->conn->error);
                return false;
            }
            $stmt->bind_param("iss", $user_id, $start_of_week, $end_of_week);
            if (!$stmt->execute()) {
                error_log("Error executing hasAppointmentThisWeek: " . $stmt->error);
                return false;
            }
            $stmt->bind_result($count);
            if ($stmt->fetch()) {
                error_log("Found {$count} appointments this week");
                $stmt->close();
                return $count > 0;
            }
            $stmt->close();
            return false;
        } catch (Exception $e) {
            error_log("Error in hasAppointmentThisWeek: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateBarberWeeklySchedule($barber_id, $schedule) {
        try {
            error_log("Starting schedule update for barber_id: " . $barber_id);
            error_log("Schedule data: " . print_r($schedule, true));

            // Validate barber_id
            if (!is_numeric($barber_id)) {
                throw new Exception("Invalid barber_id: " . $barber_id);
            }

            // Begin transaction
            $this->conn->begin_transaction();

            try {
                // Delete existing schedule
                $deleteQuery = "DELETE FROM barber_schedule WHERE barber_id = ?";
                error_log("Executing delete query: " . $deleteQuery);
                
                $deleteStmt = $this->conn->prepare($deleteQuery);
                if (!$deleteStmt) {
                    throw new Exception("Error preparing delete statement: " . $this->conn->error);
                }
                
                $deleteStmt->bind_param("i", $barber_id);
                if (!$deleteStmt->execute()) {
                    throw new Exception("Error deleting existing schedule: " . $deleteStmt->error);
                }
                error_log("Successfully deleted existing schedule");

                // Insert new schedule
                $insertQuery = "INSERT INTO barber_schedule (barber_id, day_of_week, start_time, end_time, status) VALUES (?, ?, ?, ?, ?)";
                error_log("Preparing insert query: " . $insertQuery);
                
                $insertStmt = $this->conn->prepare($insertQuery);
                if (!$insertStmt) {
                    throw new Exception("Error preparing insert statement: " . $this->conn->error);
                }

                foreach ($schedule as $day => $data) {
                    error_log("Processing day: " . $day . " with data: " . print_r($data, true));
                    
                    if (!isset($data['start_time']) || !isset($data['end_time']) || !isset($data['status'])) {
                        throw new Exception("Missing required fields for $day");
                    }
                    
                    $insertStmt->bind_param("issss", 
                        $barber_id,
                        $day,
                        $data['start_time'],
                        $data['end_time'],
                        $data['status']
                    );
                    
                    if (!$insertStmt->execute()) {
                        throw new Exception("Error inserting schedule for $day: " . $insertStmt->error);
                    }
                    error_log("Successfully inserted schedule for " . $day);
                }

                // Commit transaction
                $this->conn->commit();
                error_log("Successfully completed schedule update");
                return true;

            } catch (Exception $e) {
                // Rollback transaction on error
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Error updating barber schedule: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }

    public function getBarberWeeklySchedule($barber_id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT day_of_week, start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ?
                ORDER BY FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')
            ");
            
            if ($stmt === false) {
                error_log("Error preparing getBarberWeeklySchedule statement: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("i", $barber_id);
            
            if (!$stmt->execute()) {
                error_log("Error executing getBarberWeeklySchedule: " . $stmt->error);
                return [];
            }
            
            // Bind result variables
            $stmt->bind_result($day_of_week, $start_time, $end_time, $status);
            
            $schedule = [];
            while ($stmt->fetch()) {
                $schedule[$day_of_week] = [
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'status' => $status
                ];
            }
            
            // If no schedule found, return default schedule
            if (empty($schedule)) {
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                foreach ($days as $day) {
                    if (!isset($schedule[$day])) {
                        $schedule[$day] = [
                            'start_time' => '09:00',
                            'end_time' => '17:00',
                            'status' => 'available'
                        ];
                    }
                }
            }
            
            $stmt->close();
            return $schedule;
            
        } catch (Exception $e) {
            error_log("Error getting barber schedule: " . $e->getMessage());
            return [];
        }
    }

    public function getAvailableTimeSlots($barber_id, $date) {
        try {
            // Get the day of week
            $day_of_week = strtolower(date('l', strtotime($date)));
            
            // Get the barber's schedule for this day
            $stmt = $this->conn->prepare("
                SELECT start_time, end_time, status
                FROM barber_schedule
                WHERE barber_id = ? AND day_of_week = ?
            ");
            
            if ($stmt === false) {
                error_log("Error preparing schedule query: " . $this->conn->error);
                return [];
            }
            
            $stmt->bind_param("is", $barber_id, $day_of_week);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                error_log("No schedule found for barber $barber_id on $day_of_week");
                return [];
            }
            
            $schedule = $result->fetch_assoc();
            
            if ($schedule['status'] === 'unavailable') {
                error_log("Barber $barber_id is unavailable on $day_of_week");
                return [];
            }
            
            // Get all services to determine minimum duration
            $stmt = $this->conn->prepare("SELECT MIN(duration) as min_duration FROM services");
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
                if ($this->isBarberAvailable($barber_id, $date, $time_slot)) {
                    $time_slots[] = $time_slot;
                }
            }
            
            return $time_slots;
        } catch (Exception $e) {
            error_log("Error getting available time slots: " . $e->getMessage());
            return [];
        }
    }

    public function __destruct() {
        $this->conn->close();
    }

    public function authenticateUser($email, $password, $role) {
        try {
            // Debug log
            error_log("[AUTH DEBUG] Attempting to authenticate user: " . $email . " with role: " . $role);
            
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? AND role = ?");
            if ($stmt === false) {
                error_log("[AUTH DEBUG] Prepare failed: " . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("ss", $email, $role);
            if (!$stmt->execute()) {
                error_log("[AUTH DEBUG] Execute failed: " . $stmt->error);
                return false;
            }
            
            $stmt->bind_result($id, $username, $db_email, $hashed_password, $db_role);
            
            if ($stmt->fetch()) {
                error_log("[AUTH DEBUG] User found, verifying password");
                if (password_verify($password, $hashed_password)) {
                    error_log("[AUTH DEBUG] Password verified successfully");
                    return [
                        'id' => $id,
                        'username' => $username,
                        'email' => $db_email,
                        'role' => $db_role
                    ];
                } else {
                    error_log("[AUTH DEBUG] Password verification failed");
                }
            } else {
                error_log("[AUTH DEBUG] No user found with email: " . $email . " and role: " . $role);
            }
            
            $stmt->close();
            return false;
        } catch (Exception $e) {
            error_log("[AUTH DEBUG] Exception: " . $e->getMessage());
            return false;
        }
    }

    public function getSingleCustomer() {
        try {
            $stmt = $this->conn->prepare("SELECT id, username, email, password, role, phone, created_at FROM users WHERE role = 'customer' LIMIT 1");
            if ($stmt === false) {
                error_log("Error preparing getSingleCustomer query: " . $this->conn->error);
                return null;
            }
            if (!$stmt->execute()) {
                error_log("Error executing getSingleCustomer query: " . $stmt->error);
                return null;
            }
            $stmt->bind_result($id, $username, $email, $password, $role, $phone, $created_at);
            if ($stmt->fetch()) {
                $customer = [
                    'id' => $id,
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role,
                    'phone' => $phone,
                    'created_at' => $created_at
                ];
                $stmt->close();
                return $customer;
            }
            $stmt->close();
            return null;
        } catch (Exception $e) {
            error_log("Error in getSingleCustomer: " . $e->getMessage());
            return null;
        }
    }

    public function getAppointmentsByCustomer($customer_id) {
        try {
            error_log("Fetching appointments for customer_id: " . $customer_id);
            $stmt = $this->conn->prepare("
                SELECT a.id, a.user_id, a.barber_id, a.service_id, a.appointment_date, a.appointment_time, a.status, a.notes, a.created_at, s.name as service_name, s.price, s.duration, b.id as barber_id, u.username as barber_name
                FROM appointments a
                JOIN services s ON a.service_id = s.id
                JOIN barbers b ON a.barber_id = b.id
                JOIN users u ON b.user_id = u.id
                WHERE a.user_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
            ");
            if ($stmt === false) {
                error_log("Error preparing getAppointmentsByCustomer statement: " . $this->conn->error);
                return [];
            }
            $stmt->bind_param("i", $customer_id);
            if (!$stmt->execute()) {
                error_log("Error executing getAppointmentsByCustomer: " . $stmt->error);
                return [];
            }
            $stmt->bind_result($id, $user_id, $barber_id, $service_id, $appointment_date, $appointment_time, $status, $notes, $created_at, $service_name, $price, $duration, $barber_id2, $barber_name);
            $appointments = [];
            while ($stmt->fetch()) {
                $appointments[] = [
                    'id' => $id,
                    'user_id' => $user_id,
                    'barber_id' => $barber_id,
                    'service_id' => $service_id,
                    'appointment_date' => $appointment_date,
                    'appointment_time' => $appointment_time,
                    'status' => $status,
                    'notes' => $notes,
                    'created_at' => $created_at,
                    'service_name' => $service_name,
                    'price' => $price,
                    'duration' => $duration,
                    'barber_name' => $barber_name
                ];
            }
            $stmt->close();
            error_log("Found " . count($appointments) . " appointments for customer_id: " . $customer_id);
            error_log("Appointments data: " . print_r($appointments, true));
            return $appointments;
        } catch (Exception $e) {
            error_log("Error in getAppointmentsByCustomer: " . $e->getMessage());
            return [];
        }
    }

    public function getWorkingHours() {
        $stmt = $this->conn->prepare("SELECT id, day_of_week, day_name, open_time, close_time, is_working, created_at FROM working_hours ORDER BY day_of_week");
        if (!$stmt) {
            error_log("Error getting working hours: " . $this->conn->error);
            return [];
        }
        if (!$stmt->execute()) {
            error_log("Error executing working hours: " . $stmt->error);
            return [];
        }
        $stmt->bind_result($id, $day_of_week, $day_name, $open_time, $close_time, $is_working, $created_at);
        $hours = [];
        while ($stmt->fetch()) {
            $hours[] = [
                'id' => $id,
                'day_of_week' => $day_of_week,
                'day_name' => $day_name,
                'open_time' => $open_time,
                'close_time' => $close_time,
                'is_working' => $is_working,
                'created_at' => $created_at
            ];
        }
        $stmt->close();
        return $hours;
    }

    public function updateUser($id, $username, $email, $phone) {
        $stmt = $this->conn->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("sssi", $username, $email, $phone, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAvailableDates($barber_id, $start_date = null, $end_date = null) {
        try {
            // If no dates provided, default to next 30 days
            if (!$start_date) {
                $start_date = date('Y-m-d');
            }
            if (!$end_date) {
                $end_date = date('Y-m-d', strtotime('+30 days'));
            }

            // Get barber's weekly schedule
            $weekly_schedule = $this->getBarberWeeklySchedule($barber_id);
            
            // Get working hours
            $working_hours = $this->getWorkingHours();
            
            $available_dates = [];
            $current_date = new DateTime($start_date);
            $end_datetime = new DateTime($end_date);
            
            while ($current_date <= $end_datetime) {
                $day_name = strtolower($current_date->format('l'));
                $date_str = $current_date->format('Y-m-d');
                
                // Check if barber has specific schedule for this day
                if (isset($weekly_schedule[$day_name])) {
                    if ($weekly_schedule[$day_name]['status'] === 'available') {
                        $available_dates[] = $date_str;
                    }
                } else {
                    // If no specific schedule, check working hours
                    $day_of_week = $current_date->format('N');
                    foreach ($working_hours as $hours) {
                        if ($hours['day_of_week'] == $day_of_week && $hours['is_working']) {
                            $available_dates[] = $date_str;
                            break;
                        }
                    }
                }
                
                $current_date->modify('+1 day');
            }
            
            return $available_dates;
        } catch (Exception $e) {
            error_log("Error in getAvailableDates: " . $e->getMessage());
            return [];
        }
    }
}
?> 