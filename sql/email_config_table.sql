-- Email Configuration Table
-- This table stores email SMTP settings securely in the database

CREATE TABLE IF NOT EXISTS email_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(64) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default email configuration
INSERT IGNORE INTO email_config (setting_key, setting_value, description) VALUES
('smtp_host', 'customprojects.shawa.com.tr', 'SMTP server hostname'),
('smtp_port', '465', 'SMTP server port'),
('smtp_username', 'BladeX@customprojects.shawa.com.tr', 'SMTP username'),
('smtp_password', 'Hes0o@981', 'SMTP password'),
('from_email', 'bladex@customprojects.shawa.com.tr', 'From email address'),
('from_name', 'BarberShop Notifications', 'From name'),
('smtp_secure', 'ssl', 'SMTP security protocol (ssl/tls)'),
('reply_to', 'bladex@customprojects.shawa.com.tr', 'Reply-to email address'),
('return_path', 'bladex@customprojects.shawa.com.tr', 'Return path email address');

-- Create index for faster lookups
CREATE INDEX idx_email_config_key ON email_config(setting_key); 