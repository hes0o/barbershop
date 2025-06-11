<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a barber
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'barber') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['status'])) {
    $db = new Database();
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    
    if ($db->updateAppointmentStatus($appointment_id, $status)) {
        setFlashMessage('success', 'Appointment status updated successfully!');
    } else {
        setFlashMessage('error', 'Failed to update appointment status. Please try again.');
    }
}

header('Location: barber_dashboard.php#appointments');
exit;
?> 