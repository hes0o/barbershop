<?php
/**
 * Email Tester - Test email functionality with custom data
 */

require_once 'includes/email_helper.php';
require_once 'includes/email_service.php';

// Handle form submission
$test_result = '';
$test_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email_type = $_POST['email_type'] ?? '';
        $test_email = $_POST['test_email'] ?? '';
        $customer_name = $_POST['customer_name'] ?? 'Test Customer';
        $barber_name = $_POST['barber_name'] ?? 'Test Barber';
        $appointment_date = $_POST['appointment_date'] ?? 'December 15, 2024';
        $appointment_time = $_POST['appointment_time'] ?? '2:00 PM';
        $service_name = $_POST['service_name'] ?? 'Test Haircut';
        $customer_email = $_POST['customer_email'] ?? 'test@example.com';
        $customer_phone = $_POST['customer_phone'] ?? '+1 (555) 123-4567';

        if (empty($email_type) || empty($test_email)) {
            throw new Exception('Please select an email type and enter a test email address.');
        }

        $emailService = new EmailService();
        
        if ($email_type === 'barber_notification') {
            // Test barber notification
            $result = $emailService->sendBarberNotification(
                $test_email, // barber email
                $customer_name,
                $appointment_date,
                $appointment_time,
                $service_name,
                $customer_email,
                $customer_phone
            );
            
            if ($result) {
                $test_result = "✅ Barber notification email sent successfully to: $test_email";
            } else {
                $test_error = "❌ Failed to send barber notification email";
            }
            
        } elseif ($email_type === 'appointment_confirmation') {
            // Test customer confirmation
            $result = $emailService->sendAppointmentConfirmation(
                $test_email, // customer email
                $customer_name,
                $appointment_date,
                $appointment_time,
                $service_name,
                $barber_name
            );
            
            if ($result) {
                $test_result = "✅ Appointment confirmation email sent successfully to: $test_email";
            } else {
                $test_error = "❌ Failed to send appointment confirmation email";
            }
        }
        
    } catch (Exception $e) {
        $test_error = "❌ Error: " . $e->getMessage();
    }
}

// Get current date for default value
$current_date = date('F j, Y', strtotime('+1 day'));
$current_time = date('g:i A', strtotime('+2 hours'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Tester - BladeX Barbershop</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .content {
            padding: 40px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        select, input, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        select:focus, input:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .btn:active {
            transform: translateY(0);
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        .result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #1565c0;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #0d47a1;
        }
        .info-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .template-preview {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .template-preview h3 {
            margin-top: 0;
            color: #495057;
        }
        .template-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .template-links a {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        .template-links a:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Tester</h1>
            <p>Test your barbershop email system with custom data</p>
        </div>
        
        <div class="content">
            <div class="info-box">
                <h3>🎯 What This Tester Does</h3>
                <ul>
                    <li><strong>Barber Notification:</strong> Sends notification to barber when customer books</li>
                    <li><strong>Appointment Confirmation:</strong> Sends confirmation to customer when barber approves</li>
                    <li><strong>Custom Data:</strong> Test with your own appointment details</li>
                    <li><strong>Real Emails:</strong> Actually sends emails to test addresses</li>
                </ul>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email_type">📧 Email Type to Test:</label>
                    <select name="email_type" id="email_type" required>
                        <option value="">Select email type...</option>
                        <option value="barber_notification">Barber Notification (when customer books)</option>
                        <option value="appointment_confirmation">Appointment Confirmation (when barber approves)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="test_email">📮 Test Email Address:</label>
                    <input type="email" name="test_email" id="test_email" placeholder="Enter email address to receive test" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name">👤 Customer Name:</label>
                        <input type="text" name="customer_name" id="customer_name" value="Test Customer" required>
                    </div>
                    <div class="form-group">
                        <label for="barber_name">✂️ Barber Name:</label>
                        <input type="text" name="barber_name" id="barber_name" value="Omar Serjavi" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">📅 Appointment Date:</label>
                        <input type="text" name="appointment_date" id="appointment_date" value="<?php echo $current_date; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">🕐 Appointment Time:</label>
                        <input type="text" name="appointment_time" id="appointment_time" value="<?php echo $current_time; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="service_name">💇 Service Name:</label>
                        <input type="text" name="service_name" id="service_name" value="Premium Haircut" required>
                    </div>
                    <div class="form-group">
                        <label for="customer_email">📧 Customer Email:</label>
                        <input type="email" name="customer_email" id="customer_email" value="customer@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="customer_phone">📞 Customer Phone:</label>
                    <input type="text" name="customer_phone" id="customer_phone" value="+1 (555) 123-4567" required>
                </div>

                <button type="submit" class="btn">🚀 Send Test Email</button>
            </form>

            <?php if ($test_result): ?>
                <div class="result success">
                    <?php echo $test_result; ?>
                </div>
            <?php endif; ?>

            <?php if ($test_error): ?>
                <div class="result error">
                    <?php echo $test_error; ?>
                </div>
            <?php endif; ?>

            <div class="template-preview">
                <h3>👀 Preview Email Templates</h3>
                <p>See how your emails will look before sending:</p>
                <div class="template-links">
                    <a href="preview_template.php?template=barber_notification" target="_blank">👨‍💼 Barber Notification Preview</a>
                    <a href="preview_template.php?template=appointment_confirmation" target="_blank">✅ Customer Confirmation Preview</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill current date and time
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
            const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
            
            const formattedDate = tomorrow.toLocaleDateString('en-US', dateOptions);
            const formattedTime = now.toLocaleTimeString('en-US', timeOptions);
            
            document.getElementById('appointment_date').value = formattedDate;
            document.getElementById('appointment_time').value = formattedTime;
        });
    </script>
</body>
</html> 