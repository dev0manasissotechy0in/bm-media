<?php
/**
 * Enhanced Email Helper Class
 * Supports Multi-SMTP configuration for Auth, Newsletter, and Contact emails
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    private $mailer;
    private $db;
    private $purpose;
    
    /**
     * Constructor
     * @param string $purpose The email purpose: 'auth', 'newsletter', or 'contact'
     */
    public function __construct($purpose = 'auth') {
        require_once __DIR__ . '/../vendor/autoload.php';
        require_once __DIR__ . '/Database.php';
        
        $this->db = Database::getInstance();
        $this->purpose = $purpose;
        $this->mailer = new PHPMailer(true);
        
        // Load SMTP configuration based on purpose
        $smtp_config = $this->loadSMTPConfig($purpose);
        
        try {
            // SMTP Configuration
            $this->mailer->isSMTP();
            $this->mailer->Host = $smtp_config['host'] ?? 'smtp.gmail.com';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $smtp_config['username'] ?? '';
            $this->mailer->Password = $smtp_config['password'] ?? '';
            
            // Add timeout to prevent hanging (5 seconds)
            $this->mailer->Timeout = 5;
            $this->mailer->SMTPDebug = 0; // Disable debug output
            $this->mailer->SMTPAutoTLS = false; // Don't auto-enable TLS
            
            // Set encryption type
            if (($smtp_config['encryption'] ?? 'tls') === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            $this->mailer->Port = $smtp_config['port'] ?? 587;
            
            $this->mailer->setFrom(
                $smtp_config['from_email'] ?? 'noreply@localhost',
                $smtp_config['from_name'] ?? 'News Website'
            );
            
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log('EmailHelper initialization error: ' . $e->getMessage());
        }
    }
    
    /**
     * Load SMTP configuration from database based on purpose
     */
    private function loadSMTPConfig($purpose) {
        $prefix = $purpose . '_smtp_';
        $config = [];
        
        $settings = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM settings WHERE smtp_purpose = ?",
            [$purpose]
        );
        
        foreach ($settings as $setting) {
            $key = str_replace($prefix, '', $setting['setting_key']);
            $config[$key] = $setting['setting_value'];
        }
        
        // Check if this SMTP is enabled
        if (($config['enabled'] ?? '0') !== '1') {
            error_log("SMTP purpose '{$purpose}' is not enabled, using defaults or fallback");
        }
        
        return $config;
    }
    
    /**
     * Send OTP verification code
     * @param string $email Recipient email
     * @param string $otp The 6-digit OTP code
     * @param string $name Recipient name
     * @param string $user_type Type of user: 'user', 'author', or 'admin'
     * @param string $purpose Purpose: 'login', 'registration', or 'password_reset'
     */
    public function sendOTP($email, $otp, $name = '', $user_type = 'user', $purpose = 'login') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            $user_type_label = ucfirst($user_type);
            
            // Set subject based on purpose
            if ($purpose === 'password_reset') {
                $this->mailer->Subject = 'Password Reset Code - ' . SITE_NAME;
                $header_text = '🔑 Password Reset';
                $description_text = 'reset your password';
            } else {
                $this->mailer->Subject = 'Your OTP Verification Code - ' . SITE_NAME;
                $header_text = '🔐 Verification Code';
                $description_text = 'complete your ' . $user_type_label . ' verification';
            }
            
            $expiry_minutes = 10; // Default
            $expiry_setting = $this->db->fetchOne(
                "SELECT setting_value FROM settings WHERE setting_key = 'otp_expiry_minutes'"
            );
            if ($expiry_setting) {
                $expiry_minutes = $expiry_setting['setting_value'];
            }
            
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            </head>
            <body style='margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;'>
                <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px;'>
                    <tr>
                        <td align='center'>
                            <table width='600' cellpadding='0' cellspacing='0' style='background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                                <!-- Header -->
                                <tr>
                                    <td style='background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 40px; text-align: center;'>
                                        <h1 style='margin: 0; color: white; font-size: 28px;'>{$header_text}</h1>
                                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;'>
                                            {$user_type_label} Authentication
                                        </p>
                                    </td>
                                </tr>
                                
                                <!-- Body -->
                                <tr>
                                    <td style='padding: 40px;'>
                                        <p style='font-size: 16px; color: #333; margin: 0 0 20px 0;'>
                                            Hi " . ($name ? "<strong>" . htmlspecialchars($name) . "</strong>" : "there") . ",
                                        </p>
                                        
                                        <p style='font-size: 15px; color: #666; line-height: 1.6; margin: 0 0 30px 0;'>
                                            Use the following One-Time Password (OTP) to {$description_text}. This code is valid for <strong>{$expiry_minutes} minutes</strong>.
                                        </p>
                                        
                                        <!-- OTP Box -->
                                        <table width='100%' cellpadding='0' cellspacing='0' style='margin: 0 0 30px 0;'>
                                            <tr>
                                                <td align='center'>
                                                    <div style='background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
                                                                padding: 25px 40px; border-radius: 12px; display: inline-block;
                                                                box-shadow: 0 4px 15px rgba(37,99,235,0.3);'>
                                                        <p style='margin: 0 0 10px 0; color: rgba(255,255,255,0.8); font-size: 13px; text-transform: uppercase; letter-spacing: 1px;'>
                                                            Your OTP Code
                                                        </p>
                                                        <p style='margin: 0; color: white; font-size: 42px; font-weight: bold; letter-spacing: 8px; font-family: \"Courier New\", monospace;'>
                                                            {$otp}
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <!-- Security Notice -->
                                        <table width='100%' cellpadding='0' cellspacing='0' style='background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 5px; margin: 0 0 20px 0;'>
                                            <tr>
                                                <td>
                                                    <p style='margin: 0; color: #92400e; font-size: 14px;'>
                                                        <strong>⚠️ Security Notice:</strong><br>
                                                        Never share this code with anyone. Our team will never ask for your OTP.
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p style='font-size: 14px; color: #999; text-align: center; margin: 20px 0 0 0;'>
                                            If you didn't request this code, please ignore this email or contact our support team.
                                        </p>
                                    </td>
                                </tr>
                                
                                <!-- Footer -->
                                <tr>
                                    <td style='background: #1f2937; padding: 20px; text-align: center;'>
                                        <p style='margin: 0; color: #9ca3af; font-size: 12px;'>
                                            &copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ";
            
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Failed to send OTP: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send welcome email after successful registration
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $user_type Type of user: 'user', 'author', or 'admin'
     */
    public function sendWelcomeEmail($email, $name, $user_type = 'user') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $name);
            
            $this->mailer->Subject = 'Welcome to ' . SITE_NAME . '! 🎉';
            
            // Get social media links
            $social_links = $this->getSocialMediaLinks();
            
            $user_type_label = ucfirst($user_type);
            $dashboard_url = BASE_URL . '/' . $user_type . '/dashboard.php';
            
            $body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            </head>
            <body style='margin: 0; padding: 0; background-color: #f4f4f4;'>
                <table width='100%' cellpadding='0' cellspacing='0' style='background-color: #f4f4f4; padding: 20px;'>
                    <tr>
                        <td align='center'>
                            <table width='600' cellpadding='0' cellspacing='0' style='background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
                                <!-- Header -->
                                <tr>
                                    <td style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 50px 40px; text-align: center;'>
                                        <h1 style='margin: 0; color: white; font-size: 32px; font-family: Arial, sans-serif;'>
                                            Welcome to " . SITE_NAME . "! 🎉
                                        </h1>
                                        <p style='margin: 10px 0 0 0; color: rgba(255,255,255,0.9); font-size: 16px; font-family: Arial, sans-serif;'>
                                            We're thrilled to have you join our community!
                                        </p>
                                    </td>
                                </tr>
                                
                                <!-- Body -->
                                <tr>
                                    <td style='padding: 40px;'>
                                        <p style='font-size: 18px; color: #333; margin: 0 0 20px 0; font-family: Arial, sans-serif;'>
                                            Hi <strong>" . htmlspecialchars($name) . "</strong>,
                                        </p>
                                        
                                        <p style='font-size: 16px; color: #666; line-height: 1.6; margin: 0 0 30px 0; font-family: Arial, sans-serif;'>
                                            Thank you for creating " . ($user_type === 'admin' ? "an admin" : ($user_type === 'author' ? "an author" : "a")) . " account with us! You're now part of a vibrant community where you can stay updated with the latest news, save your favorite articles, and engage with content that matters to you.
                                        </p>
                                        
                                        <!-- Getting Started -->
                                        <table width='100%' cellpadding='0' cellspacing='0' style='background: #f8f9fa; border-radius: 8px; padding: 25px; margin: 0 0 30px 0;'>
                                            <tr>
                                                <td>
                                                    <h3 style='color: #667eea; margin: 0 0 15px 0; font-size: 20px; font-family: Arial, sans-serif;'>
                                                        🚀 Quick Tips to Get Started:
                                                    </h3>
                                                    <table width='100%' cellpadding='0' cellspacing='0'>
                                                        <tr>
                                                            <td style='padding: 8px 0;'>
                                                                <span style='color: #10b981; font-size: 18px; margin-right: 10px;'>✓</span>
                                                                <span style='color: #555; font-size: 15px; font-family: Arial, sans-serif;'>Complete your profile to personalize your experience</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style='padding: 8px 0;'>
                                                                <span style='color: #10b981; font-size: 18px; margin-right: 10px;'>✓</span>
                                                                <span style='color: #555; font-size: 15px; font-family: Arial, sans-serif;'>Save your favorite articles to read later</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style='padding: 8px 0;'>
                                                                <span style='color: #10b981; font-size: 18px; margin-right: 10px;'>✓</span>
                                                                <span style='color: #555; font-size: 15px; font-family: Arial, sans-serif;'>Comment and engage with our community</span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style='padding: 8px 0;'>
                                                                <span style='color: #10b981; font-size: 18px; margin-right: 10px;'>✓</span>
                                                                <span style='color: #555; font-size: 15px; font-family: Arial, sans-serif;'>Share articles with your friends and family</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>";
            
            // Only show newsletter CTA for regular users
            if ($user_type === 'user') {
                $body .= "
                                        <!-- Newsletter CTA -->
                                        <table width='100%' cellpadding='0' cellspacing='0' style='margin: 0 0 30px 0;'>
                                            <tr>
                                                <td align='center' style='padding: 20px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 8px;'>
                                                    <h3 style='color: white; margin: 0 0 10px 0; font-size: 20px; font-family: Arial, sans-serif;'>
                                                        📰 Stay Updated!
                                                    </h3>
                                                    <p style='color: rgba(255,255,255,0.9); margin: 0 0 20px 0; font-size: 14px; font-family: Arial, sans-serif;'>
                                                        Subscribe to our newsletter and never miss important news
                                                    </p>
                                                    <a href='" . BASE_URL . "/user/dashboard.php' 
                                                       style='background: white; color: #10b981; padding: 12px 35px; text-decoration: none; 
                                                              border-radius: 25px; display: inline-block; font-weight: bold; font-size: 16px; font-family: Arial, sans-serif;'>
                                                        Subscribe Now
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>";
            }
            
            // Show social media links if configured
            if (!empty(array_filter($social_links))) {
                $body .= "
                                        <!-- Social Media -->
                                        <table width='100%' cellpadding='0' cellspacing='0'>
                                            <tr>
                                                <td align='center' style='padding: 20px 0; border-top: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;'>
                                                    <p style='color: #666; margin: 0 0 15px 0; font-size: 16px; font-family: Arial, sans-serif;'>
                                                        <strong>Follow us on social media:</strong>
                                                    </p>
                                                    <table cellpadding='0' cellspacing='0' style='margin: 0 auto;'>
                                                        <tr>";
                
                if (!empty($social_links['facebook'])) {
                    $body .= "
                                                            <td style='padding: 0 10px;'>
                                                                <a href='" . htmlspecialchars($social_links['facebook']) . "' style='text-decoration: none;'>
                                                                    <img src='https://img.icons8.com/color/48/000000/facebook-new.png' alt='Facebook' style='width: 40px; height: 40px;'>
                                                                </a>
                                                            </td>";
                }
                
                if (!empty($social_links['twitter'])) {
                    $body .= "
                                                            <td style='padding: 0 10px;'>
                                                                <a href='" . htmlspecialchars($social_links['twitter']) . "' style='text-decoration: none;'>
                                                                    <img src='https://img.icons8.com/color/48/000000/twitter--v1.png' alt='Twitter' style='width: 40px; height: 40px;'>
                                                                </a>
                                                            </td>";
                }
                
                if (!empty($social_links['instagram'])) {
                    $body .= "
                                                            <td style='padding: 0 10px;'>
                                                                <a href='" . htmlspecialchars($social_links['instagram']) . "' style='text-decoration: none;'>
                                                                    <img src='https://img.icons8.com/color/48/000000/instagram-new.png' alt='Instagram' style='width: 40px; height: 40px;'>
                                                                </a>
                                                            </td>";
                }
                
                if (!empty($social_links['youtube'])) {
                    $body .= "
                                                            <td style='padding: 0 10px;'>
                                                                <a href='" . htmlspecialchars($social_links['youtube']) . "' style='text-decoration: none;'>
                                                                    <img src='https://img.icons8.com/color/48/000000/youtube-play.png' alt='YouTube' style='width: 40px; height: 40px;'>
                                                                </a>
                                                            </td>";
                }
                
                $body .= "
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>";
            }
            
            $body .= "
                                        <!-- CTA Button -->
                                        <table width='100%' cellpadding='0' cellspacing='0' style='margin: 30px 0 0 0;'>
                                            <tr>
                                                <td align='center'>
                                                    <a href='{$dashboard_url}' 
                                                       style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                                              color: white; padding: 15px 40px; text-decoration: none; 
                                                              border-radius: 25px; display: inline-block; font-weight: bold; 
                                                              font-size: 16px; font-family: Arial, sans-serif;'>
                                                        Go to Dashboard
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        
                                        <p style='font-size: 14px; color: #999; text-align: center; margin: 30px 0 0 0; font-family: Arial, sans-serif;'>
                                            Need help? <a href='" . BASE_URL . "/contact.php' style='color: #667eea; text-decoration: none;'>Contact our support team</a>
                                        </p>
                                    </td>
                                </tr>
                                
                                <!-- Footer -->
                                <tr>
                                    <td style='background: #1f2937; padding: 30px 40px; text-align: center;'>
                                        <p style='margin: 0 0 10px 0; color: #9ca3af; font-size: 13px; font-family: Arial, sans-serif;'>
                                            &copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.
                                        </p>
                                        <p style='margin: 0; color: #6b7280; font-size: 12px; font-family: Arial, sans-serif;'>
                                            You received this email because you created an account with us.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ";
            
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Failed to send welcome email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generic send email method
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $recipientName Recipient name (optional)
     */
    public function send($to, $subject, $body, $recipientName = '') {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($to, $recipientName);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('Failed to send email: ' . $e->getMessage());
            throw new Exception('Failed to send email: ' . $e->getMessage());
        }
    }
    
    /**
     * Get social media links from database
     */
    private function getSocialMediaLinks() {
        $links = [
            'facebook' => '',
            'twitter' => '',
            'instagram' => '',
            'youtube' => ''
        ];
        
        $settings = $this->db->fetchAll(
            "SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'social_%'"
        );
        
        foreach ($settings as $setting) {
            $key = str_replace('social_', '', $setting['setting_key']);
            if (isset($links[$key])) {
                $links[$key] = $setting['setting_value'];
            }
        }
        
        return $links;
    }
}
