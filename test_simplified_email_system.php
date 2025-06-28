<?php
/**
 * Simplified Email System Test Script
 * 
 * This script tests the simplified email system with only:
 * 1. Barber notification when customer books
 * 2. Customer confirmation when barber approves
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/email_service.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>BladeX Simplified Email System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
    .success-bg { background-color: #d4edda; }
    .error-bg { background-color: #f8d7da; }
    .warning-bg { background-color: #fff3cd; }
    .info-bg { background-color: #d1ecf1; }
</style>";

try {
    $db = new Database();
    $emailService = new EmailService();
    
    echo "<div class='test-section'>";
    echo "<h2>1. Database Connection Test</h2>";
    
    if ($db->getConnection()) {
        echo "<div class='test-result success-bg'>✓ Database connection successful</div>";
    } else {
        echo "<div class='test-result error-bg'>✗ Database connection failed</div>";
        exit;
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>2. Email Configuration Test</h2>";
    
    $emailConfig = $db->getEmailConfig();
    if (!empty($emailConfig)) {
        echo "<div class='test-result success-bg'>✓ Email configuration loaded successfully</div>";
        echo "<div class='info-bg'>Found " . count($emailConfig) . " configuration settings</div>";
    } else {
        echo "<div class='test-result error-bg'>✗ Email configuration not found</div>";
        echo "<div class='warning-bg'>Please run setup_email_config.php first</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>3. Email Templates Test</h2>";
    
    $templates = [
        'barber_notification.html',
        'appointment_confirmation.html',
        'base.html'
    ];
    
    foreach ($templates as $template) {
        $template_path = __DIR__ . '/email_templates/' . $template;
        if (file_exists($template_path)) {
            echo "<div class='test-result success-bg'>✓ Template found: $template</div>";
        } else {
            echo "<div class='test-result error-bg'>✗ Template missing: $template</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>4. Sample Data Test</h2>";
    
    // Get sample data for testing
    $customers = $db->getAllCustomers();
    $appointments = $db->getAppointmentsByDate(date('Y-m-d'));
    
    if (!empty($customers)) {
        echo "<div class='test-result success-bg'>✓ Found " . count($customers) . " customers for testing</div>";
        $test_customer = $customers[0];
    } else {
        echo "<div class='test-result warning-bg'>⚠ No customers found - some tests will be skipped</div>";
        $test_customer = null;
    }
    
    if (!empty($appointments)) {
        echo "<div class='test-result success-bg'>✓ Found " . count($appointments) . " appointments for testing</div>";
        $test_appointment = $appointments[0];
    } else {
        echo "<div class='test-result warning-bg'>⚠ No appointments found - some tests will be skipped</div>";
        $test_appointment = null;
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>5. Email Service Tests</h2>";
    
    // Test barber notification
    if ($test_appointment) {
        echo "<h3>Testing Barber Notification</h3>";
        $result = $emailService->sendBarberNotification($test_appointment['id']);
        if ($result) {
            echo "<div class='test-result success-bg'>✓ Barber notification sent successfully</div>";
        } else {
            echo "<div class='test-result error-bg'>✗ Barber notification failed</div>";
        }
    }
    
    // Test customer confirmation
    if ($test_appointment) {
        echo "<h3>Testing Customer Confirmation</h3>";
        $result = $emailService->sendCustomerConfirmation($test_appointment['id']);
        if ($result) {
            echo "<div class='test-result success-bg'>✓ Customer confirmation sent successfully</div>";
        } else {
            echo "<div class='test-result error-bg'>✗ Customer confirmation failed</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>6. Template Preview Test</h2>";
    
    $templates_to_preview = [
        'barber_notification',
        'appointment_confirmation'
    ];
    
    foreach ($templates_to_preview as $template) {
        $preview_url = "preview_template.php?template=$template";
        echo "<div class='test-result info-bg'>";
        echo "📧 <a href='$preview_url' target='_blank'>Preview $template template</a>";
        echo "</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>7. Admin Interface Test</h2>";
    
    $admin_urls = [
        'admin/email_config.php' => 'Email Configuration',
        'admin/email_management.php' => 'Email Management'
    ];
    
    foreach ($admin_urls as $url => $name) {
        if (file_exists($url)) {
            echo "<div class='test-result success-bg'>✓ <a href='$url' target='_blank'>$name</a> available</div>";
        } else {
            echo "<div class='test-result error-bg'>✗ $name not found</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>8. Barber Endpoints Test</h2>";
    
    $barber_endpoints = [
        'barber/approve_appointment.php' => 'Approve Appointment',
        'barber/reject_appointment.php' => 'Reject Appointment'
    ];
    
    foreach ($barber_endpoints as $url => $name) {
        if (file_exists($url)) {
            echo "<div class='test-result success-bg'>✓ $name endpoint available</div>";
        } else {
            echo "<div class='test-result error-bg'>✗ $name endpoint not found</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>Test Summary</h2>";
    echo "<div class='info-bg'>";
    echo "<h3>Simplified Email System Features:</h3>";
    echo "<ul>";
    echo "<li>✓ Barber notification emails (when customer books)</li>";
    echo "<li>✓ Customer confirmation emails (when barber approves)</li>";
    echo "<li>✓ Email template management</li>";
    echo "<li>✓ Admin testing interface</li>";
    echo "<li>✓ Barber approval/rejection endpoints</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result error-bg'>";
    echo "<h2>Critical Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
    echo "</div>";
}

echo "<div class='test-section'>";
echo "<h2>Next Steps</h2>";
echo "<div class='info-bg'>";
echo "<ol>";
echo "<li>Check your email inbox for test emails</li>";
echo "<li>Review email templates in the admin interface</li>";
echo "<li>Test the full booking flow: customer books → barber receives notification → barber approves → customer receives confirmation</li>";
echo "<li>Verify barber can approve/reject appointments from their dashboard</li>";
echo "</ol>";
echo "</div>";
echo "</div>";
?> 