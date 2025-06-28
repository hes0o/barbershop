<?php
/**
 * Detailed Email Debug - Shows actual SMTP conversation and errors
 */

require_once 'includes/email_helper.php';
require_once 'includes/email_service.php';

echo "<h1>🔍 Detailed Email Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .code-block { background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; white-space: pre-wrap; }
    .smtp-log { background: #000; color: #0f0; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; max-height: 400px; overflow-y: auto; }
</style>";

try {
    echo "<div class='debug-section'>";
    echo "<h2>1. PHPMailer Debug Test</h2>";
    
    // Create a test email with detailed debugging
    $email_config = include 'includes/email_config.php';
    
    echo "<div class='info'>Testing with configuration:</div>";
    echo "<div class='code-block'>";
    echo "SMTP Host: {$email_config['smtp_host']}\n";
    echo "SMTP Port: {$email_config['smtp_port']}\n";
    echo "SMTP Username: {$email_config['smtp_username']}\n";
    echo "SMTP Secure: {$email_config['smtp_secure']}\n";
    echo "From Email: {$email_config['from_email']}\n";
    echo "</div>";
    
    // Create PHPMailer instance with debugging
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Enable debugging
    $mail->SMTPDebug = 3; // Enable verbose debug output
    $mail->Debugoutput = function($str, $level) {
        echo "<div class='smtp-log'>$str</div>";
    };
    
    // Configure SMTP
    $mail->isSMTP();
    $mail->Host = $email_config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $email_config['smtp_username'];
    $mail->Password = $email_config['smtp_password'];
    $mail->SMTPSecure = $email_config['smtp_secure'];
    $mail->Port = $email_config['smtp_port'];
    $mail->CharSet = 'UTF-8';
    
    // Set sender
    $mail->setFrom($email_config['from_email'], $email_config['from_name']);
    
    // Add recipient (use a test email)
    $test_email = 'test@example.com'; // Change this to your email
    $mail->addAddress($test_email);
    
    // Set email content
    $mail->Subject = 'Test Email - BladeX Debug';
    $mail->Body = '<h1>Test Email</h1><p>This is a test email to debug SMTP issues.</p>';
    $mail->isHTML(true);
    
    echo "<div class='info'>Attempting to send test email to: $test_email</div>";
    
    try {
        $result = $mail->send();
        if ($result) {
            echo "<div class='success'>✅ Email sent successfully according to PHPMailer</div>";
        } else {
            echo "<div class='error'>❌ PHPMailer returned false</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ PHPMailer Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>2. Alternative SMTP Settings Test</h2>";
    
    // Test alternative SMTP settings
    $alternative_settings = [
        [
            'name' => 'Port 587 (STARTTLS)',
            'port' => 587,
            'secure' => 'tls'
        ],
        [
            'name' => 'Port 25 (No encryption)',
            'port' => 25,
            'secure' => ''
        ],
        [
            'name' => 'Port 2525 (Alternative)',
            'port' => 2525,
            'secure' => 'tls'
        ]
    ];
    
    foreach ($alternative_settings as $setting) {
        echo "<div class='info'>Testing {$setting['name']}...</div>";
        
        $test_mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $test_mail->SMTPDebug = 1;
        $test_mail->Debugoutput = function($str, $level) {
            echo "<div class='smtp-log'>$str</div>";
        };
        
        $test_mail->isSMTP();
        $test_mail->Host = $email_config['smtp_host'];
        $test_mail->SMTPAuth = true;
        $test_mail->Username = $email_config['smtp_username'];
        $test_mail->Password = $email_config['smtp_password'];
        $test_mail->SMTPSecure = $setting['secure'];
        $test_mail->Port = $setting['port'];
        $test_mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $test_mail->addAddress($test_email);
        $test_mail->Subject = "Test - {$setting['name']}";
        $test_mail->Body = "Testing {$setting['name']}";
        
        try {
            $result = $test_mail->send();
            if ($result) {
                echo "<div class='success'>✅ {$setting['name']} - Success</div>";
            } else {
                echo "<div class='error'>❌ {$setting['name']} - Failed</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ {$setting['name']} - Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        
        echo "<br>";
    }
    
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>3. Common Hosting Email Issues</h2>";
    echo "<div class='info'>";
    echo "<p><strong>If emails aren't being delivered, try these solutions:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Check hosting control panel:</strong> Look for 'Email' or 'Mail' settings</li>";
    echo "<li><strong>Verify email account exists:</strong> Make sure BladeX@customprojects.shawa.com.tr exists</li>";
    echo "<li><strong>Check email sending limits:</strong> Some hosts limit emails per hour/day</li>";
    echo "<li><strong>Try different SMTP settings:</strong> Use the alternative settings above</li>";
    echo "<li><strong>Contact hosting support:</strong> Ask about SMTP email sending</li>";
    echo "<li><strong>Check email logs:</strong> Look for bounce messages or delivery failures</li>";
    echo "</ol>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>4. Quick Fixes to Try</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Immediate solutions:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Change SMTP port:</strong> Try 587 instead of 465</li>";
    echo "<li><strong>Change encryption:</strong> Try 'tls' instead of 'ssl'</li>";
    echo "<li><strong>Use different email:</strong> Try a Gmail or other external email</li>";
    echo "<li><strong>Check hosting email settings:</strong> Some require specific configurations</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>Critical Error</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div class='debug-section'>";
echo "<h2>5. Next Steps</h2>";
echo "<div class='info'>";
echo "<p><strong>Based on the debug results:</strong></p>";
echo "<ul>";
echo "<li>If SMTP conversation shows errors: Fix the specific error</li>";
echo "<li>If authentication fails: Check username/password</li>";
echo "<li>If connection fails: Try alternative ports/encryption</li>";
echo "<li>If everything looks good but no delivery: Check hosting email settings</li>";
echo "</ul>";
echo "<p><strong>Change the test email address above to your real email and run this debug again.</strong></p>";
echo "</div>";
echo "</div>";
?> 