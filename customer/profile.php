<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = new Database();
$message = '';
$error = '';

// Get customer info
$customer = $db->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        // Validate input
        if (empty($username) || empty($email)) {
            $error = 'Username and email are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
        } else {
            // Check if username or email is already taken by another user
            $existing_user = $db->getUserByUsername($username);
            if ($existing_user && $existing_user['id'] !== $_SESSION['user_id']) {
                $error = 'Username already taken';
            } else {
                $existing_email = $db->getUserByEmail($email);
                if ($existing_email && $existing_email['id'] !== $_SESSION['user_id']) {
                    $error = 'Email already taken';
                } else {
                    // Update user info
                    $stmt = $db->getConnection()->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, phone = ? 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("sssi", $username, $email, $phone, $_SESSION['user_id']);
                    
                    if ($stmt->execute()) {
                        $message = 'Profile updated successfully';
                        $customer = $db->getUserById($_SESSION['user_id']); // Refresh customer data
                    } else {
                        $error = 'Failed to update profile';
                    }
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate input
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long';
        } else {
            // Verify current password
            if (password_verify($current_password, $customer['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                if ($db->updateUserPassword($_SESSION['user_id'], $hashed_password)) {
                    $message = 'Password changed successfully';
                } else {
                    $error = 'Failed to change password';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Barbershop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/css/style.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-floating {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <a href="<?php echo dirname($_SERVER['SCRIPT_NAME']) === '/customer' ? '../logout.php' : BASE_URL . '/logout.php'; ?>" class="btn btn-outline-danger position-fixed top-0 end-0 m-3">
        <i class="fas fa-sign-out-alt"></i> Sign Out
    </a>

    <div class="container profile-container">
        <h2 class="text-center mb-4">My Profile</h2>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="profile-section">
            <h3 class="mb-4">Profile Information</h3>
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required 
                           value="<?php echo htmlspecialchars($customer['username']); ?>">
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Email" required
                           value="<?php echo htmlspecialchars($customer['email']); ?>">
                    <label for="email">Email address</label>
                </div>

                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           placeholder="Phone Number"
                           value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                    <label for="phone">Phone Number (optional)</label>
                </div>

                <button type="submit" name="update_profile" class="btn btn-primary">
                    Update Profile
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="profile-section">
            <h3 class="mb-4">Change Password</h3>
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input type="password" class="form-control" id="current_password" 
                           name="current_password" placeholder="Current Password" required>
                    <label for="current_password">Current Password</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="new_password" 
                           name="new_password" placeholder="New Password" required minlength="6">
                    <label for="new_password">New Password</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" placeholder="Confirm New Password" required>
                    <label for="confirm_password">Confirm New Password</label>
                </div>

                <button type="submit" name="change_password" class="btn btn-primary">
                    Change Password
                </button>
            </form>
        </div>

        <div class="text-center mt-3">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Form validation
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()
    </script>
</body>
</html> 