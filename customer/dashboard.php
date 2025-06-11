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
    $appointment_time, $status, $service_name, $price, $duration,
    $barber_id, $barber_name
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
$stmt->bind_result($service_id, $service_name, $service_price, $service_duration, $service_description);

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

        <!-- Booking Section -->
        <div class="booking-section">
            <h3 class="section-title">Book an Appointment</h3>
            <div class="row">
                <!-- Step 1: Select Date -->
                <div class="col-md-4 mb-4">
                    <h5 class="step-title">1. Choose a Date</h5>
                    <div class="card">
                        <div class="card-body">
                            <div id="dateSelection">
                                <!-- Dates will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Select Time -->
                <div class="col-md-4 mb-4">
                    <h5 class="step-title">2. Choose a Time</h5>
                    <div class="card">
                        <div class="card-body">
                            <div id="timeSelection">
                                <!-- Time slots will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Select Service -->
                <div class="col-md-4 mb-4">
                    <h5 class="step-title">3. Choose a Service</h5>
                    <div class="row g-3" id="serviceSelection">
                        <?php foreach ($services as $service): ?>
                            <div class="col-12">
                                <div class="card service-card" data-service-id="<?php echo $service['id']; ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted"><?php echo htmlspecialchars($service['description']); ?></small>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="h5 mb-0">$<?php echo number_format($service['price'], 2); ?></span>
                                            <span class="badge bg-info"><?php echo $service['duration']; ?> mins</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Summary -->
            <div class="booking-summary">
                <h5 class="section-title">Booking Summary</h5>
                <div id="bookingSummary">
                    <p class="text-muted">Please select a date, time, and service to see your booking summary.</p>
                </div>
                <button class="book-button" id="bookButton" disabled>Book Appointment</button>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let selectedDate = null;
    let selectedTime = null;
    let selectedService = null;

    // Load Available Dates on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAvailableDates();
    });

    // Load Available Dates
    async function loadAvailableDates() {
        const dateSelection = document.getElementById('dateSelection');
        dateSelection.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

        try {
            const response = await fetch('get_available_dates.php');
            const data = await response.json();
            
            if (data.success && data.dates && data.dates.length > 0) {
                // Filter out past dates
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                const futureDates = data.dates.filter(date => {
                    const dateObj = new Date(date.date);
                    dateObj.setHours(0, 0, 0, 0);
                    return dateObj >= today;
                });

                if (futureDates.length > 0) {
                    let datesHTML = '<div class="d-flex flex-wrap gap-2">';
                    futureDates.forEach(date => {
                        datesHTML += `
                            <button class="btn btn-outline-primary time-slot" data-date="${date.date}">
                                ${date.day_name}<br>${date.date}
                            </button>
                        `;
                    });
                    datesHTML += '</div>';
                    dateSelection.innerHTML = datesHTML;

                    // Add click handlers
                    document.querySelectorAll('#dateSelection .time-slot').forEach(btn => {
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('#dateSelection .time-slot').forEach(b => b.classList.remove('selected'));
                            this.classList.add('selected');
                            selectedDate = this.dataset.date;
                            loadAvailableTimes();
                            updateBookingSummary();
                        });
                    });
                } else {
                    dateSelection.innerHTML = '<div class="alert alert-info">No available dates found.</div>';
                }
            } else {
                dateSelection.innerHTML = '<div class="alert alert-info">No available dates found.</div>';
            }
        } catch (error) {
            dateSelection.innerHTML = '<div class="alert alert-danger">Failed to load dates.</div>';
        }
    }

    // Load Available Times
    async function loadAvailableTimes() {
        if (!selectedDate) return;

        const timeSelection = document.getElementById('timeSelection');
        timeSelection.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

        try {
            const response = await fetch(`get_available_times.php?date=${selectedDate}`);
            const data = await response.json();
            
            if (data.success && data.times && data.times.length > 0) {
                let timesHTML = '<div class="d-flex flex-wrap gap-2">';
                data.times.forEach(time => {
                    timesHTML += `
                        <button class="btn btn-outline-primary time-slot" data-time="${time}">
                            ${time}
                        </button>
                    `;
                });
                timesHTML += '</div>';
                timeSelection.innerHTML = timesHTML;

                // Add click handlers
                document.querySelectorAll('#timeSelection .time-slot').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.querySelectorAll('#timeSelection .time-slot').forEach(b => b.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedTime = this.dataset.time;
                        updateBookingSummary();
                    });
                });
            } else {
                timeSelection.innerHTML = '<div class="alert alert-info">No available times found.</div>';
            }
        } catch (error) {
            timeSelection.innerHTML = '<div class="alert alert-danger">Failed to load times.</div>';
        }
    }

    // Service Selection
    document.querySelectorAll('.service-card').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedService = this.dataset.serviceId;
            updateBookingSummary();
        });
    });

    // Update Booking Summary
    function updateBookingSummary() {
        const summary = document.getElementById('bookingSummary');
        const bookButton = document.getElementById('bookButton');

        if (selectedDate && selectedTime && selectedService) {
            const serviceCard = document.querySelector(`.service-card[data-service-id="${selectedService}"]`);
            const serviceName = serviceCard.querySelector('.card-title').textContent;
            const servicePrice = serviceCard.querySelector('.h5').textContent;

            summary.innerHTML = `
                <div class="row">
                    <div class="col-md-4">
                        <strong>Date:</strong><br>
                        ${selectedDate}
                    </div>
                    <div class="col-md-4">
                        <strong>Time:</strong><br>
                        ${selectedTime}
                    </div>
                    <div class="col-md-4">
                        <strong>Service:</strong><br>
                        ${serviceName}
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <strong>Total:</strong> ${servicePrice}
                    </div>
                </div>
            `;
            bookButton.disabled = false;
        } else {
            summary.innerHTML = '<p class="text-muted">Please select a date, time, and service to see your booking summary.</p>';
            bookButton.disabled = true;
        }
    }

    // Book Appointment
    document.getElementById('bookButton').addEventListener('click', async function() {
        if (!selectedDate || !selectedTime || !selectedService) return;

        try {
            const response = await fetch('book_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    service_id: selectedService,
                    date: selectedDate,
                    time: selectedTime
                })
            });

            const data = await response.json();
            if (data.success) {
                alert('Appointment booked successfully!');
                location.reload();
            } else {
                alert(data.message || 'Failed to book appointment');
            }
        } catch (error) {
            alert('Failed to book appointment');
        }
    });

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
    </script>
</body>
</html> 