<?php
// test_qr_system.php - QR code system test using Google Chart API
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

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
    <title>QR System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .qr-img { border: 1px solid #ddd; padding: 8px; background: #fff; border-radius: 8px; }
        #reader { width: 100%; max-width: 600px; margin: 0 auto; }
        .scan-result { margin-top: 20px; }
    </style>
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">QR System Test</h1>
    
    <!-- QR Code Generation -->
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Generate Appointment QR Code</h4>
                    <form method="POST" class="mb-3">
                        <div class="mb-3">
                            <label class="form-label">Appointment ID</label>
                            <input type="text" name="appointment_id" class="form-control" placeholder="Enter appointment ID">
                        </div>
                        <button type="submit" class="btn btn-primary">Generate QR Code</button>
                    </form>
                    <?php if ($qr_url): ?>
                    <div class="mt-3">
                        <h5>Generated QR Code</h5>
                        <img src="<?php echo $qr_url; ?>" alt="Appointment QR Code" class="qr-img">
                        <div class="mt-2"><code><?php echo htmlspecialchars($qr_url); ?></code></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- QR Code Scanner -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4>Scan QR Code</h4>
                    <div id="reader"></div>
                    <div id="scan-result" class="scan-result"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <b>How it works:</b><br>
        1. Enter an appointment ID to generate a QR code<br>
        2. The QR code contains user ID, appointment ID, date, and time<br>
        3. Use the scanner to scan the QR code<br>
        4. The system will automatically mark the appointment as completed
    </div>
</div>

<script>
// Initialize QR Code Scanner
const html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", { fps: 10, qrbox: 250 });

html5QrcodeScanner.render((decodedText) => {
    // Handle the scanned code
    fetch('test_qr_system.php', {
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
</script>
</body>
</html> 