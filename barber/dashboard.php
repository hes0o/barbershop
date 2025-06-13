<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();
$barber = $db->getSingleBarber();

// Get today's date
$today = date('Y-m-d');

// Get appointments stats
$all_appointments = $db->getBarberAppointments($barber['id'], 'all');
$today_appointments = array_filter($all_appointments, function($apt) use ($today) {
    return isset($apt['appointment_date']) && $apt['appointment_date'] === $today;
});

$upcoming_appointments = array_filter($all_appointments, function($apt) use ($today) {
    return isset($apt['appointment_date']) && $apt['appointment_date'] > $today;
});

$completed_appointments = array_filter($all_appointments, function($apt) {
    return isset($apt['status']) && $apt['status'] === 'completed';
});

$pending_appointments = array_filter($all_appointments, function($apt) {
    return isset($apt['status']) && $apt['status'] === 'pending';
});

// Get weekly schedule
$weekly_schedule = $db->getBarberWeeklySchedule($barber['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barber Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .schedule-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .time-slot {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .time-slot:hover {
            background-color: #e9ecef;
        }
        .time-slot.selected {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .time-slot.unavailable {
            background-color: #f8d7da;
            color: #dc3545;
            cursor: not-allowed;
        }
        .appointment-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .appointment-time {
            font-size: 1.2rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .appointment-service {
            color: #6c757d;
        }
        .appointment-status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-completed {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .sign-out-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sign-out-btn:hover {
            background-color: #c82333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-light">
    <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']) === '/barber' ? '../logout.php' : BASE_URL . '/logout.php'; ?>" class="sign-out-btn">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>
    <div class="container-fluid py-4">
        <!-- Stats Section -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-value"><?php echo count($today_appointments); ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo count($upcoming_appointments); ?></div>
                <div class="stat-label">Upcoming Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo count($completed_appointments); ?></div>
                <div class="stat-label">Completed Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon text-warning">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?php echo count($pending_appointments); ?></div>
                <div class="stat-label">Pending Appointments</div>
            </div>
        </div>

        <div class="row">
            <!-- Weekly Schedule Section -->
            <div class="col-md-6">
                <div class="schedule-card">
                    <h4 class="mb-4">Weekly Schedule</h4>
                    <form id="weeklyScheduleForm" method="POST">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day) {
                                        $dayLower = strtolower($day);
                                        $daySchedule = $weekly_schedule[$dayLower] ?? [
                                            'start_time' => '09:00',
                                            'end_time' => '17:00',
                                            'status' => 'available'
                                        ];
                                        ?>
                                        <tr>
                                            <td><?php echo $day; ?></td>
                                            <td>
                                                <select class="form-select" name="schedule[<?php echo $dayLower; ?>][start_time]">
                                                    <?php
                                                    for ($h = 8; $h <= 20; $h++) {
                                                        for ($m = 0; $m < 60; $m += 30) {
                                                            $time = sprintf('%02d:%02d', $h, $m);
                                                            $selected = ($daySchedule['start_time'] ?? '') === $time ? 'selected' : '';
                                                            echo "<option value=\"$time\" $selected>$time</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-select" name="schedule[<?php echo $dayLower; ?>][end_time]">
                                                    <?php
                                                    for ($h = 8; $h <= 20; $h++) {
                                                        for ($m = 0; $m < 60; $m += 30) {
                                                            $time = sprintf('%02d:%02d', $h, $m);
                                                            $selected = ($daySchedule['end_time'] ?? '') === $time ? 'selected' : '';
                                                            echo "<option value=\"$time\" $selected>$time</option>";
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-select" name="schedule[<?php echo $dayLower; ?>][status]">
                                                    <option value="available" <?php echo $daySchedule['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                    <option value="unavailable" <?php echo $daySchedule['status'] === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                    </form>
                </div>
            </div>

            <!-- Today's and Upcoming Appointments Section -->
            <div class="col-md-6">
                <div class="schedule-card">
                    <h4 class="mb-4">Today's Appointments</h4>
                    <?php if (empty($today_appointments)): ?>
                        <div class="alert alert-info">
                            No appointments scheduled for today.
                        </div>
                    <?php else: ?>
                        <?php foreach ($today_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="appointment-time">
                                            <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                        <div class="appointment-service">
                                            <?php echo htmlspecialchars($appointment['service_name']); ?>
                                        </div>
                                        <div class="mt-2">
                                            <i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($appointment['customer_name']); ?>
                                            <br>
                                            <i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($appointment['customer_phone'] ?? 'No phone number'); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success ms-2" 
                                                    onclick="updateAppointmentStatus(<?php echo $appointment['id']; ?>, 'confirmed')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="updateAppointmentStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <h4 class="mb-4 mt-5">Upcoming Appointments</h4>
                    <?php if (empty($upcoming_appointments)): ?>
                        <div class="alert alert-info">
                            No upcoming appointments scheduled.
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming_appointments as $appointment): ?>
                            <div class="appointment-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="appointment-time">
                                            <?php echo date('D, M j', strtotime($appointment['appointment_date'])); ?> - <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                        <div class="appointment-service">
                                            <?php echo htmlspecialchars($appointment['service_name']); ?>
                                        </div>
                                        <div class="mt-2">
                                            <i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($appointment['customer_name']); ?>
                                            <br>
                                            <i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($appointment['customer_phone'] ?? 'No phone number'); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="appointment-status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success ms-2" 
                                                    onclick="updateAppointmentStatus(<?php echo $appointment['id']; ?>, 'confirmed')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="updateAppointmentStatus(<?php echo $appointment['id']; ?>, 'cancelled')">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const weeklyScheduleForm = document.getElementById('weeklyScheduleForm');

        if (weeklyScheduleForm) {
            weeklyScheduleForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Get submit button and store original text
                const submitButton = this.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                
                try {
                    // Show loading state
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';

                    // Get form data
                    const formData = new FormData(this);
                    
                    // Send request
                    const response = await fetch('update_schedule.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();

                    if (data.success) {
                        // Show success message
                        alert('Schedule updated successfully!');
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'Failed to update schedule');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert(error.message);
                } finally {
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        }
    });

    function updateAppointmentStatus(appointmentId, status) {
        if (status === 'cancelled') {
            // Show modal for cancellation note
            const modalHtml = `
                <div class="modal fade" id="cancelModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Cancel Appointment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="cancelNote" class="form-label">Reason for cancellation (optional)</label>
                                    <textarea class="form-control" id="cancelNote" rows="3" placeholder="Enter reason for cancellation..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-danger" onclick="confirmCancellation(${appointmentId})">Cancel Appointment</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('cancelModal');
            if (existingModal) {
                existingModal.remove();
            }
            
            // Add new modal to body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();
        } else {
            // For other status updates, proceed as normal
            if (!confirm('Are you sure you want to ' + status + ' this appointment?')) {
                return;
            }
            sendStatusUpdate(appointmentId, status);
        }
    }

    function confirmCancellation(appointmentId) {
        const note = document.getElementById('cancelNote').value;
        sendStatusUpdate(appointmentId, 'cancelled', note);
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
        modal.hide();
    }

    function sendStatusUpdate(appointmentId, status, note = null) {
        fetch('update_appointment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                status: status,
                notes: note
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the appointment.');
        });
    }
    </script>
</body>
</html> 