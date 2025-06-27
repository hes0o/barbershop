<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/includes/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'barber') {
        header('Location: ' . BASE_URL . '/barber/dashboard.php');
    } else if ($_SESSION['role'] === 'customer') {
        header('Location: ' . BASE_URL . '/customer/dashboard.php');
    }
    exit;
}

// If not logged in, redirect to login page
header('Location: ' . BASE_URL . '/new_login.php');
exit; 