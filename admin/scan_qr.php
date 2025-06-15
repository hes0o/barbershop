<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Only allow admin users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 220px; padding: 2rem; }
        @media (max-width: 991.98px) { .main-content { margin-left: 0; padding: 1rem; } }
        #reader { width: 100%; max-width: 400px; margin: 0 auto; }
        .scan-result { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column p-3" style="width:220px;min-height:100vh;background:#22223b;color:#fff;">
            <h3 class="mb-4"><i class="fas fa-crown"></i> Admin</h3>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="services.php" class="nav-link"><i class="fas fa-scissors"></i> Services</a></li>
                <li><a href="activity_log.php" class="nav-link"><i class="fas fa-history"></i> Activity Log</a></li>
                <li><a href="scan_qr.php" class="nav-link"><i class="fas fa-qrcode"></i> Scan QR Code</a></li>
            </ul>
            <hr>
            <a href="../logout.php" class="btn btn-danger w-100 mt-auto"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <h2 class="mb-4"><i class="fas fa-qrcode text-primary"></i> Scan QR Code</h2>
            <div class="card mb-4">
                <div class="card-body">
                    <p class="lead">Scan a customer's appointment QR code to mark their appointment as completed.</p>
                    <div class="container py-4">
                        <div class="row">
                            <div class="col-12">
                                <div id="reader"></div>
                                <div id="scan-result" class="scan-result"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
    // Initialize QR Code Scanner with both success and error callbacks
    const html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", { fps: 10, qrbox: 250 });

    html5QrcodeScanner.render(
        function onScanSuccess(decodedText, decodedResult) {
            // Handle the scanned code
            fetch('../test_qr_system.php', {
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
        },
        function onScanError(errorMessage) {
            // Optionally handle scan errors or ignore
        }
    );
    </script>
</body>
</html> 