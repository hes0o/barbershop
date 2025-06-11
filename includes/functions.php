<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isBarber() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'barber';
}

function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

function requireBarber() {
    if (!isBarber()) {
        header('Location: index.php');
        exit();
    }
}

// Date and Time Functions
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function getDayName($day_number) {
    $days = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday'
    ];
    return $days[$day_number] ?? 'Unknown';
}

// Validation Functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateTime($time) {
    $t = DateTime::createFromFormat('H:i:s', $time);
    return $t && $t->format('H:i:s') === $time;
}

// Error and Success Messages
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        $class = $type === 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert $class alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

// Appointment Status Functions
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-warning',
        'confirmed' => 'bg-success',
        'completed' => 'bg-info',
        'cancelled' => 'bg-danger'
    ];
    return $classes[$status] ?? 'bg-secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled'
    ];
    return $texts[$status] ?? 'Unknown';
}

// Time Slot Functions
function generateTimeSlots($start_time, $end_time, $duration = 30) {
    $slots = [];
    $current = strtotime($start_time);
    $end = strtotime($end_time);
    
    while ($current < $end) {
        $slots[] = date('H:i:s', $current);
        $current = strtotime("+$duration minutes", $current);
    }
    
    return $slots;
}

// Price Formatting
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Security Functions
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// File Upload Functions
function handleFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png'], $max_size = 5242880) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file upload';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File is too large';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errors[] = 'File was only partially uploaded';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file was uploaded';
            break;
        default:
            $errors[] = 'Unknown upload error';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $errors[] = 'File is too large';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_type = $finfo->file($file['tmp_name']);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        $errors[] = 'Invalid file type';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $ext;
    $upload_path = 'uploads/' . $filename;
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $errors[] = 'Failed to move uploaded file';
        return ['success' => false, 'errors' => $errors];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'path' => $upload_path
    ];
}
?> 