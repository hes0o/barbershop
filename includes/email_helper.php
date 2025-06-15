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