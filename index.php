<?php
session_start();
require_once __DIR__ . '/config.php';

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
header('Location: ' . BASE_URL . '/login.php');
exit; 