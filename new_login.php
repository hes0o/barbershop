<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = new Database();
        // Try to find user by email
        $user = $db->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            // Redirect based on role
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Poppins', sans-serif; }
        .login-container { max-width: 400px; margin: 5% auto; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); padding: 2rem; }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="mb-4 text-center">Sign In</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html> 