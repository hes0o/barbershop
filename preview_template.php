<?php
// Load base template
$base = file_get_contents(__DIR__ . '/email_templates/base.html');
// Load content template
$content = file_get_contents(__DIR__ . '/email_templates/appointment_confirmation.html');

// Replace placeholders in content
$content = str_replace(
    ['{{greeting}}', '{{appointment_date}}', '{{appointment_time}}', '{{service_name}}', '{{barber_name}}'],
    ['Hello John Doe,', '2024-06-15', '14:00', 'Classic Haircut', 'Jane Barber'],
    $content
);

// Replace placeholders in base
$html = str_replace(
    ['{{subject}}', '{{content}}'],
    ['Appointment Confirmation', $content],
    $base
);

// Output the result
header('Content-Type: text/html; charset=UTF-8');
echo $html; 