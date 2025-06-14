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
    <title>BladeX - Login & Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #e0e7ff 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .auth-container {
            max-width: 500px;
            width: 95%;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        .auth-container:hover {
            transform: translateY(-5px);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        .auth-header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .auth-header p {
            color: #666;
            margin-bottom: 0;
            font-size: 1.1rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-auth {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            width: 100%;
            margin-top: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            background: #3498db;
            border: none;
        }
        .btn-auth:hover:not(:disabled) {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        .btn-auth:disabled {
            background: #b6c6e3;
            cursor: not-allowed;
        }
        .logo {
            font-size: 3rem;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .switch-link {
            color: #3498db;
            cursor: pointer;
            text-decoration: underline;
        }
        .switch-link:hover {
            color: #2980b9;
        }
        .form-floating {
            transition: all 0.3s ease;
        }
        .form-floating.hide {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container d-flex align-items-center justify-content-center min-vh-100">
        <div class="auth-container">
            <div class="auth-header">
                <div class="logo">BladeX</div>
                <h1>Welcome to BladeX</h1>
                <p>Sign in to your account or create a new one</p>
            </div>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <div id="loginForm">
                <h2>Login</h2>
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="login">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="login-email" name="email" placeholder="name@example.com" required>
                        <label for="login-email">Email address</label>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
                        <label for="login-password">Password</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2" style="z-index:2;" onclick="togglePassword('login-password', this)"><i class="fa fa-eye"></i></button>
                    </div>
                    <button type="submit" class="btn btn-auth">Sign In</button>
                </form>
                <div class="mt-3 text-center">
                    <span>Don't have an account? <span class="switch-link" onclick="switchToRegister()">Register</span></span>
                </div>
            </div>
            <div id="registerForm" style="display:none;">
                <h2>Register</h2>
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="register">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="register-first-name" name="first_name" placeholder="First Name" required>
                        <label for="register-first-name">First Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="register-last-name" name="last_name" placeholder="Last Name" required>
                        <label for="register-last-name">Last Name</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="register-email" name="email" placeholder="name@example.com" required>
                        <label for="register-email">Email address</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="register-phone" name="phone" pattern="[0-9]{10}" placeholder="Phone Number">
                        <label for="register-phone">Phone Number</label>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="register-password" name="password" placeholder="Password" required minlength="6" oninput="checkPasswordStrength(this.value)">
                        <label for="register-password">Password</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2" style="z-index:2;" onclick="togglePassword('register-password', this)"><i class="fa fa-eye"></i></button>
                        <div id="password-strength" class="mt-2"></div>
                    </div>
                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="register-confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="register-confirm-password">Confirm Password</label>
                        <button type="button" class="btn btn-outline-secondary btn-sm position-absolute top-50 end-0 translate-middle-y me-2" style="z-index:2;" onclick="togglePassword('register-confirm-password', this)"><i class="fa fa-eye"></i></button>
                    </div>
                    <button type="submit" class="btn btn-auth">Create Account</button>
                </form>
                <div class="mt-3 text-center">
                    <span>Already have an account? <span class="switch-link" onclick="switchToLogin()">Login</span></span>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchToRegister() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
        }
        function switchToLogin() {
            document.getElementById('loginForm').style.display = 'block';
            document.getElementById('registerForm').style.display = 'none';
        }
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            let text = '';
            let color = '';
            switch (strength) {
                case 0:
                case 1:
                    text = 'Weak'; color = 'text-danger'; break;
                case 2:
                    text = 'Medium'; color = 'text-warning'; break;
                case 3:
                    text = 'Good'; color = 'text-info'; break;
                case 4:
                    text = 'Strong'; color = 'text-success'; break;
            }
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
            } else {
                strengthDiv.innerHTML = `<span class="fw-bold ${color}">${text} password</span>`;
            }
        }
    </script>
</body>
</html> 