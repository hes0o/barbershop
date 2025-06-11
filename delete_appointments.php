<?php
require_once 'config.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Delete all appointments
$sql = "TRUNCATE TABLE appointments";
if ($conn->query($sql)) {
    echo "All appointments have been deleted successfully.";
} else {
    echo "Error deleting appointments: " . $conn->error;
}

$conn->close();
?> 