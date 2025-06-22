<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$auth = new Auth();
$auth->requireRole(['admin']);

$db = new Database();
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_config':
                $key = $_POST['key'] ?? '';
                $value = $_POST['value'] ?? '';
                
                if (!empty($key)) {
                    $result = $db->updateEmailConfig($key, $value);
                    if ($result['success']) {
                        $message = $result['message'];
                        $messageType = 'success';
                    } else {
                        $message = $result['message'];
                        $messageType = 'error';
                    }
                }
                break;
                
            case 'test_config':
                $result = $db->testEmailConfig();
                if ($result['success']) {
                    $message = $result['message'];
                    $messageType = 'success';
                } else {
                    $message = $result['message'];
                    $messageType = 'error';
                }
                break;
        }
    }
}

// Get current email configuration
$emailConfig = $db->getAllEmailConfig();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Configuration - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .config-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        .config-item.sensitive {
            border-left-color: #dc3545;
        }
        .config-item .form-control {
            font-family: monospace;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        .password-toggle:hover {
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="appointments.php">
                                <i class="fas fa-calendar"></i> Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="services.php">
                                <i class="fas fa-cut"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="email_config.php">
                                <i class="fas fa-envelope"></i> Email Config
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="activity_log.php">
                                <i class="fas fa-history"></i> Activity Log
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-envelope"></i> Email Configuration
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="test_config">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-test-tube"></i> Test Configuration
                            </button>
                        </form>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cog"></i> SMTP Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($emailConfig)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i> No email configuration found. Please run the SQL script to create the email_config table.
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($emailConfig as $config): ?>
                                        <div class="config-item <?php echo in_array($config['key'], ['smtp_password']) ? 'sensitive' : ''; ?>">
                                            <div class="row align-items-center">
                                                <div class="col-md-3">
                                                    <label class="form-label fw-bold">
                                                        <?php echo ucwords(str_replace('_', ' ', $config['key'])); ?>
                                                    </label>
                                                    <small class="text-muted d-block">
                                                        <?php echo htmlspecialchars($config['description']); ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-7">
                                                    <form method="POST" class="update-form">
                                                        <input type="hidden" name="action" value="update_config">
                                                        <input type="hidden" name="key" value="<?php echo htmlspecialchars($config['key']); ?>">
                                                        
                                                        <?php if ($config['key'] === 'smtp_password'): ?>
                                                            <div class="password-field">
                                                                <input type="password" 
                                                                       name="value" 
                                                                       value="<?php echo htmlspecialchars($config['value']); ?>" 
                                                                       class="form-control password-input"
                                                                       placeholder="Enter password">
                                                                <button type="button" class="password-toggle" onclick="togglePassword(this)">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                            </div>
                                                        <?php else: ?>
                                                            <input type="text" 
                                                                   name="value" 
                                                                   value="<?php echo htmlspecialchars($config['value']); ?>" 
                                                                   class="form-control"
                                                                   placeholder="Enter value">
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="button" class="btn btn-primary btn-sm save-btn" onclick="saveConfig(this)">
                                                        <i class="fas fa-save"></i> Save
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle"></i> Configuration Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-shield-alt"></i> Security Features:</h6>
                                    <ul class="mb-0">
                                        <li>Email credentials are stored securely in the database</li>
                                        <li>Password fields are masked and encrypted</li>
                                        <li>All configuration changes are logged in activity log</li>
                                        <li>Test functionality to verify SMTP connection</li>
                                    </ul>
                                </div>
                                
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Important Notes:</h6>
                                    <ul class="mb-0">
                                        <li>Make sure to test the configuration after making changes</li>
                                        <li>For cPanel hosting, use your domain's SMTP server</li>
                                        <li>Port 465 typically uses SSL, Port 587 typically uses TLS</li>
                                        <li>Keep your SMTP credentials secure and don't share them</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveConfig(button) {
            const form = button.closest('.config-item').querySelector('.update-form');
            const formData = new FormData(form);
            
            // Show loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            button.disabled = true;
            
            fetch('email_config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Reload page to show updated values
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                button.innerHTML = '<i class="fas fa-save"></i> Save';
                button.disabled = false;
                alert('Error saving configuration');
            });
        }
        
        function togglePassword(button) {
            const input = button.parentElement.querySelector('.password-input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
        // Auto-save on Enter key
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const form = e.target.closest('.update-form');
                if (form) {
                    const saveBtn = form.closest('.config-item').querySelector('.save-btn');
                    saveConfig(saveBtn);
                }
            }
        });
    </script>
</body>
</html> 