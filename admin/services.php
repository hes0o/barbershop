<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Handle service actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration'])) {
                    $result = $db->addService($_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration']);
                    if ($result['success']) {
                        $message = 'Service added successfully!';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
            case 'edit':
                if (isset($_POST['id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration'])) {
                    $result = $db->updateService($_POST['id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['duration']);
                    if ($result['success']) {
                        $message = 'Service updated successfully!';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
            case 'delete':
                if (isset($_POST['id'])) {
                    $result = $db->deleteService($_POST['id']);
                    if ($result['success']) {
                        $message = 'Service deleted successfully!';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}

$services = $db->getAllServices();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column p-3" style="width:220px;">
            <h3 class="mb-4"><i class="fas fa-crown"></i> Admin</h3>
            <ul class="nav nav-pills flex-column mb-auto">
                <li><a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li class="nav-item"><a href="services.php" class="nav-link active"><i class="fas fa-scissors"></i> Services</a></li>
                <li><a href="activity_log.php" class="nav-link"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
            <hr>
            <a href="../logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <h2 class="mb-4">Services Management</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="mb-4">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Add New Service
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Duration (min)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $service): ?>
                        <tr>
                            <td><?php echo $service['id']; ?></td>
                            <td><?php echo htmlspecialchars($service['name']); ?></td>
                            <td><?php echo htmlspecialchars($service['description']); ?></td>
                            <td>$<?php echo number_format($service['price'], 2); ?></td>
                            <td><?php echo $service['duration']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-service" 
                                        data-id="<?php echo $service['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($service['name']); ?>"
                                        data-description="<?php echo htmlspecialchars($service['description']); ?>"
                                        data-price="<?php echo $service['price']; ?>"
                                        data-duration="<?php echo $service['duration']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger delete-service" data-id="<?php echo $service['id']; ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="duration" name="duration" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <div class="modal fade" id="editServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_price" class="form-label">Price ($)</label>
                            <input type="number" class="form-control" id="edit_price" name="price" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_duration" class="form-label">Duration (minutes)</label>
                            <input type="number" class="form-control" id="edit_duration" name="duration" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Service Modal -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this service? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Edit service
            $('.edit-service').click(function() {
                var id = $(this).data('id');
                var name = $(this).data('name');
                var description = $(this).data('description');
                var price = $(this).data('price');
                var duration = $(this).data('duration');

                $('#edit_id').val(id);
                $('#edit_name').val(name);
                $('#edit_description').val(description);
                $('#edit_price').val(price);
                $('#edit_duration').val(duration);

                $('#editServiceModal').modal('show');
            });

            // Delete service
            $('.delete-service').click(function() {
                var id = $(this).data('id');
                $('#delete_id').val(id);
                $('#deleteServiceModal').modal('show');
            });
        });
    </script>
</body>
</html> 