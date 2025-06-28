<?php
/**
 * Hosting Environment Test - Tests email system on hosting service
 */

echo "<h1>Hosting Environment Email Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

try {
    echo "<div class='test-section'>";
    echo "<h2>1. Hosting Environment Check</h2>";
    
    echo "<div class='info'>";
    echo "<p><strong>Server Information:</strong></p>";
    echo "<ul>";
    echo "<li>PHP Version: " . phpversion() . "</li>";
    echo "<li>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
    echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
    echo "<li>Current Path: " . __DIR__ . "</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>2. PHP Extensions Check</h2>";
    
    $required_extensions = ['mysqli', 'openssl', 'mbstring', 'json'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "<div class='success'>✓ $ext extension loaded</div>";
        } else {
            echo "<div class='error'>✗ $ext extension not loaded</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>3. File Structure Test</h2>";
    
    $required_files = [
        'includes/email_helper.php',
        'includes/email_service.php',
        'includes/email_config.php',
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
    echo "<h2>4. Database Connection Test</h2>";
    
    // Test database connection
    if (file_exists('includes/config.php')) {
        echo "<div class='success'>✓ Config file exists</div>";
        
        // Try to load config without executing
        $config_content = file_get_contents('includes/config.php');
        if (strpos($config_content, 'DB_HOST') !== false) {
            echo "<div class='success'>✓ Database configuration found</div>";
        } else {
            echo "<div class='warning'>⚠ Database configuration not found in config</div>";
        }
    } else {
        echo "<div class='error'>✗ Config file missing</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>5. Email Configuration Test</h2>";
    
    if (file_exists('includes/email_config.php')) {
        echo "<div class='success'>✓ Email config file exists</div>";
        
        try {
            $email_config = include 'includes/email_config.php';
            if (is_array($email_config) && !empty($email_config)) {
                echo "<div class='success'>✓ Email configuration loaded</div>";
                echo "<div class='info'>Found " . count($email_config) . " settings</div>";
                
                // Check for required settings
                $required_settings = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email'];
                $missing = [];
                
                foreach ($required_settings as $setting) {
                    if (!isset($email_config[$setting]) || empty($email_config[$setting])) {
                        $missing[] = $setting;
                    }
                }
                
                if (empty($missing)) {
                    echo "<div class='success'>✓ All required email settings configured</div>";
                } else {
                    echo "<div class='warning'>⚠ Missing settings: " . implode(', ', $missing) . "</div>";
                }
            } else {
                echo "<div class='warning'>⚠ Email configuration is empty</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>✗ Error loading email config: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='error'>✗ Email config file missing</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>6. Template Processing Test</h2>";
    
    $barber_template = file_get_contents('email_templates/barber_notification.html');
    $confirmation_template = file_get_contents('email_templates/appointment_confirmation.html');
    
    if ($barber_template && $confirmation_template) {
        echo "<div class='success'>✓ Templates can be loaded</div>";
        
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
        
        $processed = $barber_template;
        foreach ($test_data as $key => $value) {
            $processed = str_replace('{{' . $key . '}}', $value, $processed);
        }
        
        if (strpos($processed, '{{') === false) {
            echo "<div class='success'>✓ Template variable replacement working</div>";
        } else {
            echo "<div class='error'>✗ Template variable replacement failed</div>";
        }
    } else {
        echo "<div class='error'>✗ Cannot load templates</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>7. Quick Links for Testing</h2>";
    echo "<div class='info'>";
    echo "<p>Test these URLs:</p>";
    echo "<ul>";
    echo "<li><a href='preview_template.php?template=barber_notification' target='_blank'>Preview Barber Notification</a></li>";
    echo "<li><a href='preview_template.php?template=appointment_confirmation' target='_blank'>Preview Customer Confirmation</a></li>";
    echo "<li><a href='admin/email_management.php' target='_blank'>Admin Email Management</a></li>";
    echo "<li><a href='simple_email_test.php' target='_blank'>Simple Email Test</a></li>";
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
echo "<h2>Hosting Setup Recommendations</h2>";
echo "<div class='info'>";
echo "<p><strong>For your hosting service:</strong></p>";
echo "<ol>";
echo "<li>Make sure MySQL/MariaDB is enabled in your hosting control panel</li>";
echo "<li>Configure your database credentials in the .env file</li>";
echo "<li>Set up SMTP email settings for your hosting provider</li>";
echo "<li>Ensure PHP has the required extensions (mysqli, openssl, mbstring)</li>";
echo "<li>Check file permissions for the email_templates folder</li>";
echo "</ol>";
echo "</div>";
echo "</div>";
?> 