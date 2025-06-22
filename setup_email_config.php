<?php
/**
 * Email Configuration Setup Script
 * 
 * This script creates the email_config table and populates it with default values.
 * Run this script once to set up the database-based email configuration.
 */

require_once 'includes/db.php';

echo "<h2>Email Configuration Setup</h2>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create email_config table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS email_config (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(64) UNIQUE NOT NULL,
        setting_value TEXT,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($createTableSQL)) {
        echo "<p style='color: green;'>✓ Email configuration table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
    }
    
    // Create index
    $indexSQL = "CREATE INDEX IF NOT EXISTS idx_email_config_key ON email_config(setting_key)";
    if ($conn->query($indexSQL)) {
        echo "<p style='color: green;'>✓ Index created successfully</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Index creation warning: " . $conn->error . "</p>";
    }
    
    // Insert default configuration
    $defaultConfig = [
        ['smtp_host', 'customprojects.shawa.com.tr', 'SMTP server hostname'],
        ['smtp_port', '465', 'SMTP server port'],
        ['smtp_username', 'BladeX@customprojects.shawa.com.tr', 'SMTP username'],
        ['smtp_password', 'Hes0o@981', 'SMTP password'],
        ['from_email', 'bladex@customprojects.shawa.com.tr', 'From email address'],
        ['from_name', 'BarberShop Notifications', 'From name'],
        ['smtp_secure', 'ssl', 'SMTP security protocol (ssl/tls)'],
        ['reply_to', 'bladex@customprojects.shawa.com.tr', 'Reply-to email address'],
        ['return_path', 'bladex@customprojects.shawa.com.tr', 'Return path email address']
    ];
    
    $inserted = 0;
    $updated = 0;
    
    foreach ($defaultConfig as $config) {
        $key = $config[0];
        $value = $config[1];
        $description = $config[2];
        
        // Check if setting exists
        $checkStmt = $conn->prepare("SELECT id FROM email_config WHERE setting_key = ?");
        $checkStmt->bind_param("s", $key);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing
            $updateStmt = $conn->prepare("UPDATE email_config SET setting_value = ?, description = ? WHERE setting_key = ?");
            $updateStmt->bind_param("sss", $value, $description, $key);
            if ($updateStmt->execute()) {
                $updated++;
                echo "<p style='color: blue;'>✓ Updated: $key</p>";
            } else {
                echo "<p style='color: red;'>✗ Error updating $key: " . $updateStmt->error . "</p>";
            }
        } else {
            // Insert new
            $insertStmt = $conn->prepare("INSERT INTO email_config (setting_key, setting_value, description) VALUES (?, ?, ?)");
            $insertStmt->bind_param("sss", $key, $value, $description);
            if ($insertStmt->execute()) {
                $inserted++;
                echo "<p style='color: green;'>✓ Inserted: $key</p>";
            } else {
                echo "<p style='color: red;'>✗ Error inserting $key: " . $insertStmt->error . "</p>";
            }
        }
    }
    
    echo "<hr>";
    echo "<h3>Setup Summary:</h3>";
    echo "<p>• Table created: email_config</p>";
    echo "<p>• Settings inserted: $inserted</p>";
    echo "<p>• Settings updated: $updated</p>";
    
    // Test the configuration
    echo "<hr>";
    echo "<h3>Testing Configuration:</h3>";
    
    $testConfig = $db->getEmailConfig();
    if (!empty($testConfig)) {
        echo "<p style='color: green;'>✓ Configuration loaded successfully from database</p>";
        echo "<p>Found " . count($testConfig) . " configuration settings</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to load configuration from database</p>";
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='admin/email_config.php'>Admin → Email Config</a> to manage your email settings</li>";
    echo "<li>Update the SMTP credentials with your actual values</li>";
    echo "<li>Test the email configuration using the 'Test Configuration' button</li>";
    echo "<li>You can now safely remove or update your old email_config.php file</li>";
    echo "</ol>";
    
    echo "<p style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
    echo "<strong>Security Note:</strong> Your email credentials are now stored securely in the database. ";
    echo "You can manage them through the admin interface without touching any files.";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #555; margin-top: 20px; }
hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
</style> 