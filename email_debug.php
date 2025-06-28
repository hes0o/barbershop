<?php
/**
 * Email Debug - Detailed debugging for email issues
 */

require_once 'includes/email_helper.php';
require_once 'includes/email_service.php';

echo "<h1>🔍 Email System Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .code-block { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
</style>";

try {
    echo "<div class='debug-section'>";
    echo "<h2>1. Email Configuration Check</h2>";
    
    // Load email configuration
    $email_config = include 'includes/email_config.php';
    
    if (is_array($email_config) && !empty($email_config)) {
        echo "<div class='success'>✓ Email configuration loaded</div>";
        echo "<div class='info'>Configuration details:</div>";
        echo "<div class='code-block'>";
        foreach ($email_config as $key => $value) {
            if ($key === 'smtp_password') {
                echo "$key: " . str_repeat('*', strlen($value)) . "\n";
            } else {
                echo "$key: $value\n";
            }
        }
        echo "</div>";
    } else {
        echo "<div class='error'>✗ Email configuration is empty or invalid</div>";
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>2. Email Helper Test</h2>";
    
    try {
        $emailHelper = new EmailHelper();
        echo "<div class='success'>✓ EmailHelper class instantiated successfully</div>";
        
        // Test template loading
        $barber_template = $emailHelper->loadTemplate('barber_notification');
        if ($barber_template) {
            echo "<div class='success'>✓ Barber notification template loaded</div>";
        } else {
            echo "<div class='error'>✗ Failed to load barber notification template</div>";
        }
        
        $confirmation_template = $emailHelper->loadTemplate('appointment_confirmation');
        if ($confirmation_template) {
            echo "<div class='success'>✓ Appointment confirmation template loaded</div>";
        } else {
            echo "<div class='error'>✗ Failed to load appointment confirmation template</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>✗ EmailHelper error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>3. Email Service Test</h2>";
    
    try {
        $emailService = new EmailService();
        echo "<div class='success'>✓ EmailService class instantiated successfully</div>";
        
        // Test with sample data
        $test_data = [
            'barber_email' => 'test@example.com',
            'customer_name' => 'Test Customer',
            'appointment_date' => 'December 15, 2024',
            'appointment_time' => '2:00 PM',
            'service_name' => 'Test Haircut',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '+1 (555) 123-4567',
            'barber_name' => 'Test Barber'
        ];
        
        echo "<div class='info'>Testing with sample data:</div>";
        echo "<div class='code-block'>";
        foreach ($test_data as $key => $value) {
            echo "$key: $value\n";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>✗ EmailService error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>4. Template Processing Test</h2>";
    
    try {
        $emailHelper = new EmailHelper();
        
        // Test barber notification template processing
        $barber_vars = [
            'greeting' => 'Hello Test Barber,',
            'appointment_date' => 'December 15, 2024',
            'appointment_time' => '2:00 PM',
            'service_name' => 'Test Haircut',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+1 (555) 123-4567'
        ];
        
        $processed_barber = $emailHelper->processTemplate('barber_notification', $barber_vars);
        if ($processed_barber) {
            echo "<div class='success'>✓ Barber notification template processed successfully</div>";
            echo "<div class='info'>Template length: " . strlen($processed_barber) . " characters</div>";
        } else {
            echo "<div class='error'>✗ Failed to process barber notification template</div>";
        }
        
        // Test confirmation template processing
        $confirmation_vars = [
            'greeting' => 'Hello Test Customer,',
            'appointment_date' => 'December 15, 2024',
            'appointment_time' => '2:00 PM',
            'service_name' => 'Test Haircut',
            'barber_name' => 'Test Barber'
        ];
        
        $processed_confirmation = $emailHelper->processTemplate('appointment_confirmation', $confirmation_vars);
        if ($processed_confirmation) {
            echo "<div class='success'>✓ Appointment confirmation template processed successfully</div>";
            echo "<div class='info'>Template length: " . strlen($processed_confirmation) . " characters</div>";
        } else {
            echo "<div class='error'>✗ Failed to process appointment confirmation template</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>✗ Template processing error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>5. SMTP Connection Test</h2>";
    
    try {
        // Test SMTP connection
        $email_config = include 'includes/email_config.php';
        
        if (isset($email_config['smtp_host']) && isset($email_config['smtp_port'])) {
            echo "<div class='info'>Testing SMTP connection to {$email_config['smtp_host']}:{$email_config['smtp_port']}</div>";
            
            // Try to create a simple SMTP connection test
            $connection = @fsockopen($email_config['smtp_host'], $email_config['smtp_port'], $errno, $errstr, 10);
            
            if ($connection) {
                echo "<div class='success'>✓ SMTP connection successful</div>";
                fclose($connection);
            } else {
                echo "<div class='error'>✗ SMTP connection failed: $errstr (Error $errno)</div>";
                echo "<div class='warning'>⚠ This might be due to hosting restrictions or firewall settings</div>";
            }
        } else {
            echo "<div class='error'>✗ SMTP host or port not configured</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>✗ SMTP test error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>6. PHP Mail Function Test</h2>";
    
    // Test if PHP mail function is available
    if (function_exists('mail')) {
        echo "<div class='success'>✓ PHP mail() function is available</div>";
    } else {
        echo "<div class='error'>✗ PHP mail() function is not available</div>";
    }
    
    // Check for required PHP extensions
    $required_extensions = ['openssl', 'mbstring'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "<div class='success'>✓ $ext extension loaded</div>";
        } else {
            echo "<div class='error'>✗ $ext extension not loaded</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>7. File Permissions Check</h2>";
    
    $files_to_check = [
        'email_templates/',
        'email_templates/base.html',
        'email_templates/barber_notification.html',
        'email_templates/appointment_confirmation.html',
        'includes/email_helper.php',
        'includes/email_service.php',
        'includes/email_config.php'
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists($file)) {
            if (is_readable($file)) {
                echo "<div class='success'>✓ $file exists and is readable</div>";
            } else {
                echo "<div class='error'>✗ $file exists but is not readable</div>";
            }
        } else {
            echo "<div class='error'>✗ $file does not exist</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>8. Common Solutions</h2>";
    echo "<div class='info'>";
    echo "<p><strong>If emails are failing to send, try these solutions:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Check hosting email settings:</strong> Some hosting providers require specific SMTP settings</li>";
    echo "<li><strong>Verify SMTP credentials:</strong> Make sure username and password are correct</li>";
    echo "<li><strong>Check email limits:</strong> Hosting providers often have daily email sending limits</li>";
    echo "<li><strong>Test with different port:</strong> Try port 587 instead of 465, or vice versa</li>";
    echo "<li><strong>Check spam filters:</strong> Test emails might be going to spam folder</li>";
    echo "<li><strong>Contact hosting support:</strong> Some providers require email sending to be enabled</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Critical Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='debug-section'>";
echo "<h2>9. Next Steps</h2>";
echo "<div class='info'>";
echo "<p>Based on the debug results above:</p>";
echo "<ul>";
echo "<li>If SMTP connection fails: Check your hosting provider's SMTP settings</li>";
echo "<li>If templates fail to load: Check file permissions and paths</li>";
echo "<li>If configuration is empty: Check database connection and email settings</li>";
echo "<li>If everything looks good: The issue might be with the actual email sending process</li>";
echo "</ul>";
echo "<p><strong>Try the email tester again after fixing any issues found above.</strong></p>";
echo "</div>";
echo "</div>";
?> 