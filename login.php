<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

// Check for success message from registration
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} else {
    $success = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';

            error_log("Login attempt - Email: $email, Role: $role");

            if (empty($email) || empty($password) || empty($role)) {
                $error = 'Please fill in all fields';
                error_log("Login failed - Empty fields");
            } else {
                $db = new Database();
                $user = $db->authenticateUser($email, $password, $role);

                if ($user) {
                    error_log("Login successful for user: " . print_r($user, true));
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $role;
                    $_SESSION['username'] = $user['username'];

                    // Redirect to appropriate dashboard
                    if ($role === 'barber') {
                        header('Location: barber/dashboard.php');
                    } else {
                        header('Location: customer/dashboard.php');
                    }
                    exit;
                } else {
                    error_log("Login failed - Invalid credentials");
                    $error = 'Invalid email or password';
                }
            }
        } elseif ($_POST['action'] === 'register') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            $phone = trim($_POST['phone'] ?? '');

            // Validate input
            if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
                $error = 'All fields are required';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format';
            } else {
                $db = new Database();
                
                // Check if username or email already exists
                if ($db->getUserByUsername($username)) {
                    $error = 'Username already exists';
                } elseif ($db->getUserByEmail($email)) {
                    $error = 'Email already exists';
                } else {
                    // Create user
                    if ($db->createUser($username, $email, $password, 'customer', $phone)) {
                        $success = 'Registration successful! You can now login.';
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
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
    <title>Login - Barbershop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #2c3e50;
            --light-bg: #ecf0f1;
        }

        body {
            background: linear-gradient(135deg, var(--light-bg) 0%, #bdc3c7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-container {
            max-width: 900px;
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
            color: var(--primary-color);
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
            border-color: var(--secondary-color);
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
            background: var(--secondary-color);
            border: none;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .role-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .role-option {
            flex: 1;
            text-align: center;
            padding: 1.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .role-option:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .role-option.active {
            border-color: var(--secondary-color);
            background-color: rgba(52, 152, 219, 0.1);
        }

        .role-option i {
            font-size: 2rem;
            margin-bottom: 0.75rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .role-option.active i {
            color: var(--secondary-color);
            transform: scale(1.1);
        }

        .nav-tabs {
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--text-color);
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-tabs .nav-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--secondary-color);
            transition: width 0.3s ease;
        }

        .nav-tabs .nav-link:hover::after {
            width: 100%;
        }

        .nav-tabs .nav-link.active {
            color: var(--secondary-color);
            background: none;
        }

        .nav-tabs .nav-link.active::after {
            width: 100%;
        }

        .tab-content {
            padding: 1.5rem 0;
        }

        .password-requirements {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.75rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .form-floating {
            margin-bottom: 1.5rem;
        }

        .form-floating label {
            color: #666;
        }

        .alert {
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-container {
                width: 100%;
                margin: 1rem;
                padding: 1.5rem;
            }

            .auth-header h1 {
                font-size: 2rem;
            }

            .role-selector {
                flex-direction: column;
            }

            .nav-tabs .nav-link {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .slide-up {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Custom Checkbox Style */
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.25em;
            vertical-align: top;
            background-color: #fff;
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            border: 2px solid #dee2e6;
            appearance: none;
            color-adjust: exact;
            transition: background-color 0.15s ease-in-out, background-position 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .form-check-input:checked {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        /* Loading Spinner */
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="auth-container animate__animated animate__fadeIn">
        <div class="auth-header animate__animated animate__fadeInDown">
            <h1>Welcome to BarberShop</h1>
            <p>Sign in to your account or create a new one</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success animate__animated animate__fadeIn" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="authTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">
                    <i class="fas fa-user-plus me-2"></i>Register
                </button>
            </li>
        </ul>

        <div class="tab-content" id="authTabsContent">
            <!-- Login Form -->
            <div class="tab-pane fade show active animate__animated animate__fadeIn" id="login" role="tabpanel">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="login">
                    
                    <div class="role-selector">
                        <div class="role-option active" data-role="customer">
                            <i class="fas fa-user"></i>
                            <div>Customer</div>
                        </div>
                        <div class="role-option" data-role="barber">
                            <i class="fas fa-cut"></i>
                            <div>Barber</div>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="roleInput" value="customer">
                    
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="login-email" name="email" placeholder="name@example.com" required>
                        <label for="login-email">Email address</label>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="login-password" name="password" placeholder="Password" required>
                        <label for="login-password">Password</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </form>
            </div>

            <!-- Register Form -->
            <div class="tab-pane fade animate__animated animate__fadeIn" id="register" role="tabpanel">
                <form method="POST" action="" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="register-username" name="username" placeholder="Username" required>
                        <label for="register-username">Username</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="register-email" name="email" placeholder="name@example.com" required>
                        <label for="register-email">Email address</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="tel" class="form-control" id="register-phone" name="phone" pattern="[0-9]{10}" placeholder="Phone Number">
                        <label for="register-phone">Phone Number</label>
                        <div class="form-text">Enter a 10-digit phone number</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="register-password" name="password" placeholder="Password" required minlength="6">
                        <label for="register-password">Password</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="register-confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="register-confirm-password">Confirm Password</label>
                    </div>

                    <div class="password-requirements">
                        <i class="fas fa-info-circle me-2"></i>
                        Password must be at least 6 characters long
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-auth">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                </form>
            </div>
        </div>

        <div class="spinner" id="loadingSpinner"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle role selection with animation
        const roleOptions = document.querySelectorAll('.role-option');
        const roleInput = document.getElementById('roleInput');

        roleOptions.forEach(option => {
            option.addEventListener('click', function() {
                roleOptions.forEach(opt => {
                    opt.classList.remove('active');
                    opt.classList.remove('animate__animated', 'animate__pulse');
                });
                this.classList.add('active');
                this.classList.add('animate__animated', 'animate__pulse');
                roleInput.value = this.dataset.role;
            });
        });

        // Form validation with animation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                        form.classList.add('animate__animated', 'animate__shakeX');
                        setTimeout(() => {
                            form.classList.remove('animate__animated', 'animate__shakeX');
                        }, 1000);
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Phone number validation with formatting
        const phoneInput = document.getElementById('register-phone');
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            e.target.value = value;
        });

        // Show loading spinner on form submit
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                document.getElementById('loadingSpinner').style.display = 'block';
            });
        });

        // Tab switching animation
        const tabLinks = document.querySelectorAll('.nav-link');
        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                const target = document.querySelector(this.dataset.bsTarget);
                target.classList.remove('animate__fadeIn');
                void target.offsetWidth; // Trigger reflow
                target.classList.add('animate__fadeIn');
            });
        });

        // Password strength indicator
        const passwordInput = document.getElementById('register-password');
        const requirements = document.querySelector('.password-requirements');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = [];

            if (password.length >= 6) {
                strength++;
                feedback.push('✓ Length is good');
            } else {
                feedback.push('✗ At least 6 characters');
            }

            if (/[A-Z]/.test(password)) {
                strength++;
                feedback.push('✓ Contains uppercase');
            } else {
                feedback.push('✗ Add uppercase letter');
            }

            if (/[0-9]/.test(password)) {
                strength++;
                feedback.push('✓ Contains number');
            } else {
                feedback.push('✗ Add a number');
            }

            requirements.innerHTML = feedback.join('<br>');
        });
    </script>
</body>
</html> 