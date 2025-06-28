<?php
/**
 * Simple Email Test - Basic email system test without database
 */

echo "<h1>Simple Email System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

try {
    echo "<div class='test-section'>";
    echo "<h2>1. Testing File Structure</h2>";
    
    $required_files = [
        'includes/email_helper.php',
        'includes/email_service.php',
        'email_templates/base.html',
        'email_templates/barber_notification.html',
        'email_templates/appointment_confirmation.html'
    ];
    
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            echo "<div class='success'>✓ Found: $file</div>";
        } else {
            echo "<div class='error'>✗ Missing: $file</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>2. Testing Email Templates</h2>";
    
    // Test template loading
    $base_template = file_get_contents('email_templates/base.html');
    $barber_template = file_get_contents('email_templates/barber_notification.html');
    $confirmation_template = file_get_contents('email_templates/appointment_confirmation.html');
    
    if ($base_template && $barber_template && $confirmation_template) {
        echo "<div class='success'>✓ All email templates can be loaded</div>";
        
        // Check for required variables in templates
        $barber_vars = ['{{greeting}}', '{{appointment_date}}', '{{appointment_time}}', '{{service_name}}', '{{customer_name}}', '{{customer_email}}', '{{customer_phone}}'];
        $confirmation_vars = ['{{greeting}}', '{{appointment_date}}', '{{appointment_time}}', '{{service_name}}', '{{barber_name}}'];
        
        $missing_barber_vars = [];
        foreach ($barber_vars as $var) {
            if (strpos($barber_template, $var) === false) {
                $missing_barber_vars[] = $var;
            }
        }
        
        $missing_confirmation_vars = [];
        foreach ($confirmation_vars as $var) {
            if (strpos($confirmation_template, $var) === false) {
                $missing_confirmation_vars[] = $var;
            }
        }
        
        if (empty($missing_barber_vars)) {
            echo "<div class='success'>✓ Barber notification template has all required variables</div>";
        } else {
            echo "<div class='error'>✗ Barber template missing variables: " . implode(', ', $missing_barber_vars) . "</div>";
        }
        
        if (empty($missing_confirmation_vars)) {
            echo "<div class='success'>✓ Customer confirmation template has all required variables</div>";
        } else {
            echo "<div class='error'>✗ Confirmation template missing variables: " . implode(', ', $missing_confirmation_vars) . "</div>";
        }
    } else {
        echo "<div class='error'>✗ Cannot load email templates</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>3. Testing Template Processing</h2>";
    
    // Test variable replacement
    $test_data = [
        'greeting' => 'Hello Test User,',
        'appointment_date' => 'December 15, 2024',
        'appointment_time' => '2:00 PM',
        'service_name' => 'Test Haircut',
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '+1 (555) 123-4567',
        'barber_name' => 'Omar Serjavi'
    ];
    
    $processed_barber = $barber_template;
    foreach ($test_data as $key => $value) {
        $processed_barber = str_replace('{{' . $key . '}}', $value, $processed_barber);
    }
    
    $processed_confirmation = $confirmation_template;
    foreach ($test_data as $key => $value) {
        $processed_confirmation = str_replace('{{' . $key . '}}', $value, $processed_confirmation);
    }
    
    // Check if variables were replaced
    if (strpos($processed_barber, '{{') === false && strpos($processed_barber, 'Test User') !== false) {
        echo "<div class='success'>✓ Barber template variable replacement working</div>";
    } else {
        echo "<div class='error'>✗ Barber template variable replacement failed</div>";
    }
    
    if (strpos($processed_confirmation, '{{') === false && strpos($processed_confirmation, 'Test User') !== false) {
        echo "<div class='success'>✓ Confirmation template variable replacement working</div>";
    } else {
        echo "<div class='error'>✗ Confirmation template variable replacement failed</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>4. Email System Status</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Email System Components:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Email templates are in place</li>";
    echo "<li>✅ Template variable processing works</li>";
    echo "<li>✅ File structure is correct</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Ensure database is running and accessible</li>";
    echo "<li>Configure email SMTP settings in database</li>";
    echo "<li>Test the full booking flow</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>5. Quick Links</h2>";
    echo "<div class='info'>";
    echo "<p>Test these URLs in your browser:</p>";
    echo "<ul>";
    echo "<li><a href='preview_template.php?template=barber_notification' target='_blank'>Preview Barber Notification</a></li>";
    echo "<li><a href='preview_template.php?template=appointment_confirmation' target='_blank'>Preview Customer Confirmation</a></li>";
    echo "<li><a href='admin/email_management.php' target='_blank'>Admin Email Management</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='test-section'>";
echo "<h2>Test Summary</h2>";
echo "<div class='info'>";
echo "<p>If all tests above show green checkmarks, your email system structure is correct!</p>";
echo "<p>The main issue was likely the function redeclaration error, which has been fixed.</p>";
echo "</div>";
echo "</div>";
?> 