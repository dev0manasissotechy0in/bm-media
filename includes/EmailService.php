<?php
/**
 * Email Service with Multiple SMTP Configurations
 * Supports different SMTP servers for Auth, Newsletter, and Contact
 */

class EmailService {
    private $db;
    private $smtp_configs = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfigs();
    }
    
    /**
     * Load all SMTP configurations
     */
    private function loadConfigs() {
        $purposes = ['auth', 'newsletter', 'contact'];
        
        foreach ($purposes as $purpose) {
            $config = $this->db->fetchAll(
                "SELECT setting_key, setting_value FROM settings WHERE smtp_purpose = ?",
                [$purpose]
            );
            
            $settings = [];
            foreach ($config as $setting) {
                $key = str_replace($purpose . '_smtp_', '', $setting['setting_key']);
                $settings[$key] = $setting['setting_value'];
            }
            
            $this->smtp_configs[$purpose] = $settings;
        }
    }
    
    /**
     * Send email using specified SMTP configuration
     * 
     * @param string $purpose 'auth', 'newsletter', or 'contact'
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $toName Recipient name (optional)
     * @return bool Success status
     */
    public function sendEmail($purpose, $to, $subject, $body, $toName = '') {
        $config = $this->smtp_configs[$purpose] ?? null;
        
        if (!$config || empty($config['enabled']) || $config['enabled'] != '1') {
            error_log("EmailService Error: SMTP not enabled for purpose: $purpose");
            return false;
        }
        
        // Validate required config fields
        if (empty($config['host']) || empty($config['username']) || empty($config['password'])) {
            error_log("EmailService Error: Missing required SMTP configuration for purpose: $purpose");
            return false;
        }
        
        try {
            return $this->sendWithPHPMailer($config, $to, $subject, $body, $toName);
        } catch (Exception $e) {
            error_log("EmailService Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private function sendWithPHPMailer($config, $to, $subject, $body, $toName) {
        // Load PHPMailer via Composer autoloader
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['username'];
            $mail->Password = $config['password'];
            
            // Set encryption based on port if not specified
            if (!empty($config['port'])) {
                if ($config['port'] == 465) {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
                } else {
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; // TLS
                }
            } else {
                $mail->SMTPSecure = $config['encryption'] ?? 'tls';
            }
            
            $mail->Port = $config['port'];
            $mail->CharSet = 'UTF-8';
            
            // Timeout settings
            $mail->Timeout = 30;
            $mail->SMTPKeepAlive = false;
            
            // Enable debug output (set to 0 in production)
            $mail->SMTPDebug = 0; // 0 = off, 1 = client, 2 = client and server
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP Debug [$level]: $str");
            };
            
            // Recipients
            $mail->setFrom($config['from_email'], $config['from_name']);
            $mail->addAddress($to, $toName);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $mail->ErrorInfo);
            error_log("Exception Message: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send OTP for authentication
     */
    public function sendOTP($email, $otp, $purpose = 'login') {
        $subjects = [
            'login' => 'Your Login OTP Code',
            'registration' => 'Verify Your Email',
            'password_reset' => 'Password Reset Code'
        ];
        
        $subject = $subjects[$purpose] ?? 'Your OTP Code';
        
        $body = $this->getOTPTemplate($otp, $purpose);
        
        return $this->sendEmail('auth', $email, $subject, $body);
    }
    
    /**
     * Send newsletter
     */
    public function sendNewsletter($subscriber, $campaign) {
        $tracking_token = bin2hex(random_bytes(16));
        
        // Save tracking token
        $this->db->insert('newsletter_tracking', [
            'campaign_id' => $campaign['id'],
            'subscriber_id' => $subscriber['id'],
            'tracking_token' => $tracking_token
        ]);
        
        // Replace tracking placeholders
        $body = $this->prepareNewsletterBody($campaign['content'], $subscriber, $tracking_token);
        
        return $this->sendEmail('newsletter', $subscriber['email'], $campaign['subject'], $body, $subscriber['name']);
    }
    
    /**
     * Send contact form notification
     */
    public function sendContactNotification($contactData) {
        $adminEmail = $this->getAdminEmail();
        
        $subject = "New Contact Form Submission: " . $contactData['subject'];
        $body = $this->getContactTemplate($contactData);
        
        return $this->sendEmail('contact', $adminEmail, $subject, $body);
    }
    
    /**
     * Get admin email from settings
     */
    private function getAdminEmail() {
        $setting = $this->db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'contact_email'");
        return $setting['setting_value'] ?? 'admin@example.com';
    }
    
    /**
     * OTP Email Template
     */
    private function getOTPTemplate($otp, $purpose) {
        $messages = [
            'login' => 'Please use the following OTP code to complete your login:',
            'registration' => 'Please use the following OTP code to verify your email:',
            'password_reset' => 'Please use the following OTP code to reset your password:'
        ];
        
        $message = $messages[$purpose] ?? 'Your OTP code:';
        
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .otp-box { background: #f8f9fa; border: 2px solid #007bff; border-radius: 8px; padding: 30px; text-align: center; margin: 20px 0; }
        .otp-code { font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 8px; }
        .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h2>OTP Verification</h2>
        <p>$message</p>
        
        <div class="otp-box">
            <div class="otp-code">$otp</div>
        </div>
        
        <p><strong>Important:</strong></p>
        <ul>
            <li>This code is valid for 10 minutes</li>
            <li>Do not share this code with anyone</li>
            <li>If you didn't request this code, please ignore this email</li>
        </ul>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * Prepare newsletter body with tracking
     */
    private function prepareNewsletterBody($content, $subscriber, $tracking_token) {
        $base_url = BASE_URL;
        
        // Add open tracking pixel
        $tracking_pixel = "<img src='{$base_url}/api/newsletter/track-open.php?t={$tracking_token}' width='1' height='1' style='display:none;' />";
        
        // Replace unsubscribe link
        $unsubscribe_link = "{$base_url}/newsletter/unsubscribe.php?email=" . urlencode($subscriber['email']) . "&token={$tracking_token}";
        $content = str_replace('{{unsubscribe_url}}', $unsubscribe_link, $content);
        
        // Replace subscriber name
        $content = str_replace('{{subscriber_name}}', htmlspecialchars($subscriber['name']), $content);
        
        // Add tracking pixel at the end
        $content .= $tracking_pixel;
        
        return $content;
    }
    
    /**
     * Contact Form Email Template
     */
    private function getContactTemplate($data) {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .label { font-weight: bold; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>New Contact Form Submission</h2>
        
        <div class="info-box">
            <p><span class="label">Name:</span> {$data['name']}</p>
            <p><span class="label">Email:</span> {$data['email']}</p>
            <p><span class="label">Subject:</span> {$data['subject']}</p>
            <p><span class="label">IP Address:</span> {$data['ip_address']}</p>
            <p><span class="label">Submitted:</span> {$data['created_at']}</p>
        </div>
        
        <h3>Message:</h3>
        <div class="info-box">
            <p>{$data['message']}</p>
        </div>
        
        <p><a href="{$_SERVER['HTTP_HOST']}/admin/contact-queries.php">View in Admin Panel</a></p>
    </div>
</body>
</html>
HTML;
    }
}
