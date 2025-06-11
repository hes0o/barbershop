<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit();
}

$db = new Database();
$user_id = $_SESSION['user_id'];

// Get user's appointments
$appointments = $db->getAppointmentsByUser($user_id);

// Separate current and past appointments
$current_appointments = [];
$past_appointments = [];
$today = date('Y-m-d');

foreach ($appointments as $appointment) {
    if ($appointment['appointment_date'] >= $today) {
        $current_appointments[] = $appointment;
    } else {
        $past_appointments[] = $appointment;
    }
}

// Sort appointments
usort($current_appointments, function($a, $b) {
    return strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) - 
           strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
});

usort($past_appointments, function($a, $b) {
    return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) - 
           strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
});

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">My Appointments</h2>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="appointmentTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active text-dark" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button" role="tab">
                                Current Appointments
                                <span class="badge bg-primary ms-2"><?php echo count($current_appointments); ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-dark" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                                Appointment History
                                <span class="badge bg-secondary ms-2"><?php echo count($past_appointments); ?></span>
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="appointmentTabsContent">
                        <!-- Current Appointments Tab -->
                        <div class="tab-pane fade show active" id="current" role="tabpanel">
                            <?php if (empty($current_appointments)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                    <p class="lead">You have no upcoming appointments.</p>
                                    <a href="book.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-plus me-2"></i>Book an Appointment
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Service</th>
                                                <th>Barber</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($current_appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                    <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($appointment['service_name']); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            Duration: <?php echo $appointment['duration']; ?> mins
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['barber_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getStatusColor($appointment['status']); ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Appointment History Tab -->
                        <div class="tab-pane fade" id="history" role="tabpanel">
                            <?php if (empty($past_appointments)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                    <p class="lead">No past appointments found.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Service</th>
                                                <th>Barber</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($past_appointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                                    <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($appointment['service_name']); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            Duration: <?php echo $appointment['duration']; ?> mins
                                                        </small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($appointment['barber_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getStatusColor($appointment['status']); ?>">
                                                            <?php echo ucfirst($appointment['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.nav-tabs .badge {
    font-size: 0.8em;
    padding: 0.25em 0.6em;
}

.table th {
    white-space: nowrap;
}

.table td {
    vertical-align: middle;
}

.nav-tabs .nav-link {
    color: #333 !important;
    font-weight: 500;
}

.nav-tabs .nav-link.active {
    color: #0d6efd !important;
    font-weight: 600;
}

.btn-primary {
    color: #fff !important;
}

.btn-primary:hover {
    color: #fff !important;
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}
</style>

<?php include 'includes/footer.php'; ?> 