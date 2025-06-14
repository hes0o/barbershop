<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();
$appointments = $db->getConnection()->query("SELECT a.id, u.username AS customer, b.id AS barber_id, s.name AS service, a.appointment_date, a.appointment_time, a.status FROM appointments a JOIN users u ON a.user_id = u.id JOIN barbers b ON a.barber_id = b.id JOIN services s ON a.service_id = s.id ORDER BY a.appointment_date DESC, a.appointment_time DESC");
?>
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Admin - Appointments</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
    <link href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css\" rel=\"stylesheet\">
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .sidebar { min-height: 100vh; background: #22223b; color: #fff; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #4f8cff; color: #fff; }
        .sidebar .nav-link i { margin-right: 8px; }
        .main-content { margin-left: 220px; padding: 2rem; }
    </style>
</head>
<body>
    <div class=\"d-flex\">
        <!-- Sidebar -->
        <nav class=\"sidebar d-flex flex-column p-3\" style=\"width:220px;\">
            <h3 class=\"mb-4\"><i class=\"fas fa-crown\"></i> Admin</h3>
            <ul class=\"nav nav-pills flex-column mb-auto\">
                <li><a href=\"dashboard.php\" class=\"nav-link\"><i class=\"fas fa-chart-line\"></i> Dashboard</a></li>
                <li><a href=\"users.php\" class=\"nav-link\"><i class=\"fas fa-users\"></i> Users</a></li>
                <li class=\"nav-item\"><a href=\"appointments.php\" class=\"nav-link active\"><i class=\"fas fa-calendar-alt\"></i> Appointments</a></li>
                <li><a href=\"services.php\" class=\"nav-link\"><i class=\"fas fa-scissors\"></i> Services</a></li>
            </ul>
            <hr>
            <a href=\"../logout.php\" class=\"btn btn-danger w-100\"><i class=\"fas fa-sign-out-alt\"></i> Logout</a>
        </nav>
        <!-- Main Content -->
        <div class=\"main-content flex-grow-1\">
            <h2 class=\"mb-4\">Appointments Management</h2>
            <div class=\"table-responsive\">
                <table class=\"table table-striped\">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Barber ID</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['customer']); ?></td>
                            <td><?php echo htmlspecialchars($row['barber_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['service']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 