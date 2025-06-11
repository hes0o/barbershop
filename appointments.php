<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in
requireLogin();

$db = new Database();
error_log("Fetching appointments for user_id: " . $_SESSION['user_id']);
$appointments = $db->getAppointmentsByUser($_SESSION['user_id']);
error_log("Number of appointments found: " . count($appointments));

// Sort appointments by date and time (most recent first)
usort($appointments, function($a, $b) {
    return strtotime($b['appointment_date'] . ' ' . $b['appointment_time']) - 
           strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
});

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="card-title mb-0">My Appointments</h2>
                        <a href="book.php" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Book New Appointment
                        </a>
                    </div>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($appointments)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <p class="lead">You have no appointments yet.</p>
                            <a href="book.php" class="btn btn-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Book Your First Appointment
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
                                    <?php foreach ($appointments as $appointment): ?>
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

<style>
.table th {
    white-space: nowrap;
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.table td {
    vertical-align: middle;
    padding: 1rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.05);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
    font-size: 0.85em;
}

.btn-primary {
    color: #fff !important;
    font-weight: 500;
    padding: 0.5rem 1.5rem;
}

.btn-primary:hover {
    color: #fff !important;
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.text-muted {
    font-size: 0.85em;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-title {
    color: #212529;
    font-weight: 600;
}

.alert {
    border: none;
    border-radius: 0.5rem;
}
</style>

<?php include 'includes/footer.php'; ?> 