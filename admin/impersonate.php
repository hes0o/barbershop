<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

// Only allow admin users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$db = new Database();
$user_id = (int)$_GET['id'];

// Get user details
$stmt = $db->getConnection()->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    // Store admin session data
    $_SESSION['admin_id'] = $_SESSION['user_id'];
    $_SESSION['admin_role'] = $_SESSION['role'];
    
    // Set impersonated user session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['impersonating'] = true;
    
    // Log the impersonation
    $admin_id = $_SESSION['admin_id'];
    $stmt = $db->getConnection()->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, 'impersonate', ?)");
    $details = "Admin (ID: $admin_id) impersonated user (ID: {$user['id']})";
    $stmt->bind_param("is", $admin_id, $details);
    $stmt->execute();
    
    // Redirect based on role
    switch ($user['role']) {
        case 'customer':
            header('Location: ' . BASE_URL . '/customer/dashboard.php');
            break;
        case 'barber':
            header('Location: ' . BASE_URL . '/barber/dashboard.php');
            break;
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/dashboard.php');
    }
    exit;
} else {
    // User not found
    header('Location: ' . BASE_URL . '/admin/users.php?error=User not found');
    exit;
} 