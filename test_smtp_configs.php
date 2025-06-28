<?php
/**
 * Test Different SMTP Configurations
 */

require_once 'includes/email_helper.php';

echo "<h1>🔧 Test Different SMTP Configurations</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .config { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
</style>";

// Get current config
$current_config = include 'includes/email_config.php';

// Different SMTP configurations to test
$configs_to_test = [
    [
        'name' => 'Current Config (Port 465 SSL)',
        'host' => $current_config['smtp_host'],
        'port' => 465,
        'secure' => 'ssl',
        'username' => $current_config['smtp_username'],
        'password' => $current_config['smtp_password']
    ],
    [
        'name' => 'Port 587 TLS',
        'host' => $current_config['smtp_host'],
        'port' => 587,
        'secure' => 'tls',
        'username' => $current_config['smtp_username'],
        'password' => $current_config['smtp_password']
    ],
    [
        'name' => 'Port 587 No Encryption',
        'host' => $current_config['smtp_host'],
        'port' => 587,
        'secure' => '',
        'username' => $current_config['smtp_username'],
        'password' => $current_config['smtp_password']
    ],
    [
        'name' => 'Port 25 No Encryption',
        'host' => $current_config['smtp_host'],
        'port' => 25,
        'secure' => '',
        'username' => $current_config['smtp_username'],
        'password' => $current_config['smtp_password']
    ],
    [
        'name' => 'Alternative Host (mail.domain)',
        'host' => 'mail.' . str_replace('www.', '', $current_config['smtp_host']),
        'port' => 587,
        'secure' => 'tls',
        'username' => $current_config['smtp_username'],
        'password' => $current_config['smtp_password']
    ]
];

// Test email address (change this to your email)
$test_email = 'your-email@example.com'; // CHANGE THIS TO YOUR EMAIL

echo "<div class='test-section'>";
echo "<h2>Testing SMTP Configurations</h2>";
echo "<p>Test email address: <strong>$test_email</strong></p>";
echo "<p><strong>Change the email address in the code above to your real email address.</strong></p>";
echo "</div>";

foreach ($configs_to_test as $config) {
    echo "<div class='test-section'>";
    echo "<h3>Testing: {$config['name']}</h3>";
    
    echo "<div class='config'>";
    echo "Host: {$config['host']}\n";
    echo "Port: {$config['port']}\n";
    echo "Secure: " . ($config['secure'] ? $config['secure'] : 'none') . "\n";
    echo "Username: {$config['username']}\n";
    echo "</div>";
    
    try {
        // Create PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Configure SMTP
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['secure'];
        $mail->Port = $config['port'];
        $mail->CharSet = 'UTF-8';
        
        // Set sender
        $mail->setFrom($current_config['from_email'], $current_config['from_name']);
        
        // Add recipient
        $mail->addAddress($test_email);
        
        // Set email content
        $mail->Subject = "Test - {$config['name']}";
        $mail->Body = "<h1>SMTP Test</h1><p>Testing configuration: {$config['name']}</p><p>If you receive this email, this configuration works!</p>";
        $mail->isHTML(true);
        
        // Send email
        $result = $mail->send();
        
        if ($result) {
            echo "<div class='success'>✅ SUCCESS - Email sent using {$config['name']}</div>";
            echo "<div class='info'>Check your email inbox for: 'Test - {$config['name']}'</div>";
        } else {
            echo "<div class='error'>❌ FAILED - {$config['name']}</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ ERROR - {$config['name']}: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
}

echo "<div class='test-section'>";
echo "<h2>Next Steps</h2>";
echo "<div class='info'>";
echo "<p><strong>If any configuration works:</strong></p>";
echo "<ol>";
echo "<li>Note which configuration succeeded</li>";
echo "<li>Update your email configuration with those settings</li>";
echo "<li>Test the email tester again</li>";
echo "</ol>";
echo "<p><strong>If none work:</strong></p>";
echo "<ol>";
echo "<li>Check your hosting control panel for email settings</li>";
echo "<li>Contact your hosting provider about SMTP email</li>";
echo "<li>Consider using an external email service (Gmail, SendGrid, etc.)</li>";
echo "</ol>";
echo "</div>";
echo "</div>";
?> 