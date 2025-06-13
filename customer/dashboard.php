<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Debug: Log the session user_id
error_log('Dashboard: user_id in session: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));

// Debug: Log the session contents
error_log('Dashboard: SESSION = ' . print_r($_SESSION, true));

// Prepare the SQL and log it
$sql = "SELECT u.*, COUNT(a.id) as total_appointments, MAX(a.appointment_date) as last_visit FROM users u LEFT JOIN appointments a ON u.id = a.user_id WHERE u.id = ? GROUP BY u.id";
error_log('Dashboard: SQL = ' . $sql . ' [user_id=' . ($_SESSION['user_id'] ?? 'not set') . ']');

$stmt = $db->getConnection()->prepare($sql);
if ($stmt === false) {
    error_log("Error preparing customer query: " . $db->getConnection()->error);
    die("An error occurred. Please try again later.");
}

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

// Debug: Log any SQL errors
if ($stmt->error) {
    error_log('Dashboard: SQL error: ' . $stmt->error);
}

// Bind the result variables
$stmt->bind_result(
    $id, $username, $email, $password, $role, $phone, $created_at, $total_appointments, $last_visit
);

// Fetch the result
if ($stmt->fetch()) {
    $customer = [
        'id' => $id,
        'username' => $username,
        'email' => $email,
        'role' => $role,
        'phone' => $phone,
        'total_appointments' => $total_appointments,
        'last_visit' => $last_visit
    ];
} else {
    error_log("Dashboard: No customer found for user_id: " . $_SESSION['user_id']);
    echo '<div style="margin:2em auto;max-width:500px;text-align:center;">';
    echo '<h2>Customer information not found.</h2>';
    echo '<p>Your account could not be found. Please <a href="../logout.php">log out</a> and try logging in again.</p>';
    echo '<pre>Session: ' . htmlspecialchars(print_r($_SESSION, true)) . '</pre>';
    echo '</div>';
    exit;
}

$stmt->close();

// Get upcoming appointments
$stmt = $db->getConnection()->prepare("
    SELECT a.*, s.name as service_name, s.price, s.duration,
           b.id as barber_id, u.username as barber_name
    FROM appointments a
    JOIN services s ON a.service_id = s.id
    JOIN barbers b ON a.barber_id = b.id
    JOIN users u ON b.user_id = u.id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");

if ($stmt === false) {
    error_log("Error preparing appointments query: " . $db->getConnection()->error);
    die("An error occurred. Please try again later.");
}

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();

// Bind the result variables for appointments
$stmt->bind_result(
    $appointment_id, $user_id, $barber_id, $service_id, $appointment_date,
    $appointment_time, $status, $notes, $created_at,
    $service_name, $price, $duration,
    $barber_id2, $barber_name
);

$appointments = [];
while ($stmt->fetch()) {
    $appointments[] = [
        'id' => $appointment_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'status' => $status,
        'service_name' => $service_name,
        'price' => $price,
        'duration' => $duration,
        'barber_name' => $barber_name
    ];
}

$stmt->close();

// Get available services
$stmt = $db->getConnection()->prepare("SELECT * FROM services ORDER BY price ASC");
$stmt->execute();

// Bind the result variables for services
$stmt->bind_result($service_id, $service_name, $service_description, $service_price, $service_duration, $service_created_at);

$services = [];
while ($stmt->fetch()) {
    $services[] = [
        'id' => $service_id,
        'name' => $service_name,
        'price' => $service_price,
        'duration' => $service_duration,
        'description' => $service_description
    ];
}

$stmt->close();

// Get available barbers
$stmt = $db->getConnection()->prepare("
    SELECT b.id, u.username, u.email, b.bio, b.experience_years
    FROM barbers b
    JOIN users u ON b.user_id = u.id
    WHERE b.status = 'active'
");

if ($stmt === false) {
    error_log("Error preparing barbers query: " . $db->getConnection()->error);
    die("An error occurred. Please try again later.");
}

$stmt->execute();

// Bind the result variables for barbers
$stmt->bind_result($barber_id, $barber_name, $barber_email, $barber_bio, $barber_experience);

$barbers = [];
while ($stmt->fetch()) {
    $barbers[] = [
        'id' => $barber_id,
        'name' => $barber_name,
        'email' => $barber_email,
        'bio' => $barber_bio,
        'experience' => $barber_experience
    ];
}

$stmt->close();

// Sort appointments by date and time
usort($appointments, function($a, $b) {
    return strtotime($b['date'] . ' ' . $b['time']) - 
           strtotime($a['date'] . ' ' . $a['time']);
});

// Get upcoming appointments (today and future)
$upcoming_appointments = array_filter($appointments, function($apt) {
    $appointment_date = strtotime($apt['date']);
    $today = strtotime('today');
    return $appointment_date >= $today;
});

// Get past appointments
$past_appointments = array_filter($appointments, function($apt) {
    $appointment_date = strtotime($apt['date']);
    $today = strtotime('today');
    return $appointment_date < $today;
});

// Add this function before the HTML
function getStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'confirmed':
            return 'success';
        case 'completed':
            return 'info';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/style.css" rel="stylesheet">
    <style>
        .sign-out-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background-color: var(--danger);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sign-out-btn:hover {
            background-color: var(--danger-hover);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .welcome-card:hover {
            transform: translateY(-5px);
        }
        .booking-section {
            background-color: var(--white);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .service-card {
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            transition: all 0.3s;
            background: var(--white);
            cursor: pointer;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: var(--accent-color);
        }
        .service-card.selected {
            border-color: var(--accent-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        .time-slot {
            border: 2px solid var(--gray-200);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin: 0.25rem;
            transition: all 0.3s;
            background: var(--white);
        }
        .time-slot:hover {
            background-color: var(--gray-100);
            border-color: var(--gray-300);
        }
        .time-slot.selected {
            background-color: var(--accent-color);
            color: var(--white);
            border-color: var(--accent-color);
        }
        .time-slot.unavailable {
            background-color: rgba(231, 76, 60, 0.1);
            color: var(--danger);
            border-color: var(--danger);
            cursor: not-allowed;
        }
        .appointment-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            background: var(--white);
            margin-bottom: 1.5rem;
        }
        .appointment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .booking-summary {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-200);
        }
        .step-title {
            color: var(--secondary-color);
            font-weight: 500;
            margin-bottom: 1rem;
        }
        .book-button {
            background: var(--accent-color);
            color: var(--white);
            padding: 0.75rem 2rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.2);
        }
        .book-button:hover:not(:disabled) {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
        }
        .book-button:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']) === '/customer' ? '../logout.php' : BASE_URL . '/logout.php'; ?>" class="sign-out-btn">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>

    <div class="container py-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card welcome-card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="card-title text-white mb-2">Welcome, <?php echo htmlspecialchars($customer['username']); ?>!</h2>
                                <p class="card-text text-white-50">Book your next appointment or manage your existing ones.</p>
                            </div>
                            <a href="profile.php" class="btn btn-light">
                                <i class="fas fa-user"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointments History -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="section-title">Appointments History</h3>
                <?php if (empty($appointments)): ?>
                    <div class="alert alert-info">
                        No appointments found. Book your first service now!
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card appointment-card">
                                    <div class="card-body p-4">
                                        <span class="badge bg-<?php echo getStatusColor($appointment['status']); ?> status-badge">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                        <h5 class="card-title mb-3"><?php echo htmlspecialchars($appointment['service_name']); ?></h5>
                                        <p class="card-text">
                                            <i class="fas fa-calendar me-2 text-primary"></i>
                                            <?php echo date('F j, Y', strtotime($appointment['date'])); ?>
                                            <br>
                                            <i class="fas fa-clock me-2 text-primary"></i>
                                            <?php echo date('g:i A', strtotime($appointment['time'])); ?>
                                            <br>
                                            <i class="fas fa-user me-2 text-primary"></i>
                                            Barber: <?php echo htmlspecialchars($appointment['barber_name']); ?>
                                            <br>
                                            <i class="fas fa-dollar-sign me-2 text-primary"></i>
                                            Price: $<?php echo number_format($appointment['price'], 2); ?>
                                            <?php if ($appointment['status'] === 'cancelled' && !empty($appointment['notes'])): ?>
                                            <br>
                                            <i class="fas fa-info-circle me-2 text-danger"></i>
                                            <span class="text-danger">Cancellation Note: <?php echo htmlspecialchars($appointment['notes']); ?></span>
                                            <?php endif; ?>
                                        </p>
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <button class="btn btn-danger btn-sm" onclick="cancelAppointment(<?php echo $appointment['id']; ?>)">
                                                Cancel Appointment
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Modern & Beautiful Booking Form -->
        <div class="booking-section mb-4">
            <div class="card shadow-lg border-0">
                <div class="card-body p-4">
                    <h3 class="section-title mb-4"><i class="fas fa-calendar-plus text-primary me-2"></i>Book an Appointment</h3>
                    <form id="bookingForm" class="row g-4">
                        <!-- Date Selection -->
                        <div class="col-md-4">
                            <label for="bookingDate" class="form-label">Select Date</label>
                            <select class="form-select" id="bookingDate" name="date" required>
                                <option value="">Choose a date</option>
                                <?php
                                // Get the active barber
                                $barber = $db->getSingleBarber();
                                if ($barber) {
                                    // Get available dates based on barber's schedule
                                    $available_dates = [];
                                    $scheduleSync = new ScheduleSync();
                                    
                                    // Get dates for the next 30 days
                                    for ($i = 0; $i < 30; $i++) {
                                        $date = date('Y-m-d', strtotime("+$i days"));
                                        $day_of_week = strtolower(date('l', strtotime($date)));
                                        
                                        // Check if barber is available on this day
                                        $stmt = $db->getConnection()->prepare("
                                            SELECT start_time, end_time, status 
                                            FROM barber_schedule 
                                            WHERE barber_id = ? AND day_of_week = ?
                                        ");
                                        
                                        $stmt->bind_param("is", $barber['id'], $day_of_week);
                                        $stmt->execute();
                                        $stmt->bind_result($start_time, $end_time, $status);
                                        
                                        if ($stmt->fetch() && $status === 'available' && $start_time && $end_time) {
                                            // Check if there are any available time slots
                                            $time_slots = $scheduleSync->getAvailableTimeSlots($barber['id'], $date);
                                            if (!empty($time_slots)) {
                                                $available_dates[] = $date;
                                            }
                                        }
                                        $stmt->close();
                                    }
                                    
                                    // Display available dates
                                    if (empty($available_dates)) {
                                        echo '<option value="" disabled>No available dates found</option>';
                                    } else {
                                        foreach ($available_dates as $date) {
                                            echo '<option value="' . $date . '">' . date('D, M j, Y', strtotime($date)) . '</option>';
                                        }
                                    }
                                } else {
                                    echo '<option value="" disabled>No barber available</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Time Selection -->
                        <div class="col-md-4">
                            <label for="bookingTime" class="form-label">Select Time</label>
                            <select class="form-select" id="bookingTime" name="time" required disabled>
                                <option value="">Select a date first</option>
                            </select>
                        </div>

                        <!-- Service Selection -->
                        <div class="col-md-4">
                            <label for="bookingService" class="form-label">Select Service</label>
                            <select class="form-select" id="bookingService" name="service_id" required>
                                <option value="">Choose a service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['id']; ?>">
                                        <?php echo htmlspecialchars($service['name']) . ' ($' . number_format($service['price'], 2) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Submit Button -->
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary" id="submitBooking">
                                <i class="fas fa-calendar-check me-2"></i>Book Appointment
                            </button>
                        </div>

                        <!-- Result Message -->
                        <div class="col-12">
                            <div id="bookingResult"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Cancel Appointment
    function cancelAppointment(appointmentId) {
        if (confirm('Are you sure you want to cancel this appointment?')) {
            fetch('<?php echo BASE_URL; ?>/cancel_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ appointment_id: appointmentId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('An error occurred while cancelling the appointment.');
            });
        }
    }

    // Function to load available times
    async function loadAvailableTimes(date) {
        const timeSelect = document.getElementById('bookingTime');
        timeSelect.disabled = true;
        timeSelect.innerHTML = '<option value="">Loading times...</option>';

        try {
            const response = await fetch(`get_available_times.php?date=${date}`);
            const data = await response.json();

            timeSelect.innerHTML = '<option value="">Select a time</option>';
            
            if (data.success && data.times && data.times.length > 0) {
                data.times.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time;
                    option.textContent = time;
                    timeSelect.appendChild(option);
                });
                timeSelect.disabled = false;
            } else {
                timeSelect.innerHTML = '<option value="">No available times</option>';
            }
        } catch (error) {
            console.error('Error loading times:', error);
            timeSelect.innerHTML = '<option value="">Error loading times</option>';
        }
    }

    // Event Listeners
    document.getElementById('bookingDate').addEventListener('change', function() {
        const date = this.value;
        if (date) {
            loadAvailableTimes(date);
        } else {
            const timeSelect = document.getElementById('bookingTime');
            timeSelect.innerHTML = '<option value="">Select a date first</option>';
            timeSelect.disabled = true;
        }
    });

    // Form Submission
    document.getElementById('bookingForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitButton = document.getElementById('submitBooking');
        const resultDiv = document.getElementById('bookingResult');
        
        // Get form values
        const date = document.getElementById('bookingDate').value;
        const time = document.getElementById('bookingTime').value;
        const service_id = parseInt(document.getElementById('bookingService').value);
        
        // Validate form data
        if (!date || !time || !service_id) {
            resultDiv.innerHTML = '<div class="alert alert-danger">Please fill in all fields</div>';
            return;
        }
        
        // Disable submit button and show loading state
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Booking...';
        
        try {
            const response = await fetch('book_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    date: date,
                    time: time,
                    service_id: service_id
                })
            });

            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                // Reset form
                this.reset();
                document.getElementById('bookingTime').disabled = true;
                document.getElementById('bookingTime').innerHTML = '<option value="">Select a date first</option>';
                // Reload page after 2 seconds
                setTimeout(() => location.reload(), 2000);
            } else {
                throw new Error(data.error || 'Failed to book appointment');
            }
        } catch (error) {
            console.error('Booking error:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        } finally {
            // Reset button state
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Book Appointment';
        }
    });
    </script>
</body>
</html> 