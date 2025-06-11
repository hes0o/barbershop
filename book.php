<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$db = new Database();

// Get all services
$services = $db->getAllServices();

// Get working hours
$working_hours = $db->getWorkingHours();
$working_hours_map = [];
foreach ($working_hours as $hours) {
    $working_hours_map[$hours['day_of_week']] = $hours;
}

// Get available dates (next 14 days)
$available_dates = [];
$today = date('Y-m-d');
for ($i = 0; $i < 14; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $day_of_week = date('N', strtotime($date));
    
    // Skip if no working hours for this day
    if (!isset($working_hours_map[$day_of_week])) {
        continue;
    }
    
    // Add date if barber is working
    $available_dates[] = [
        'date' => $date,
        'day_name' => date('D', strtotime($date)),
        'day_number' => date('d', strtotime($date)),
        'month' => date('M', strtotime($date))
    ];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['service_id']) && isset($_POST['appointment_date']) && isset($_POST['appointment_time'])) {
        
        $service_id = $_POST['service_id'];
        $date = $_POST['appointment_date'];
        $time = $_POST['appointment_time'];
        
        error_log("Processing booking request - Service ID: $service_id, Date: $date, Time: $time");
        
        // Check if user already has an appointment this week
        if ($db->hasAppointmentThisWeek($_SESSION['user_id'])) {
            $error = "You already have an appointment scheduled for this week. Please wait until next week to book another appointment.";
            error_log("User already has an appointment this week");
        } else {
            // Get service duration
            $service = $db->getServiceById($service_id);
            if (!$service) {
                $error = "Invalid service selected.";
                error_log("Invalid service ID: $service_id");
            } else {
                // Get the first available barber
                $barber = $db->getSingleBarber();
                if (!$barber) {
                    $error = "No barbers available at this time.";
                    error_log("No barbers found");
                } else {
                    error_log("Found barber - ID: {$barber['id']}, Name: {$barber['username']}");
                    
                    // Check if time slot is still available
                    if ($db->isTimeSlotAvailable($barber['id'], $date, $time, $service['duration'])) {
                        error_log("Time slot is available - Creating appointment");
                        $result = $db->createAppointment(
                            $_SESSION['user_id'],
                            $barber['id'],
                            $service_id,
                            $date,
                            $time
                        );
                        
                        if ($result) {
                            error_log("Appointment created successfully");
                            $_SESSION['success'] = "Appointment booked successfully! You will be redirected to your appointments page.";
                            header('Location: appointments.php');
                            exit;
                        } else {
                            $error = "Failed to book appointment. Please try again.";
                            error_log("Failed to create appointment");
                        }
                    } else {
                        $error = "Selected time slot is no longer available. Please choose another time.";
                        error_log("Time slot is not available");
                    }
                }
            }
        }
    }
}

// Get current step
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Include header after all potential redirects
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-4">Book an Appointment</h4>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" id="bookingForm" onsubmit="return validateAndSubmit(event)">
                        <!-- Step 1: Service Selection -->
                        <div class="booking-step" id="step1">
                            <h5 class="mb-3">1. Select Service</h5>
                            <div class="row">
                                <?php foreach ($services as $service): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100 service-card">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="service_id" id="service_<?php echo $service['id']; ?>" 
                                                           value="<?php echo $service['id']; ?>" required>
                                                    <label class="form-check-label" for="service_<?php echo $service['id']; ?>">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($service['name']); ?></h6>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php echo $service['duration']; ?> minutes
                                                        </p>
                                                        <p class="text-primary mb-0">
                                                            <i class="fas fa-dollar-sign me-1"></i>
                                                            <?php echo number_format($service['price'], 2); ?>
                                                        </p>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-end mt-3">
                                <button type="button" class="btn btn-primary" onclick="nextStep(1)">
                                    Next <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Date Selection -->
                        <div class="booking-step d-none" id="step2">
                            <h5 class="mb-3">2. Select Date</h5>
                            <?php if (empty($available_dates)): ?>
                                <div class="alert alert-warning">
                                    No available dates found. Please check back later.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($available_dates as $date): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100 date-card">
                                                <div class="card-body text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" 
                                                               name="appointment_date" id="date_<?php echo $date['date']; ?>" 
                                                               value="<?php echo $date['date']; ?>" required>
                                                        <label class="form-check-label" for="date_<?php echo $date['date']; ?>">
                                                            <h6 class="mb-1"><?php echo $date['day_name']; ?></h6>
                                                            <p class="mb-0"><?php echo $date['month'] . ' ' . $date['day_number']; ?></p>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                    Next <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Time Selection -->
                        <div class="booking-step d-none" id="step3">
                            <h5 class="mb-3">3. Select Time</h5>
                            <div class="row" id="timeSlots">
                                <!-- Time slots will be loaded dynamically -->
                                <div class="col-12 text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </button>
                                <button type="submit" class="btn btn-success" id="bookButton" disabled>
                                    <i class="fas fa-calendar-check me-1"></i> Book Appointment
                                </button>
                            </div>
                        </div>
                        
                        <!-- Hidden input for appointment time -->
                        <input type="hidden" name="appointment_time" id="appointment_time">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Your Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="appointment-details">
                    <p><strong>Service:</strong> <span id="confirmService"></span></p>
                    <p><strong>Date:</strong> <span id="confirmDate"></span></p>
                    <p><strong>Time:</strong> <span id="confirmTime"></span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBooking">Confirm Booking</button>
            </div>
        </div>
    </div>
</div>

<style>
.service-card, .barber-card, .date-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.service-card:hover, .barber-card:hover, .date-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.form-check-input:checked + .form-check-label .card {
    border-color: var(--bs-primary);
    background-color: var(--bs-primary-bg-subtle);
}

