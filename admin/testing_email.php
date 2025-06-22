<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/auth.php';
require_once '../includes/email_helper.php';
require_once '../includes/header.php';

// Initialize Auth
$auth = new Auth();

// Check if user is admin
if (!$auth->isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emails = isset($_POST['emails']) ? trim($_POST['emails']) : '';
    $test_type = isset($_POST['test_type']) ? $_POST['test_type'] : 'simple';
    
    if (empty($emails)) {
        $error = 'Please enter at least one email address';
    } else {
        $email_list = array_map('trim', explode(',', $emails));
        $valid_emails = [];
        $invalid_emails = [];
        
        // Validate emails
        foreach ($email_list as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid_emails[] = $email;
            } else {
                $invalid_emails[] = $email;
            }
        }
        
        if (empty($valid_emails)) {
            $error = 'No valid email addresses provided';
        } else {
            $email = new EmailHelper();
            $success_count = 0;
            $failed_count = 0;
            $failed_emails = [];
            
            // Prepare test email content based on type
            switch ($test_type) {
                case 'html':
                    $subject = 'BladeX Email Test - HTML Format';
                    $body = '
                        <h1>BladeX Email Test</h1>
                        <p>This is a test email to verify HTML email delivery.</p>
                        <p>If you can see this formatted message, HTML emails are working correctly.</p>
                        <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
                    ';
                    $altBody = 'This is a test email to verify email delivery. Sent at: ' . date('Y-m-d H:i:s');
                    break;
                    
                case 'appointment':
                    $subject = 'BladeX Email Test - Appointment Format';
                    $body = '
                        <h1>BladeX Appointment Test</h1>
                        <p>This is a test appointment notification email.</p>
                        <p>Appointment Details:</p>
                        <ul>
                            <li>Date: ' . date('F j, Y') . '</li>
                            <li>Time: ' . date('g:i A') . '</li>
                            <li>Service: Test Haircut</li>
                            <li>Barber: Test Barber</li>
                        </ul>
                        <p>Sent at: ' . date('Y-m-d H:i:s') . '</p>
                    ';
                    $altBody = 'This is a test appointment notification email. Sent at: ' . date('Y-m-d H:i:s');
                    break;
                    
                default:
                    $subject = 'BladeX Email Test - Simple Format';
                    $body = '<p>This is a test email to verify email delivery. Sent at: ' . date('Y-m-d H:i:s') . '</p>';
                    $altBody = 'This is a test email to verify email delivery. Sent at: ' . date('Y-m-d H:i:s');
            }
            
            // Send test emails
            foreach ($valid_emails as $to_email) {
                $result = $email->send($to_email, $subject, $body, $altBody);
                if ($result === true) {
                    $success_count++;
                } else {
                    $failed_count++;
                    $failed_emails[] = $to_email . ' (' . $result . ')';
                }
            }
            
            // Prepare result message
            $message = "Test Results:\n";
            $message .= "- Successfully sent: $success_count\n";
            $message .= "- Failed to send: $failed_count\n";
            
            if (!empty($invalid_emails)) {
                $message .= "\nInvalid email addresses:\n";
                $message .= implode("\n", $invalid_emails);
            }
            
            if (!empty($failed_emails)) {
                $message .= "\nFailed emails with errors:\n";
                $message .= implode("\n", $failed_emails);
            }
        }
    }
}
?>

<div class="container mt-4">
    <h2>Email Delivery Test</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($message): ?>
        <div class="alert alert-info">
            <pre><?php echo htmlspecialchars($message); ?></pre>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="emails" class="form-label">Email Addresses (comma-separated)</label>
                    <textarea class="form-control" id="emails" name="emails" rows="3" required 
                              placeholder="Enter email addresses separated by commas"><?php echo isset($_POST['emails']) ? htmlspecialchars($_POST['emails']) : ''; ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="test_type" class="form-label">Test Type</label>
                    <select class="form-select" id="test_type" name="test_type">
                        <option value="simple" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] === 'simple') ? 'selected' : ''; ?>>Simple Text</option>
                        <option value="html" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] === 'html') ? 'selected' : ''; ?>>HTML Format</option>
                        <option value="appointment" <?php echo (isset($_POST['test_type']) && $_POST['test_type'] === 'appointment') ? 'selected' : ''; ?>>Appointment Format</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Send Test Emails</button>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Tips for Email Deliverability</h5>
            <ul>
                <li>Check spam folders for test emails</li>
                <li>Verify SPF, DKIM, and DMARC records are properly configured</li>
                <li>Ensure your SMTP settings are correct</li>
                <li>Monitor email bounce rates and delivery statistics</li>
                <li>Keep your email templates clean and professional</li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 