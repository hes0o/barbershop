<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a barber
requireLogin();
if ($_SESSION['role'] !== 'barber') {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$barber_id = $db->getBarberIdByUserId($_SESSION['user_id']);

// Get search parameter
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get customers with their appointment history
$customers = $db->getBarberCustomers($barber_id, $search);

require_once '../includes/header.php';
?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>My Customers</h2>
        </div>
        <div class="col-md-4">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" 
                       placeholder="Search customers..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <?php if (empty($customers)): ?>
        <div class="alert alert-info">
            No customers found.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($customers as $customer): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($customer['username']); ?></h5>
                            <p class="card-text">
                                <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($customer['email']); ?><br>
                                <i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?>
                            </p>
                            <div class="mt-3">
                                <h6>Appointment History</h6>
                                <ul class="list-unstyled">
                                    <li>
                                        <small class="text-muted">
                                            Total Appointments: <?php echo $customer['total_appointments']; ?>
                                        </small>
                                    </li>
                                    <li>
                                        <small class="text-muted">
                                            Last Visit: <?php echo $customer['last_visit'] ? date('M d, Y', strtotime($customer['last_visit'])) : 'Never'; ?>
                                        </small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-sm btn-primary" 
                                    onclick="viewCustomerHistory(<?php echo $customer['id']; ?>)">
                                View History
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Customer History Modal -->
<div class="modal fade" id="customerHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Customer History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="customerHistoryContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewCustomerHistory(customerId) {
    const modal = new bootstrap.Modal(document.getElementById('customerHistoryModal'));
    const content = document.getElementById('customerHistoryContent');
    
    // Show loading state
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch customer history
    fetch(`get_customer_history.php?customer_id=${customerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                content.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                return;
            }
            
            if (data.length === 0) {
                content.innerHTML = '<p class="text-center">No appointment history found.</p>';
                return;
            }
            
            // Create history table
            content.innerHTML = `
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Service</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.map(appointment => `
                                <tr>
                                    <td>${new Date(appointment.appointment_date).toLocaleDateString()}</td>
                                    <td>${appointment.appointment_time}</td>
                                    <td>${appointment.service_name}</td>
                                    <td>
                                        <span class="badge bg-${getStatusColor(appointment.status)}">
                                            ${appointment.status}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        })
        .catch(error => {
            console.error('Error:', error);
            content.innerHTML = '<div class="alert alert-danger">Error loading customer history.</div>';
        });
}

function getStatusColor(status) {
    switch (status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}
</script>

<?php include '../includes/footer.php'; ?> 