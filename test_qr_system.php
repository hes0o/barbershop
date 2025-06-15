<?php
// test_qr_system.php - QR code system test using Google Chart API

class QRSystem {
    // Generate a Google Chart API QR code URL for given data
    public static function generateQRCodeUrl($data, $size = 200) {
        $encoded = urlencode($data);
        return "https://chart.googleapis.com/chart?chs={$size}x{$size}&cht=qr&chl={$encoded}&chld=L|1";
    }

    // Generate QR code for a user (by user ID or email)
    public static function userQRCode($user_id, $email = null) {
        $payload = $email ? "USER:$user_id|EMAIL:$email" : "USER:$user_id";
        return self::generateQRCodeUrl($payload);
    }

    // Generate QR code for an appointment (by appointment ID)
    public static function appointmentQRCode($appointment_id) {
        $payload = "APPOINTMENT:$appointment_id";
        return self::generateQRCodeUrl($payload);
    }
}

// Handle form submission
$user_qr_url = $appointment_qr_url = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id']) && $_POST['user_id'] !== '') {
        $user_id = trim($_POST['user_id']);
        $email = isset($_POST['user_email']) ? trim($_POST['user_email']) : null;
        $user_qr_url = QRSystem::userQRCode($user_id, $email);
    }
    if (isset($_POST['appointment_id']) && $_POST['appointment_id'] !== '') {
        $appointment_id = trim($_POST['appointment_id']);
        $appointment_qr_url = QRSystem::appointmentQRCode($appointment_id);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QR System Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .qr-img { border: 1px solid #ddd; padding: 8px; background: #fff; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">QR System Test</h1>
    <form method="POST" class="row g-4 mb-5">
        <div class="col-md-6">
            <h4>Generate User QR Code</h4>
            <div class="mb-3">
                <label class="form-label">User ID</label>
                <input type="text" name="user_id" class="form-control" placeholder="Enter user ID">
            </div>
            <div class="mb-3">
                <label class="form-label">User Email (optional)</label>
                <input type="email" name="user_email" class="form-control" placeholder="Enter user email">
            </div>
        </div>
        <div class="col-md-6">
            <h4>Generate Appointment QR Code</h4>
            <div class="mb-3">
                <label class="form-label">Appointment ID</label>
                <input type="text" name="appointment_id" class="form-control" placeholder="Enter appointment ID">
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Generate QR Codes</button>
        </div>
    </form>
    <div class="row g-4">
        <?php if ($user_qr_url): ?>
        <div class="col-md-6">
            <h5>User QR Code</h5>
            <img src="<?php echo $user_qr_url; ?>" alt="User QR Code" class="qr-img">
            <div class="mt-2"><code><?php echo htmlspecialchars($user_qr_url); ?></code></div>
        </div>
        <?php endif; ?>
        <?php if ($appointment_qr_url): ?>
        <div class="col-md-6">
            <h5>Appointment QR Code</h5>
            <img src="<?php echo $appointment_qr_url; ?>" alt="Appointment QR Code" class="qr-img">
            <div class="mt-2"><code><?php echo htmlspecialchars($appointment_qr_url); ?></code></div>
        </div>
        <?php endif; ?>
    </div>
    <hr class="my-5">
    <div class="alert alert-info">
        <b>How it works:</b> Enter a user ID (and optionally email) or an appointment ID, then click "Generate QR Codes". The QR code encodes the relevant data and can be scanned by any QR code reader. This is a test system—if you like the results, we can integrate it into the real application and add scanning/check-in features!
    </div>
</div>
</body>
</html> 