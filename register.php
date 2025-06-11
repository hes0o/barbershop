<?php
session_start();
require_once 'config.php';
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        try {
            $db = new Database();
            
            // Check if username or email already exists
            if ($db->getUserByUsername($username)) {
                $error = 'Username already exists';
            } elseif ($db->getUserByEmail($email)) {
                $error = 'Email already exists';
            } else {
                // Create user
                if ($db->createUser($username, $email, $password, 'customer', $phone)) {
                    $_SESSION['success_message'] = 'Registration successful! You can now login.';
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'An error occurred during registration. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Barbershop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">Create an Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" required 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <label for="username">Username</label>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Email" required
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <label for="email">Email address</label>
                </div>

                <div class="form-floating">
                    <input type="tel" class="form-control" id="phone" name="phone" 
                           placeholder="Phone Number"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    <label for="phone">Phone Number (optional)</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required minlength="6">
                    <label for="password">Password</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" placeholder="Confirm Password" required>
                    <label for="confirm_password">Confirm Password</label>
                </div>

                <div class="password-requirements">
                    Password must be at least 6 characters long
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mt-3">
                    Register
                </button>

                <div class="text-center mt-3">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
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