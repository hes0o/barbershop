<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/schedule_sync.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Check if this is an AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // Set headers for JSON response
    header('Content-Type: application/json');

    try {
        // Log the raw input for debugging
        $raw_input = file_get_contents('php://input');
        error_log("Raw input: " . $raw_input);

        // Basic validation
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
            throw new Exception('Please log in as a customer to book appointments');
        }

        // Get and validate input
        $data = json_decode($raw_input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }

        if (!$data) {
            throw new Exception('Invalid request data');
        }

        // Log the decoded data
        error_log("Decoded data: " . print_r($data, true));

        // Validate required fields
        $required_fields = ['service_id', 'date', 'time'];
        $missing_fields = array_filter($required_fields, function($field) use ($data) {
            return !isset($data[$field]) || $data[$field] === '';
        });

        if (!empty($missing_fields)) {
            throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
        }

        // Validate and sanitize input
        $service_id = filter_var($data['service_id'], FILTER_VALIDATE_INT);
        if ($service_id === false) {
            throw new Exception('Invalid service selection');
        }

        // Validate date format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date'])) {
            throw new Exception('Invalid date format. Expected YYYY-MM-DD');
        }

        // Validate time format (HH:MM)
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['time'])) {
            throw new Exception('Invalid time format. Expected HH:MM');
        }

        // Validate date is not in the past
        $appointment_date = strtotime($data['date']);
        $today = strtotime('today');
        if ($appointment_date < $today) {
            throw new Exception('Cannot book appointments in the past');
        }

        // Initialize schedule sync
        $scheduleSync = new ScheduleSync();

        // Get database connection
        $db = new Database();
        $conn = $db->getConnection();

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert appointment
            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    user_id, barber_id, service_id, 
                    appointment_date, appointment_time, 
                    status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $stmt->bind_param(
                "iiiss",
                $_SESSION['user_id'],
                $barber['id'],
                $service_id,
                $data['date'],
                $data['time']
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to create appointment');
            }

            $appointment_id = $conn->insert_id;

            // Commit transaction
            $conn->commit();

            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Appointment booked successfully',
                'appointment_id' => $appointment_id
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        error_log("Booking error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    } catch (Error $e) {
        error_log("System error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'A system error occurred. Please try again.'
        ]);
    }
    exit;
}

// Regular page load - show booking interface
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();
$barber = $db->getSingleBarber();
$services = $db->getAllServices();

// Get available dates for the next 30 days
$available_dates = $db->getAvailableDates($barber['id']);

