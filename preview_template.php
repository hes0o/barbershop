<?php
// Load base template
$base = file_get_contents(__DIR__ . '/email_templates/base.html');

// Get template name from URL parameter
$template = $_GET['template'] ?? 'barber_notification';

// Define sample data for each template
$sample_data = [
    'barber_notification' => [
        'greeting' => 'Hello Omar Serjavi,',
        'appointment_date' => 'December 15, 2024',
        'appointment_time' => '2:00 PM',
        'service_name' => 'Classic Haircut',
        'customer_name' => 'John Doe',
        'customer_email' => 'john.doe@example.com',
        'customer_phone' => '+1 (555) 123-4567'
    ],
    'appointment_confirmation' => [
        'greeting' => 'Hello John Doe,',
        'appointment_date' => 'December 15, 2024',
        'appointment_time' => '2:00 PM',
        'service_name' => 'Classic Haircut',
        'barber_name' => 'Omar Serjavi'
    ]
];

// Get the template data
$data = $sample_data[$template] ?? $sample_data['barber_notification'];

// Load content template
$template_file = __DIR__ . '/email_templates/' . $template . '.html';
if (!file_exists($template_file)) {
    die("Template file not found: $template");
}

$content = file_get_contents($template_file);

// Replace placeholders in content
foreach ($data as $key => $value) {
    $content = str_replace('{{' . $key . '}}', $value, $content);
}

// Set subject based on template
$subjects = [
    'barber_notification' => 'New Appointment Request - BladeX',
    'appointment_confirmation' => 'Appointment Confirmed - BladeX'
];

$subject = $subjects[$template] ?? 'Email Preview';

// Replace placeholders in base
$html = str_replace(
    ['{{subject}}', '{{content}}'],
    [$subject, $content],
    $base
);

// Output the result
header('Content-Type: text/html; charset=UTF-8');
echo $html; 