<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Initialize session
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$db = new Database();

// Handle filters
$filters = [];
if (isset($_GET['user_id'])) $filters['user_id'] = $_GET['user_id'];
if (isset($_GET['action'])) $filters['action'] = $_GET['action'];
if (isset($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
if (isset($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
if (isset($_GET['limit'])) $filters['limit'] = $_GET['limit'];

// Get activity logs
$logs = $db->getActivityLog($filters);

// Get unique actions for filter dropdown
$actions = array_unique(array_column($logs, 'action'));

// Get all users for filter dropdown
$users = $db->getAllUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .sidebar a.active {
            background-color: #007bff;
        }
        .main-content {
            padding: 20px;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h3 class="text-white text-center mb-4">Admin Panel</h3>
                <a href="dashboard.php">Dashboard</a>
                <a href="users.php">Users</a>
                <a href="appointments.php">Appointments</a>
                <a href="services.php">Services</a>
                <a href="activity_log.php" class="active">Activity Log</a>
                <a href="../logout.php">Logout</a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 class="mb-4">Activity Log</h2>

                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="user_id" class="form-label">User</label>
                            <select name="user_id" id="user_id" class="form-select">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo isset($_GET['user_id']) && $_GET['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="action" class="form-label">Action</label>
                            <select name="action" id="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $action): ?>
                                    <option value="<?php echo htmlspecialchars($action); ?>" <?php echo isset($_GET['action']) && $_GET['action'] == $action ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($action); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="limit" class="form-label">Limit</label>
                            <select name="limit" id="limit" class="form-select">
                                <option value="50" <?php echo (!isset($_GET['limit']) || $_GET['limit'] == 50) ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo isset($_GET['limit']) && $_GET['limit'] == 100 ? 'selected' : ''; ?>>100</option>
                                <option value="200" <?php echo isset($_GET['limit']) && $_GET['limit'] == 200 ? 'selected' : ''; ?>>200</option>
                                <option value="500" <?php echo isset($_GET['limit']) && $_GET['limit'] == 500 ? 'selected' : ''; ?>>500</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="activity_log.php" class="btn btn-secondary">Clear Filters</a>
                        </div>
                    </form>
                </div>

                <!-- Activity Log Table -->
                <div class="table-responsive">
                    <table id="activityTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables@1.10.18/media/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#activityTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25
            });

            // Initialize date pickers
            flatpickr("input[type=date]", {
                dateFormat: "Y-m-d"
            });
        });
    </script>
</body>
</html> 