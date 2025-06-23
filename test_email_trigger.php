<?php
// test_email_trigger.php - Test email sending functionality
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/email_helper.php';

$to = isset($_GET['to']) ? $_GET['to'] : 'your@email.com'; // Change to your test email
$subject = 'Test Email Trigger';
$body = '<h1>This is a test email from your Barbershop app</h1><p>If you received this, email sending is working!</p>';

$emailHelper = new EmailHelper();
$result = $emailHelper->send($to, $subject, $body);

if ($result === true) {
    echo "<div style='color:green;'>✅ Email sent successfully to $to</div>";
} else {
    echo "<div style='color:red;'>❌ Failed to send email: $result</div>";
} 