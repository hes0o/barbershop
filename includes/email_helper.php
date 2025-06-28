<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailHelper {
    private $config;
    private $mailer;

    public function __construct() {
        $this->config = require __DIR__ . '/email_config.php';
        $this->mailer = new PHPMailer(true);
        $this->setup();
    }

    private function setup() {
        $c = $this->config;
        $m = $this->mailer;
        $m->isSMTP();
        $m->Host = $c['smtp_host'];
        $m->SMTPAuth = true;
        $m->Username = $c['smtp_username'];
        $m->Password = $c['smtp_password'];
        $m->SMTPSecure = $c['smtp_secure'];
        $m->Port = $c['smtp_port'];
        $m->CharSet = 'UTF-8';
        $m->setFrom($c['from_email'], $c['from_name']);
    }

    /**
     * Load a template file
     * @param string $template_name Template name (without .html extension)
     * @return string|false Template content or false on failure
     */
    public function loadTemplate($template_name) {
        $template_path = __DIR__ . '/../email_templates/' . $template_name . '.html';
        if (file_exists($template_path)) {
            return file_get_contents($template_path);
        }
        return false;
    }

    /**
     * Process a template with variables
     * @param string $template_name Template name (without .html extension)
     * @param array $variables Variables to replace in template
     * @return string|false Processed template or false on failure
     */
    public function processTemplate($template_name, $variables) {
        $base_template = $this->loadTemplate('base');
        $content_template = $this->loadTemplate($template_name);
        
        if (!$base_template || !$content_template) {
            return false;
        }
        
        // Replace variables in content template
        foreach ($variables as $key => $value) {
            $content_template = str_replace('{{' . $key . '}}', $value, $content_template);
        }
        
        // Replace subject and content in base template
        $subject = $variables['subject'] ?? 'Email from BladeX Barbershop';
        $html = str_replace(['{{subject}}', '{{content}}'], [$subject, $content_template], $base_template);
        
        return $html;
    }

    /**
     * Send an email
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body HTML body
     * @param string|null $altBody Plain text body (optional)
     * @return bool|string True on success, error message on failure
     */
    public function send($to, $subject, $body, $altBody = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->isHTML(true);
            if ($altBody) {
                $this->mailer->AltBody = $altBody;
            }
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
} 