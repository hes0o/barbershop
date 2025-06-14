<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Only allow admin users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();

// Quick stats
$total_users = count($db->getAllCustomers());
$total_barbers = count($db->getAllBarbers());
$total_appointments = $db->getConnection()->query('SELECT COUNT(*) FROM appointments')->fetch_row()[0];
$total_services = count($db->getAllServices());

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .sidebar { min-height: 100vh; background: #22223b; color: #fff; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #4f8cff; color: #fff; }
        .sidebar .nav-link i { margin-right: 8px; }
        .main-content { margin-left: 220px; padding: 2rem; }
        .stat-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 1.5rem; text-align: center; }
        .stat-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .stat-value { font-size: 2rem; font-weight: bold; }
        .stat-label { color: #6c757d; font-size: 1rem; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column p-3" style="width:220px;">
            <h3 class="mb-4"><i class="fas fa-crown"></i> Admin</h3>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="services.php" class="nav-link"><i class="fas fa-scissors"></i> Services</a></li>
            </ul>
            <hr>
            <a href="../logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <h2 class="mb-4">Dashboard Overview</h2>
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Customers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-success"><i class="fas fa-user-tie"></i></div>
                        <div class="stat-value"><?php echo $total_barbers; ?></div>
                        <div class="stat-label">Barbers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-info"><i class="fas fa-calendar-alt"></i></div>
                        <div class="stat-value"><?php echo $total_appointments; ?></div>
                        <div class="stat-label">Appointments</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon text-warning"><i class="fas fa-scissors"></i></div>
                        <div class="stat-value"><?php echo $total_services; ?></div>
                        <div class="stat-label">Services</div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info">Welcome to the admin dashboard! Use the sidebar to manage users, barbers, appointments, and services.</div>
        </div>
    </div>
</body>
</html> 