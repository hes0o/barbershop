<?php
require_once __DIR__ . '/includes/email_helper.php';

$email = new EmailHelper();
$result = $email->send(
    'hassanshawa30@gmail.com', // <-- Replace with your real email to receive the test
    'Test Email from BarberShop',
    '<h1>This is a test email</h1><p>If you see this, your SMTP is working!</p>',
    'This is a test email. If you see this, your SMTP is working!'
);

if ($result === true) {
    echo 'Email sent successfully!';
} else {
    echo 'Error sending email: ' . htmlspecialchars($result);
}