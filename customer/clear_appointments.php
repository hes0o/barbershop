<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Clear Past Appointments</h2>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    $today = date('Y-m-d');
    $sql = "DELETE FROM appointments WHERE appointment_date < ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "Error preparing statement: " . $conn->error;
        exit;
    }
    $stmt->bind_param("s", $today);
    if ($stmt->execute()) {
        echo "<div style='color: green;'>✓ Deleted " . $stmt->affected_rows . " past appointments.</div>";
    } else {
        echo "<div style='color: red;'>✗ Error deleting appointments: " . $stmt->error . "</div>";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
} 