.time-slot {
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.time-slot:hover {
    background-color: #f8f9fa;
}

.time-slot.selected {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

.time-slot.unavailable {
    background-color: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.7;
}
</style>

<script>
let selectedDate = null;
let selectedTime = null;

function validateAndSubmit(event) {
    event.preventDefault();
    
    // Validate all steps
    if (!validateStep(1) || !validateStep(2) || !selectedTime) {
        alert('Please complete all steps before booking');
        return false;
    }
    
    // Set the appointment time in the hidden input
    document.getElementById('appointment_time').value = selectedTime;
    
    // Show confirmation modal
    const serviceName = document.querySelector('input[name="service_id"]:checked')
        .closest('.card-body')
        .querySelector('h6').textContent;
    
    document.getElementById('confirmService').textContent = serviceName;
    document.getElementById('confirmDate').textContent = new Date(selectedDate).toLocaleDateString();
    document.getElementById('confirmTime').textContent = formatTime(selectedTime);
    
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
    
    return false;
}

function nextStep(step) {
    // Validate current step
    if (!validateStep(step)) {
        return;
    }
    
    // Hide current step
    document.getElementById(`step${step}`).classList.add('d-none');
    
    // Show next step
    document.getElementById(`step${step + 1}`).classList.remove('d-none');
    
    // If moving to time selection, load available slots
    if (step === 2) {
        loadTimeSlots();
    }
}

function prevStep(step) {
    // Hide current step
    document.getElementById(`step${step}`).classList.add('d-none');
    
    // Show previous step
    document.getElementById(`step${step - 1}`).classList.remove('d-none');
}

function validateStep(step) {
    const form = document.getElementById('bookingForm');
    let isValid = true;
    
    if (step === 1) {
        // Service selection validation
        const serviceInputs = form.querySelectorAll('input[name="service_id"]');
        let serviceSelected = false;
        
        serviceInputs.forEach(input => {
            if (input.checked) {
                serviceSelected = true;
                input.closest('.card').classList.remove('border-danger');
            }
        });
        
        if (!serviceSelected) {
            isValid = false;
            serviceInputs.forEach(input => {
                input.closest('.card').classList.add('border-danger');
            });
            alert('Please select a service to continue');
        }
    } else if (step === 2) {
        // Date selection validation
        const dateInputs = form.querySelectorAll('input[name="appointment_date"]');
        let dateSelected = false;
        
        dateInputs.forEach(input => {
            if (input.checked) {
                dateSelected = true;
                input.closest('.card').classList.remove('border-danger');
            }
        });
        
        if (!dateSelected) {
            isValid = false;
            dateInputs.forEach(input => {
                input.closest('.card').classList.add('border-danger');
            });
            alert('Please select a date to continue');
        }
    }
    
    return isValid;
}

function selectTimeSlot(time) {
    selectedTime = time;
    
    // Remove selected class from all slots
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    
    // Add selected class to clicked slot
    event.currentTarget.classList.add('selected');
    
    // Enable book button
    document.getElementById('bookButton').disabled = false;
}

function formatTime(time) {
    return new Date(`2000-01-01T${time}`).toLocaleTimeString([], { 
        hour: 'numeric', 
        minute: '2-digit'
    });
}

function loadTimeSlots() {
    const serviceId = document.querySelector('input[name="service_id"]:checked').value;
    const date = document.querySelector('input[name="appointment_date"]:checked').value;
    selectedDate = date;
    
    // Show loading spinner
    const timeSlots = document.getElementById('timeSlots');
    timeSlots.innerHTML = `
        <div class="col-12 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Fetch available time slots
    fetch(`get_available_slots.php?service_id=${serviceId}&date=${date}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                timeSlots.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            ${data.error}
                        </div>
                    </div>
                `;
                document.getElementById('bookButton').disabled = true;
                return;
            }
            
            if (data.length === 0) {
                timeSlots.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-info">
                            No available time slots for this date. Please select another date.
                        </div>
                    </div>
                `;
                document.getElementById('bookButton').disabled = true;
                return;
            }
            
            // Display time slots
            timeSlots.innerHTML = data.map(slot => `
                <div class="col-md-4 mb-3">
                    <div class="time-slot ${slot.available ? '' : 'unavailable'}"
                         onclick="${slot.available ? `selectTimeSlot('${slot.time}')` : ''}">
                        ${formatTime(slot.time)}
                    </div>
                </div>
            `).join('');
            
            document.getElementById('bookButton').disabled = true;
        })
        .catch(error => {
            console.error('Error:', error);
            timeSlots.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">
                        Error loading time slots. Please try again.
                    </div>
                </div>
            `;
            document.getElementById('bookButton').disabled = true;
        });
}

// Add event listeners for service selection
document.addEventListener('DOMContentLoaded', function() {
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Remove border-danger from all cards
            serviceCards.forEach(c => c.classList.remove('border-danger'));
            
            // Add selected styling
            serviceCards.forEach(c => c.classList.remove('border-primary'));
            this.classList.add('border-primary');
        });
    });
    
    const dateCards = document.querySelectorAll('.date-card');
    dateCards.forEach(card => {
        card.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Remove border-danger from all cards
            dateCards.forEach(c => c.classList.remove('border-danger'));
            
            // Add selected styling
            dateCards.forEach(c => c.classList.remove('border-primary'));
            this.classList.add('border-primary');
        });
    });
});

document.getElementById('confirmBooking').addEventListener('click', function() {
    document.getElementById('bookingForm').submit();
});
</script>

<?php include 'includes/footer.php'; ?> 