// Get working hours
$working_hours = $db->getWorkingHours();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .service-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .service-card:hover {
            transform: translateY(-5px);
        }
        .service-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .time-slot {
            cursor: pointer;
            transition: all 0.3s;
        }
        .time-slot:hover {
            background-color: #e9ecef;
        }
        .time-slot.selected {
            background-color: #0d6efd;
            color: white;
        }
        .time-slot.unavailable {
            background-color: #f8d7da;
            color: #dc3545;
            cursor: not-allowed;
        }
        .date-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .date-card:hover {
            transform: translateY(-5px);
        }
        .date-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Book an Appointment</h2>
                        
                        <!-- Step 1: Select Service -->
                        <div id="step1" class="mb-4">
                            <h4>1. Select a Service</h4>
                            <div class="row">
                                <?php foreach ($services as $service): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card service-card" onclick="selectService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>', <?php echo $service['price']; ?>, <?php echo $service['duration']; ?>)">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h5>
                                                <p class="card-text">
                                                    <i class="fas fa-dollar-sign"></i> <?php echo number_format($service['price'], 2); ?><br>
                                                    <i class="fas fa-clock"></i> <?php echo $service['duration']; ?> minutes
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 2: Select Date -->
                        <div id="step2" class="mb-4" style="display: none;">
                            <h4>2. Select a Date</h4>
                            <div class="row">
                                <?php foreach ($available_dates as $date): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card date-card" onclick="selectDate('<?php echo $date; ?>')">
                                            <div class="card-body text-center">
                                                <h5 class="card-title"><?php echo date('D, M j', strtotime($date)); ?></h5>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Step 3: Select Time -->
                        <div id="step3" class="mb-4" style="display: none;">
                            <h4>3. Select a Time</h4>
                            <div id="timeSlots" class="row">
                                <!-- Time slots will be populated dynamically -->
                            </div>
                        </div>

                        <!-- Booking Summary -->
                        <div id="bookingSummary" class="alert alert-info" style="display: none;">
                            <h5>Booking Summary</h5>
                            <p id="summaryText"></p>
                            <button class="btn btn-primary" onclick="confirmBooking()">Confirm Booking</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let selectedService = null;
    let selectedDate = null;
    let selectedTime = null;

    function selectService(id, name, price, duration) {
        selectedService = { id, name, price, duration };
        
        // Update UI
        document.querySelectorAll('.service-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Show next step
        document.getElementById('step2').style.display = 'block';
    }

    function selectDate(date) {
        selectedDate = date;
        
        // Update UI
        document.querySelectorAll('.date-card').forEach(card => {
            card.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Show next step and load time slots
        document.getElementById('step3').style.display = 'block';
        loadTimeSlots(date);
    }

    function loadTimeSlots(date) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        timeSlotsContainer.innerHTML = '<div class="col-12 text-center"><div class="spinner-border" role="status"></div></div>';

        fetch('get_available_times.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                date: date,
                service_id: selectedService.id
            })
        })
        .then(response => response.json())
        .then(data => {
            timeSlotsContainer.innerHTML = '';
            if (data.success && data.times.length > 0) {
                data.times.forEach(time => {
                    const timeSlot = document.createElement('div');
                    timeSlot.className = 'col-md-3 mb-3';
                    timeSlot.innerHTML = `
                        <div class="time-slot p-2 text-center border rounded" onclick="selectTime('${time}')">
                            ${time}
                        </div>
                    `;
                    timeSlotsContainer.appendChild(timeSlot);
                });
            } else {
                timeSlotsContainer.innerHTML = '<div class="col-12 text-center">No available time slots for this date.</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeSlotsContainer.innerHTML = '<div class="col-12 text-center text-danger">Error loading time slots.</div>';
        });
    }

    function selectTime(time) {
        selectedTime = time;
        
        // Update UI
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
        // Show booking summary
        updateBookingSummary();
    }

    function updateBookingSummary() {
        if (selectedService && selectedDate && selectedTime) {
            const summary = document.getElementById('bookingSummary');
            const summaryText = document.getElementById('summaryText');
            
            summaryText.innerHTML = `
                <strong>Service:</strong> ${selectedService.name}<br>
                <strong>Date:</strong> ${new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}<br>
                <strong>Time:</strong> ${selectedTime}<br>
                <strong>Price:</strong> $${selectedService.price.toFixed(2)}<br>
                <strong>Duration:</strong> ${selectedService.duration} minutes
            `;
            
            summary.style.display = 'block';
        }
    }

    function confirmBooking() {
        if (!selectedService || !selectedDate || !selectedTime) {
            alert('Please complete all steps before confirming your booking.');
            return;
        }

        // Show loading state
        const confirmButton = document.querySelector('#bookingSummary button');
        const originalText = confirmButton.innerHTML;
        confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Booking...';
        confirmButton.disabled = true;

        fetch('book_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                service_id: selectedService.id,
                date: selectedDate,
                time: selectedTime
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Appointment booked successfully!');
                window.location.href = 'dashboard.php';
            } else {
                throw new Error(data.error || data.message || 'Failed to book appointment');
            }
        })
        .catch(error => {
            console.error('Booking error:', error);
            alert('Error: ' + error.message);
        })
        .finally(() => {
            // Reset button state
            confirmButton.innerHTML = originalText;
            confirmButton.disabled = false;
        });
    }
    </script>
</body>
</html>
