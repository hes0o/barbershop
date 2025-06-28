<?php
require_once __DIR__ . '/email_helper.php';
require_once __DIR__ . '/db.php';

class EmailService {
    private $emailHelper;
    private $db;
    
    public function __construct() {
        $this->emailHelper = new EmailHelper();
        $this->db = new Database();
    }
    
    /**
     * Load and process email template
     */
    private function loadTemplate($template, $vars) {
        $base = file_get_contents(__DIR__ . '/../email_templates/base.html');
        $content = file_get_contents(__DIR__ . '/../email_templates/' . $template);
        
        // Replace variables in content
        foreach ($vars as $k => $v) {
            $content = str_replace('{{' . $k . '}}', $v, $content);
        }
        
        // Replace subject and content in base template
        $html = str_replace(['{{subject}}', '{{content}}'], [$vars['subject'], $content], $base);
        return $html;
    }
    
    /**
     * Send notification email to barber when customer books appointment
     * (For testing with individual parameters)
     */
    public function sendBarberNotification($barber_email, $customer_name, $appointment_date, $appointment_time, $service_name, $customer_email, $customer_phone) {
        try {
            $vars = [
                'subject' => 'New Appointment Request - BladeX',
                'greeting' => 'Hello Barber,',
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'service_name' => $service_name,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone
            ];
            
            $body = $this->loadTemplate('barber_notification.html', $vars);
            return $this->emailHelper->send($barber_email, $vars['subject'], $body);
        } catch (Exception $e) {
            error_log("Error sending barber notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send confirmation email to customer when barber approves appointment
     * (For testing with individual parameters)
     */
    public function sendAppointmentConfirmation($customer_email, $customer_name, $appointment_date, $appointment_time, $service_name, $barber_name) {
        try {
            $vars = [
                'subject' => 'Appointment Confirmed - BladeX',
                'greeting' => 'Hello ' . $customer_name . ',',
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'service_name' => $service_name,
                'barber_name' => $barber_name
            ];
            
            $body = $this->loadTemplate('appointment_confirmation.html', $vars);
            return $this->emailHelper->send($customer_email, $vars['subject'], $body);
        } catch (Exception $e) {
            error_log("Error sending customer confirmation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification email to barber when customer books appointment
     * (Original method with appointment_id)
     */
    public function sendBarberNotificationById($appointment_id) {
        try {
            $appointment = $this->db->getAppointmentById($appointment_id);
            if (!$appointment) {
                throw new Exception("Appointment not found");
            }
            
            $customer = $this->db->getUserById($appointment['user_id']);
            $barber = $this->db->getUserById($appointment['barber_id']);
            $service = $this->db->getServiceById($appointment['service_id']);
            
            if (!$customer || !$barber || !$service) {
                throw new Exception("Required data not found");
            }
            
            $appointment_date = date('F j, Y', strtotime($appointment['appointment_date']));
            $appointment_time = date('g:i A', strtotime($appointment['appointment_time']));
            
            $vars = [
                'subject' => 'New Appointment Request - BladeX',
                'greeting' => 'Hello ' . $barber['first_name'] . ' ' . $barber['last_name'] . ',',
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'service_name' => $service['name'],
                'customer_name' => $customer['first_name'] . ' ' . $customer['last_name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'] ?? 'Not provided'
            ];
            
            $body = $this->loadTemplate('barber_notification.html', $vars);
            return $this->emailHelper->send($barber['email'], $vars['subject'], $body);
        } catch (Exception $e) {
            error_log("Error sending barber notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send confirmation email to customer when barber approves appointment
     * (Original method with appointment_id)
     */
    public function sendCustomerConfirmationById($appointment_id) {
        try {
            $appointment = $this->db->getAppointmentById($appointment_id);
            if (!$appointment) {
                throw new Exception("Appointment not found");
            }
            
            $customer = $this->db->getUserById($appointment['user_id']);
            $barber = $this->db->getUserById($appointment['barber_id']);
            $service = $this->db->getServiceById($appointment['service_id']);
            
            if (!$customer || !$barber || !$service) {
                throw new Exception("Required data not found");
            }
            
            $appointment_date = date('F j, Y', strtotime($appointment['appointment_date']));
            $appointment_time = date('g:i A', strtotime($appointment['appointment_time']));
            
            $vars = [
                'subject' => 'Appointment Confirmed - BladeX',
                'greeting' => 'Hello ' . $customer['first_name'] . ' ' . $customer['last_name'] . ',',
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time,
                'service_name' => $service['name'],
                'barber_name' => $barber['first_name'] . ' ' . $barber['last_name']
            ];
            
            $body = $this->loadTemplate('appointment_confirmation.html', $vars);
            return $this->emailHelper->send($customer['email'], $vars['subject'], $body);
        } catch (Exception $e) {
            error_log("Error sending customer confirmation: " . $e->getMessage());
            return false;
        }
    }
}
?> 