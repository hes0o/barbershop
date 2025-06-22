<?php
// admin/scan_qr.php - Copied from test_qr_system.php for production use
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

class QRSystem {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // Generate a QR code URL for given data using QRServer API
    public static function generateQRCodeUrl($data, $size = 200) {
        $encoded = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?data={$encoded}&size={$size}x{$size}";
    }

    // Generate QR code for an appointment with user info
    public function generateAppointmentQR($appointment_id) {
        // Get appointment details
        $appointment = $this->db->getAppointmentById($appointment_id);
        if (!$appointment) {
            return false;
        }

        // Format: USER_ID:APPOINTMENT_ID:DATE:TIME
        $payload = sprintf(
            "USER:%d|APT:%d|DATE:%s|TIME:%s",
            $appointment['user_id'],
            $appointment['id'],
            $appointment['appointment_date'],
            $appointment['appointment_time']
        );
        
        return self::generateQRCodeUrl($payload);
    }

    // Process scanned QR code and update appointment status
    public function processScannedQR($qr_data) {
        // Parse QR data
        $parts = explode('|', $qr_data);
        $data = [];
        foreach ($parts as $part) {
            list($key, $value) = explode(':', $part);
            $data[$key] = $value;
        }

        if (!isset($data['USER']) || !isset($data['APT'])) {
            return ['success' => false, 'message' => 'Invalid QR code format'];
        }

        // Update appointment status
        $appointment_id = $data['APT'];
        $user_id = $data['USER'];

        // Verify appointment exists and belongs to user
        $appointment = $this->db->getAppointmentById($appointment_id);
        if (!$appointment || $appointment['user_id'] != $user_id) {
            return ['success' => false, 'message' => 'Invalid appointment'];
        }

        // Update status to completed
        $success = $this->db->updateAppointmentStatus($appointment_id, 'completed');
        
        return [
            'success' => $success,
            'message' => $success ? 'Appointment marked as completed' : 'Failed to update appointment',
            'appointment' => $appointment
        ];
    }
}

// Handle form submission for QR generation
$qr_url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $qr_system = new QRSystem();
    $qr_url = $qr_system->generateAppointmentQR($_POST['appointment_id']);
}

// Handle QR code scanning (AJAX endpoint)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_qr'])) {
    header('Content-Type: application/json');
    $qr_system = new QRSystem();
    $result = $qr_system->processScannedQR($_POST['qr_data']);
    echo json_encode($result);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR System - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .sidebar { min-height: 100vh; background: #22223b; color: #fff; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #4f8cff; color: #fff; }
        .sidebar .nav-link i { margin-right: 8px; }
        .main-content { margin-left: 220px; padding: 2rem; }
        .qr-img { border: 1px solid #ddd; padding: 8px; background: #fff; border-radius: 8px; }
        #reader { width: 100%; max-width: 400px; margin: 0 auto; }
        .scan-result { margin-top: 20px; }
        .card { box-shadow: 0 2px 8px rgba(0,0,0,0.07); border-radius: 16px; }
        .card-body { padding: 2rem; }
        .form-label { font-weight: 500; }
        .btn { font-size: 1.1rem; padding: 0.75rem 1.5rem; }
        @media (max-width: 991.98px) {
            .sidebar { position: fixed; left: -220px; top: 0; width: 220px; z-index: 1040; transition: left 0.3s; }
            .sidebar.show { left: 0; }
            .main-content { margin-left: 0; padding: 1rem; }
            .sidebar-backdrop { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.3); z-index: 1039; }
            .sidebar-backdrop.show { display: block; }
        }
        @media (max-width: 767.98px) {
            .row.flex-lg-row { flex-direction: column !important; }
            .col-md-6 { width: 100%; max-width: 100%; }
            .card { margin-bottom: 1.5rem; }
            .card-body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Mobile Navbar -->
    <nav class="navbar navbar-dark bg-dark d-lg-none">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <span class="navbar-brand mb-0 h1"><i class="fas fa-crown"></i> Admin</span>
        </div>
    </nav>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column p-3" id="sidebar">
            <h3 class="mb-4 d-none d-lg-block"><i class="fas fa-crown"></i> Admin</h3>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="services.php" class="nav-link"><i class="fas fa-scissors"></i> Services</a></li>
                <li><a href="activity_log.php" class="nav-link"><i class="fas fa-history"></i> Activity Log</a></li>
                <li><a href="scan_qr.php" class="nav-link active"><i class="fas fa-qrcode"></i> Scan QR Code</a></li>
            </ul>
            <hr>
            <a href="../logout.php" class="btn btn-danger w-100 mt-auto"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="container py-4">
                <div class="text-center mb-4">
                    <h1 class="fw-bold mb-2"><i class="fas fa-qrcode text-primary"></i> Scan & Generate QR Code</h1>
                    <p class="lead">Easily generate and scan appointment QR codes for fast check-in and completion.</p>
                </div>
                <div class="row flex-lg-row g-4 mb-4">
                    <!-- QR Code Generation -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="mb-3"><i class="fas fa-plus-circle text-success"></i> Generate Appointment QR Code</h4>
                                <form method="POST" class="mb-3">
                                    <div class="mb-3">
                                        <label class="form-label">Appointment ID</label>
                                        <input type="text" name="appointment_id" class="form-control form-control-lg" placeholder="Enter appointment ID" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-qrcode"></i> Generate QR Code</button>
                                </form>
                                <?php if ($qr_url): ?>
                                <div class="mt-3 text-center">
                                    <h5 class="mb-2">Generated QR Code</h5>
                                    <img src="<?php echo $qr_url; ?>" alt="Appointment QR Code" class="qr-img mb-2" style="max-width: 100%; height: auto;">
                                    <div class="small text-muted"><code><?php echo htmlspecialchars($qr_url); ?></code></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- QR Code Scanner -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h4 class="mb-3"><i class="fas fa-camera text-info"></i> Scan QR Code</h4>
                                <div id="reader"></div>
                                <div id="scan-result" class="scan-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <b>How it works:</b><br>
                    <ol class="mb-0">
                        <li>Enter an appointment ID to generate a QR code.</li>
                        <li>The QR code contains user ID, appointment ID, date, and time.</li>
                        <li>Use the scanner to scan the QR code.</li>
                        <li>The system will automatically mark the appointment as completed.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
    // Sidebar toggle for mobile
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                sidebarBackdrop.classList.toggle('show');
            });
        }
        
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', function() {
                sidebar.classList.remove('show');
                sidebarBackdrop.classList.remove('show');
            });
        }

        // Initialize QR Code Scanner
        const html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", { fps: 10, qrbox: 250 });

        html5QrcodeScanner.render((decodedText) => {
            // Handle the scanned code
            fetch('scan_qr.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `scan_qr=1&qr_data=${encodeURIComponent(decodedText)}`
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('scan-result');
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>Appointment Completed!</h5>
                            <p>User ID: ${data.appointment.user_id}</p>
                            <p>Appointment ID: ${data.appointment.id}</p>
                            <p>Date: ${data.appointment.appointment_date}</p>
                            <p>Time: ${data.appointment.appointment_time}</p>
                        </div>`;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5>Error</h5>
                            <p>${data.message}</p>
                        </div>`;
                }
            })
            .catch(error => {
                document.getElementById('scan-result').innerHTML = `
                    <div class="alert alert-danger">
                        <h5>Error</h5>
                        <p>Failed to process QR code: ${error.message}</p>
                    </div>`;
            });
        });
    });
    </script>
</body>
</html> 