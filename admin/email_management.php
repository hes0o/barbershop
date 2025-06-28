<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/email_service.php';

$auth = new Auth();
$auth->requireRole(['admin']);

$db = new Database();
$emailService = new EmailService();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'test_barber_notification':
                $appointment_id = $_POST['appointment_id'] ?? '';
                if ($appointment_id) {
                    $result = $emailService->sendBarberNotification($appointment_id);
                    if ($result) {
                        $message = 'Barber notification email sent successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to send barber notification email.';
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'test_customer_confirmation':
                $appointment_id = $_POST['appointment_id'] ?? '';
                if ($appointment_id) {
                    $result = $emailService->sendCustomerConfirmation($appointment_id);
                    if ($result) {
                        $message = 'Customer confirmation email sent successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to send customer confirmation email.';
                        $messageType = 'error';
                    }
                }
                break;
        }
    }
}

// Get data for the interface
$barbers = $db->getAllBarbers();
$pending_appointments = [];
$confirmed_appointments = [];

if (!empty($barbers)) {
    $barber_id = $barbers[0]['id']; // Use the first available barber
    $pending_appointments = $db->getBarberAppointments($barber_id, 'pending');
    $confirmed_appointments = $db->getBarberAppointments($barber_id, 'confirmed');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Management - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="appointments.php">
                                <i class="fas fa-calendar"></i> Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="services.php">
                                <i class="fas fa-cut"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="email_config.php">
                                <i class="fas fa-envelope"></i> Email Config
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="email_management.php">
                                <i class="fas fa-envelope-open"></i> Email Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="activity_log.php">
                                <i class="fas fa-history"></i> Activity Log
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Email Management</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Email Templates Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Email Templates</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-bell fa-2x text-warning mb-2"></i>
                                                <h6>Barber Notification</h6>
                                                <p class="text-muted small">Sent to barber when customer books appointment</p>
                                                <a href="../preview_template.php?template=barber_notification" class="btn btn-sm btn-outline-primary" target="_blank">Preview</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                                <h6>Customer Confirmation</h6>
                                                <p class="text-muted small">Sent to customer when barber approves appointment</p>
                                                <a href="../preview_template.php?template=appointment_confirmation" class="btn btn-sm btn-outline-primary" target="_blank">Preview</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Testing Section -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Test Barber Notification</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="test_barber_notification">
                                    <div class="mb-3">
                                        <label class="form-label">Select Pending Appointment:</label>
                                        <select name="appointment_id" class="form-select" required>
                                            <option value="">Choose an appointment...</option>
                                            <?php foreach ($pending_appointments as $apt): ?>
                                                <option value="<?php echo $apt['id']; ?>">
                                                    <?php echo $apt['customer_name']; ?> - 
                                                    <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?> 
                                                    (<?php echo $apt['service_name']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-warning" <?php echo empty($pending_appointments) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-bell"></i> Send Test Notification
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Test Customer Confirmation</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="test_customer_confirmation">
                                    <div class="mb-3">
                                        <label class="form-label">Select Confirmed Appointment:</label>
                                        <select name="appointment_id" class="form-select" required>
                                            <option value="">Choose an appointment...</option>
                                            <?php foreach ($confirmed_appointments as $apt): ?>
                                                <option value="<?php echo $apt['id']; ?>">
                                                    <?php echo $apt['customer_name']; ?> - 
                                                    <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?> 
                                                    (<?php echo $apt['service_name']; ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success" <?php echo empty($confirmed_appointments) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-calendar-check"></i> Send Test Confirmation
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email System Information -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Email System Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> How the Email System Works</h6>
                                    <p class="mb-2">The email system is designed to facilitate communication between customers and barbers:</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Email Flow:</h6>
                                        <ol>
                                            <li><strong>Customer books appointment</strong> → Barber receives notification email</li>
                                            <li><strong>Barber approves appointment</strong> → Customer receives confirmation email</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Email Recipients:</h6>
                                        <ul>
                                            <li><strong>Barber Notification:</strong> Sent to barber when appointment is requested</li>
                                            <li><strong>Customer Confirmation:</strong> Sent to customer when appointment is approved</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 