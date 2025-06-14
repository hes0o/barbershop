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

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role'])) {
                    $result = $db->addUser($_POST['username'], $_POST['email'], $_POST['password'], $_POST['role']);
                    if ($result['success']) {
                        $message = 'User added successfully!';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
            case 'edit':
                if (isset($_POST['id'], $_POST['username'], $_POST['email'], $_POST['role'])) {
                    $result = $db->updateUser($_POST['id'], $_POST['username'], $_POST['email'], $_POST['role']);
                    if ($result['success']) {
                        $message = 'User updated successfully!';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
            case 'delete':
                if (isset($_POST['id'])) {
                    $result = $db->deleteUser($_POST['id']);
                    if ($result['success']) {
                        $message = 'User deleted successfully!';
                    } else {
                        $error = $result['message'];
                    }
                }
                break;
        }
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Build query with filters
$query = "SELECT u.*, 
          COUNT(DISTINCT a.id) as total_appointments,
          MAX(a.appointment_date) as last_activity
          FROM users u 
          LEFT JOIN appointments a ON u.id = a.user_id";

$where_conditions = [];
$params = [];
$types = '';

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if ($role_filter) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " GROUP BY u.id";

// Add sorting
$allowed_sort_columns = ['id', 'username', 'email', 'role', 'created_at', 'total_appointments', 'last_activity'];
$sort_by = in_array($sort_by, $allowed_sort_columns) ? $sort_by : 'id';
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
$query .= " ORDER BY $sort_by $sort_order";

// Prepare and execute query
$stmt = $db->getConnection()->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
// Manual fetch instead of get_result()
$stmt->store_result();
$stmt->bind_result($id, $first_name, $last_name, $email, $password, $role, $phone, $created_at, $total_appointments, $last_activity);
$users = [];
while ($stmt->fetch()) {
    $users[] = [
        'id' => $id,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'password' => $password,
        'role' => $role,
        'phone' => $phone,
        'created_at' => $created_at,
        'total_appointments' => $total_appointments,
        'last_activity' => $last_activity
    ];
}
$stmt->close();

// Get unique roles for filter
$roles = [];
$role_stmt = $db->getConnection()->prepare("SELECT DISTINCT role FROM users ORDER BY role");
$role_stmt->execute();
$role_stmt->bind_result($role_val);
while ($role_stmt->fetch()) {
    $roles[] = ['role' => $role_val];
}
$role_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .sidebar { min-height: 100vh; background: #22223b; color: #fff; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #4f8cff; color: #fff; }
        .sidebar .nav-link i { margin-right: 8px; }
        .main-content { margin-left: 220px; padding: 2rem; }
        .filter-section { background: #fff; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .action-buttons .btn { margin-right: 0.25rem; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #e9ecef; display: flex; align-items: center; justify-content: center; }
        .user-avatar i { font-size: 1.2rem; color: #6c757d; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar d-flex flex-column p-3" style="width:220px;">
            <h3 class="mb-4"><i class="fas fa-crown"></i> Admin</h3>
            <ul class="nav nav-pills flex-column mb-auto">
                <li><a href="dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="nav-item"><a href="users.php" class="nav-link active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-alt"></i> Appointments</a></li>
                <li><a href="services.php" class="nav-link"><i class="fas fa-scissors"></i> Services</a></li>
                <li><a href="activity_log.php" class="nav-link"><i class="fas fa-history"></i> Activity Log</a></li>
            </ul>
            <hr>
            <a href="../logout.php" class="btn btn-danger w-100"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Main Content -->
        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Users Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add New User
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="role">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['role']; ?>" <?php echo $role_filter === $role['role'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($role['role']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="sort">
                            <option value="id" <?php echo $sort_by === 'id' ? 'selected' : ''; ?>>Sort by ID</option>
                            <option value="username" <?php echo $sort_by === 'username' ? 'selected' : ''; ?>>Sort by Username</option>
                            <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Sort by Email</option>
                            <option value="role" <?php echo $sort_by === 'role' ? 'selected' : ''; ?>>Sort by Role</option>
                            <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Sort by Created Date</option>
                            <option value="total_appointments" <?php echo $sort_by === 'total_appointments' ? 'selected' : ''; ?>>Sort by Appointments</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-striped" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Appointments</th>
                            <th>Last Activity</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'barber' ? 'success' : 'primary'); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['total_appointments']; ?></td>
                                <td><?php echo $user['last_activity'] ? date('M j, Y', strtotime($user['last_activity'])) : 'Never'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-secondary" onclick="showUserInfo(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="impersonateUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-user-secret"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="customer">Customer</option>
                                <option value="barber">Barber</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="editRole" required>
                                <option value="customer">Customer</option>
                                <option value="barber">Barber</option>
                                <option value="admin">Admin</option>
                            </select>
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

    <!-- User Info Modal -->
    <div class="modal fade" id="userInfoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group">
                        <li class="list-group-item"><strong>ID:</strong> <span id="infoId"></span></li>
                        <li class="list-group-item"><strong>First Name:</strong> <span id="infoFirstName"></span></li>
                        <li class="list-group-item"><strong>Last Name:</strong> <span id="infoLastName"></span></li>
                        <li class="list-group-item"><strong>Email:</strong> <span id="infoEmail"></span></li>
                        <li class="list-group-item"><strong>Role:</strong> <span id="infoRole"></span></li>
                        <li class="list-group-item"><strong>Phone:</strong> <span id="infoPhone"></span></li>
                        <li class="list-group-item"><strong>Created At:</strong> <span id="infoCreated"></span></li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#usersTable').DataTable({
                pageLength: 25,
                order: [[0, 'desc']],
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
            });
        });

        function editUser(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editUsername').value = user.username;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editRole').value = user.role;
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function impersonateUser(userId) {
            if (confirm('Are you sure you want to impersonate this user? You will be logged in as them.')) {
                window.location.href = `impersonate.php?id=${userId}`;
            }
        }

        function showUserInfo(user) {
            document.getElementById('infoId').textContent = user.id;
            document.getElementById('infoFirstName').textContent = user.first_name;
            document.getElementById('infoLastName').textContent = user.last_name;
            document.getElementById('infoEmail').textContent = user.email;
            document.getElementById('infoRole').textContent = user.role;
            document.getElementById('infoPhone').textContent = user.phone || '-';
            document.getElementById('infoCreated').textContent = user.created_at ? new Date(user.created_at).toLocaleString() : '-';
            new bootstrap.Modal(document.getElementById('userInfoModal')).show();
        }
    </script>
</body>
</html> 