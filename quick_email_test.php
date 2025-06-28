<?php
/**
 * Quick Email Test - Tests email functionality without database
 */

require_once __DIR__ . '/includes/email_helper.php';

echo "<h1>Quick Email System Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style>";

try {
    echo "<div class='test-section'>";
    echo "<h2>1. Testing Email Helper</h2>";
    
    $emailHelper = new EmailHelper();
    echo "<div class='success'>✓ EmailHelper class loaded successfully</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>2. Testing Email Templates</h2>";
    
    $templates = [
        'base.html',
        'barber_notification.html',
        'appointment_confirmation.html'
    ];
    
    foreach ($templates as $template) {
        $template_path = __DIR__ . '/email_templates/' . $template;
        if (file_exists($template_path)) {
            echo "<div class='success'>✓ Template found: $template</div>";
        } else {
            echo "<div class='error'>✗ Template missing: $template</div>";
        }
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>3. Testing Template Processing</h2>";
    
    // Test template processing
    $base = file_get_contents(__DIR__ . '/email_templates/base.html');
    $barber_template = file_get_contents(__DIR__ . '/email_templates/barber_notification.html');
    
    if ($base && $barber_template) {
        echo "<div class='success'>✓ Templates can be loaded</div>";
        
        // Test variable replacement
        $test_vars = [
            'greeting' => 'Hello Test Barber,',
            'appointment_date' => 'December 15, 2024',
            'appointment_time' => '2:00 PM',
            'service_name' => 'Test Haircut',
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+1 (555) 123-4567'
        ];
        
        foreach ($test_vars as $key => $value) {
            $barber_template = str_replace('{{' . $key . '}}', $value, $barber_template);
        }
        
        $html = str_replace(['{{subject}}', '{{content}}'], ['Test Subject', $barber_template], $base);
        
        if (strpos($html, 'Test Barber') !== false && strpos($html, 'Test Customer') !== false) {
            echo "<div class='success'>✓ Template variable replacement working</div>";
        } else {
            echo "<div class='error'>✗ Template variable replacement failed</div>";
        }
    } else {
        echo "<div class='error'>✗ Cannot load templates</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>4. Testing Email Configuration</h2>";
    
    if (file_exists(__DIR__ . '/includes/email_config.php')) {
        echo "<div class='success'>✓ Email configuration file exists</div>";
        
        // Test if we can load email config safely
        try {
            $config = include __DIR__ . '/includes/email_config.php';
            if (is_array($config) && !empty($config)) {
                echo "<div class='success'>✓ Email configuration loaded</div>";
                echo "<div class='info'>Found " . count($config) . " configuration settings</div>";
                
                // Check for required settings
                $required_settings = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email'];
                $missing_settings = [];
                
                foreach ($required_settings as $setting) {
                    if (!isset($config[$setting]) || empty($config[$setting])) {
                        $missing_settings[] = $setting;
                    }
                }
                
                if (empty($missing_settings)) {
                    echo "<div class='success'>✓ All required email settings are configured</div>";
                } else {
                    echo "<div class='error'>✗ Missing required settings: " . implode(', ', $missing_settings) . "</div>";
                }
            } else {
                echo "<div class='error'>✗ Email configuration is empty</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>✗ Error loading email configuration: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    } else {
        echo "<div class='error'>✗ Email configuration file missing</div>";
    }
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>5. Template Preview Links</h2>";
    echo "<div class='info'>";
    echo "<p>You can preview the email templates here:</p>";
    echo "<ul>";
    echo "<li><a href='preview_template.php?template=barber_notification' target='_blank'>Barber Notification Template</a></li>";
    echo "<li><a href='preview_template.php?template=appointment_confirmation' target='_blank'>Customer Confirmation Template</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='test-section'>";
    echo "<h2>6. Manual Testing Steps</h2>";
    echo "<div class='info'>";
    echo "<p>To test the full email flow:</p>";
    echo "<ol>";
    echo "<li>Login as a customer and book an appointment</li>";
    echo "<li>Check if the barber receives a notification email</li>";
    echo "<li>Login as the barber and approve the appointment</li>";
    echo "<li>Check if the customer receives a confirmation email</li>";
    echo "</ol>";
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
echo "<p>If all tests pass above, your email system is ready for testing!</p>";
echo "<p>Next steps:</p>";
echo "<ul>";
echo "<li>Test the full booking flow</li>";
echo "<li>Check your email inbox for test emails</li>";
echo "<li>Verify email templates look correct</li>";
echo "</ul>";
echo "</div>";
echo "</div>";
?> 