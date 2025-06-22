<?php
// Email configuration from database
// This file now reads email settings from the database for better security

require_once 'db.php';

function getEmailConfig() {
    $db = new Database();
    $config = $db->getEmailConfig();
    
    if (empty($config)) {
        // Fallback to default values if database config is not available
        return [
            'smtp_host' => 'customprojects.shawa.com.tr',
            'smtp_port' => 465,
            'smtp_username' => 'BladeX@customprojects.shawa.com.tr',
            'smtp_password' => 'Hes0o@981',
            'from_email' => 'bladex@customprojects.shawa.com.tr',
            'from_name' => 'BarberShop Notifications',
            'smtp_secure' => 'ssl',
            'reply_to' => 'bladex@customprojects.shawa.com.tr',
            'return_path' => 'bladex@customprojects.shawa.com.tr'
        ];
    }
    
    // Convert database format to simple key-value array
    $emailConfig = [];
    foreach ($config as $key => $data) {
        $emailConfig[$key] = $data['value'];
    }
    
    return $emailConfig;
}

// Return the configuration array
return getEmailConfig(); 