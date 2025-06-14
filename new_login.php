<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = new Database();
        $user = $db->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            } elseif ($user['role'] === 'barber') {
                header('Location: barber/dashboard.php');
                exit;
            } else {
                header('Location: customer/dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $db = new Database();
        if ($db->getUserByUsername($username)) {
            $error = 'Username already exists.';
        } elseif ($db->getUserByEmail($email)) {
            $error = 'Email already exists.';
        } else {
            // Save as customer, store first/last name in username (or extend DB if needed)
            $full_username = $first_name . ' ' . $last_name;
            if ($db->createUser($username, $email, $password, 'customer', $phone)) {
                $success = 'Registration successful! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
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
    <title>Barbershop Login & Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .auth-wrapper {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
        }
        .auth-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(79,140,255,0.10);
            overflow: hidden;
            display: flex;
            width: 900px;
            max-width: 98vw;
        }
        .auth-side {
            flex: 1 1 0;
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-side.bg {
            background: linear-gradient(135deg, #4f8cff 60%, #6ee7b7 100%);
            color: #fff;
            align-items: center;
            text-align: center;
            justify-content: center;
        }
        .auth-side.bg h2 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }
        .auth-side.bg p {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        .auth-side.bg i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }
        .form-label { font-weight: 500; }
        .form-control { border-radius: 10px; }
        .btn-auth { border-radius: 10px; font-weight: 600; }
        .switch-link { color: #4f8cff; cursor: pointer; text-decoration: underline; }
        .switch-link:hover { color: #2563eb; }
        @media (max-width: 900px) {
            .auth-card { flex-direction: column; width: 98vw; }
            .auth-side.bg { min-height: 180px; }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card animate__animated animate__fadeInDown">
        <!-- Left: Login -->
        <div class="auth-side">
            <h2 class="mb-4 text-center"><i class="fas fa-sign-in-alt text-primary me-2"></i>Sign In</h2>
            <?php if ($error && (!isset($_POST['action']) || $_POST['action'] === 'login')): ?>
                <div class="alert alert-danger animate__animated animate__fadeInDown"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success && (!isset($_POST['action']) || $_POST['action'] === 'login')): ?>
                <div class="alert alert-success animate__animated animate__fadeInDown"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="login">
                <div class="mb-3">
                    <label for="login_email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="login_email" name="email" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="login_password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="login_password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-auth">Login</button>
            </form>
            <div class="mt-3 text-center">
                <span>Don't have an account? <span class="switch-link" onclick="switchToRegister()">Register</span></span>
            </div>
        </div>
        <!-- Right: Register -->
        <div class="auth-side bg" id="registerSide" style="display:none;">
            <i class="fas fa-user-plus mb-3"></i>
            <h2>Register</h2>
            <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                <div class="alert alert-danger animate__animated animate__fadeInDown"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                <div class="alert alert-success animate__animated animate__fadeInDown"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="register">
                <div class="row g-2">
                    <div class="col-6 mb-2">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="col-6 mb-2">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" name="phone" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-success w-100 btn-auth mt-2">Register</button>
            </form>
            <div class="mt-3 text-center">
                <span>Already have an account? <span class="switch-link" onclick="switchToLogin()">Login</span></span>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.js"></script>
<script>
    function switchToRegister() {
        document.querySelector('.auth-side').style.display = 'none';
        document.getElementById('registerSide').style.display = 'flex';
    }
    function switchToLogin() {
        document.querySelector('.auth-side').style.display = 'flex';
        document.getElementById('registerSide').style.display = 'none';
    }
    // Auto-switch to register if registration error/success
    <?php if (isset($_POST['action']) && $_POST['action'] === 'register'): ?>
        switchToRegister();
    <?php endif; ?>
</script>
</body>
</html> 