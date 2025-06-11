<?php
session_start();
require_once '../config.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Handle service updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_service':
                $service_id = $_POST['service_id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $duration = $_POST['duration'];
                
                $stmt = $db->conn->prepare("UPDATE services SET name = ?, description = ?, price = ?, duration = ? WHERE id = ?");
                $stmt->bind_param("ssdii", $name, $description, $price, $duration, $service_id);
                
                if ($stmt->execute()) {
                    $message = "Service updated successfully!";
                } else {
                    $error = "Failed to update service.";
                }
                break;
                
            case 'delete_service':
                $service_id = $_POST['service_id'];
                
                $stmt = $db->conn->prepare("DELETE FROM services WHERE id = ?");
                $stmt->bind_param("i", $service_id);
                
                if ($stmt->execute()) {
                    $message = "Service deleted successfully!";
                } else {
                    $error = "Failed to delete service.";
                }
                break;
                
            case 'add_service':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $duration = $_POST['duration'];
                
                $stmt = $db->conn->prepare("INSERT INTO services (name, description, price, duration) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssdi", $name, $description, $price, $duration);
                
                if ($stmt->execute()) {
                    $message = "Service added successfully!";
                } else {
                    $error = "Failed to add service.";
                }
                break;
        }
    }
}

// Get all services
$services = $db->getAllServices();
?>

<?php include '../includes/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Admin Menu</h5>
                    <div class="list-group">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-concierge-bell me-2"></i>Services
                        </a>
                        <a href="appointments.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt me-2"></i>Appointments
                        </a>
                        <a href="barbers.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-tie me-2"></i>Barbers
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog me-2"></i>Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="card-title mb-0">Manage Services</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                            <i class="fas fa-plus me-2"></i>Add New Service
                        </button>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['description']); ?></td>
                                        <td>$<?php echo number_format($service['price'], 2); ?></td>
                                        <td><?php echo $service['duration']; ?> min</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editServiceModal<?php echo $service['id']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteServiceModal<?php echo $service['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Service Modal -->
                                    <div class="modal fade" id="editServiceModal<?php echo $service['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="update_service">
                                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Service</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="name" 
                                                                   value="<?php echo htmlspecialchars($service['name']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Description</label>
                                                            <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Price ($)</label>
                                                            <input type="number" class="form-control" name="price" 
                                                                   value="<?php echo $service['price']; ?>" step="0.01" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Duration (minutes)</label>
                                                            <input type="number" class="form-control" name="duration" 
                                                                   value="<?php echo $service['duration']; ?>" required>
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
                                    <div class="modal fade" id="deleteServiceModal<?php echo $service['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="delete_service">
                                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                                    
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Delete Service</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    
                                                    <div class="modal-body">
                                                        <p>Are you sure you want to delete this service?</p>
                                                        <p><strong><?php echo htmlspecialchars($service['name']); ?></strong></p>
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add_service">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price ($)</label>
                        <input type="number" class="form-control" name="price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Duration (minutes)</label>
                        <input type="number" class="form-control" name="duration" required>
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

<?php include '../includes/footer.php'; ?> 