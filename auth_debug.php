<?php
require_once 'config.php';
require_once 'includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function h($v) { return htmlspecialchars($v, ENT_QUOTES); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $db = new Database();
    $user = $db->getUserByEmail($email);
    echo "<h3>Step 1: User Lookup</h3>";
    if ($user) {
        echo "<pre>" . print_r($user, true) . "</pre>";
        echo "<b>Password Hash in DB:</b> <code>" . h($user['password']) . "</code><br>";
        echo "<b>Role in DB:</b> <code>" . h($user['role']) . "</code><br>";
        $verify = password_verify($password, $user['password']);
        echo "<b>password_verify result:</b> " . ($verify ? 'TRUE' : 'FALSE') . "<br>";
        echo "<b>Role match:</b> " . ($user['role'] === $role ? 'TRUE' : 'FALSE') . "<br>";
        echo "<h3>Step 2: Using authenticateUser()</h3>";
        $result = $db->authenticateUser($email, $password, $role);
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "<b>No user found with that email.</b>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authentication Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h2>Authentication Debug Tool</h2>
    <form method="POST" class="card p-4 mb-4">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role" required>
                <option value="customer">Customer</option>
                <option value="barber">Barber</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Test Authentication</button>
    </form>
</div>
</body>
</html> 