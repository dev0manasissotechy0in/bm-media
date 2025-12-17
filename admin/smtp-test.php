<?php
/**
 * SMTP Multi-Config Test Script
 * Tests all SMTP configurations: Auth, Newsletter, Contact
 */

require_once 'auth_check.php';
require_once '../includes/EmailService.php';

$page_title = 'SMTP Test & Diagnostics';
$db = Database::getInstance();

$testResults = [];
$errors = [];

// Handle test execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testType = $_POST['test_type'] ?? '';
    $testEmail = $_POST['test_email'] ?? '';
    
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    } else {
        $emailService = new EmailService();
        
        switch ($testType) {
            case 'auth_login_otp':
                // Test Admin Login OTP
                $otp = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
                $emailBody = getLoginOTPTemplate($otp, $_SESSION['admin_name']);
                $result = $emailService->sendEmail('auth', $testEmail, 'Admin Login OTP - Test', $emailBody);
                $testResults[] = [
                    'type' => 'Admin Login OTP',
                    'purpose' => 'auth',
                    'email' => $testEmail,
                    'status' => $result,
                    'otp' => $otp,
                    'message' => $result ? 'Email sent successfully!' : 'Failed to send email. Check SMTP configuration.'
                ];
                break;
                
            case 'auth_forgot_otp':
                // Test Forgot Password OTP
                $otp = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
                $emailBody = getForgotPasswordOTPTemplate($otp, $_SESSION['admin_name']);
                $result = $emailService->sendEmail('auth', $testEmail, 'Password Reset OTP - Test', $emailBody);
                $testResults[] = [
                    'type' => 'Forgot Password OTP',
                    'purpose' => 'auth',
                    'email' => $testEmail,
                    'status' => $result,
                    'otp' => $otp,
                    'message' => $result ? 'Email sent successfully!' : 'Failed to send email. Check SMTP configuration.'
                ];
                break;
                
            case 'auth_reporter_otp':
                // Test Reporter Login OTP
                $otp = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
                $emailBody = getReporterOTPTemplate($otp);
                $result = $emailService->sendEmail('auth', $testEmail, 'Reporter Login OTP - Test', $emailBody);
                $testResults[] = [
                    'type' => 'Reporter Login OTP',
                    'purpose' => 'auth',
                    'email' => $testEmail,
                    'status' => $result,
                    'otp' => $otp,
                    'message' => $result ? 'Email sent successfully!' : 'Failed to send email. Check SMTP configuration.'
                ];
                break;
                
            case 'auth_user_otp':
                // Test User Registration OTP
                $otp = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
                $emailBody = getUserOTPTemplate($otp);
                $result = $emailService->sendEmail('auth', $testEmail, 'User Registration OTP - Test', $emailBody);
                $testResults[] = [
                    'type' => 'User Registration OTP',
                    'purpose' => 'auth',
                    'email' => $testEmail,
                    'status' => $result,
                    'otp' => $otp,
                    'message' => $result ? 'Email sent successfully!' : 'Failed to send email. Check SMTP configuration.'
                ];
                break;
                
            case 'newsletter_test':
                // Test Newsletter
                $emailBody = getNewsletterTemplate();
                $result = $emailService->sendEmail('newsletter', $testEmail, 'Newsletter Test - Latest News', $emailBody);
                $testResults[] = [
                    'type' => 'Newsletter',
                    'purpose' => 'newsletter',
                    'email' => $testEmail,
                    'status' => $result,
                    'message' => $result ? 'Newsletter sent successfully!' : 'Failed to send newsletter. Check SMTP configuration.'
                ];
                break;
                
            case 'newsletter_article':
                // Test Article Newsletter
                $article = $db->fetchOne("SELECT * FROM articles WHERE status = 'published' ORDER BY id DESC LIMIT 1");
                if ($article) {
                    $emailBody = getArticleNewsletterTemplate($article);
                    $result = $emailService->sendEmail('newsletter', $testEmail, 'New Article: ' . $article['title'], $emailBody);
                    $testResults[] = [
                        'type' => 'Article Newsletter',
                        'purpose' => 'newsletter',
                        'email' => $testEmail,
                        'status' => $result,
                        'article' => $article['title'],
                        'message' => $result ? 'Article newsletter sent successfully!' : 'Failed to send article. Check SMTP configuration.'
                    ];
                } else {
                    $errors[] = 'No published articles found to test';
                }
                break;
                
            case 'contact_test':
                // Test Contact Form
                $emailBody = getContactFormTemplate();
                $result = $emailService->sendEmail('contact', $testEmail, 'Contact Form Test - New Inquiry', $emailBody);
                $testResults[] = [
                    'type' => 'Contact Form',
                    'purpose' => 'contact',
                    'email' => $testEmail,
                    'status' => $result,
                    'message' => $result ? 'Contact email sent successfully!' : 'Failed to send contact email. Check SMTP configuration.'
                ];
                break;
                
            case 'test_all':
                // Test all SMTP configurations
                $allTests = ['auth_login_otp', 'auth_forgot_otp', 'newsletter_test', 'contact_test'];
                foreach ($allTests as $test) {
                    $_POST['test_type'] = $test;
                    // Recursive call would be here, but we'll inline it
                }
                break;
        }
    }
}

// Load SMTP configurations status
$smtp_status = [];
$purposes = ['auth', 'newsletter', 'contact'];
foreach ($purposes as $purpose) {
    $config = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE smtp_purpose = ?", [$purpose]);
    $settings = [];
    foreach ($config as $setting) {
        $key = str_replace($purpose . '_smtp_', '', $setting['setting_key']);
        $settings[$key] = $setting['setting_value'];
    }
    $smtp_status[$purpose] = [
        'enabled' => ($settings['enabled'] ?? '0') == '1',
        'host' => $settings['host'] ?? '',
        'port' => $settings['port'] ?? '',
        'username' => $settings['username'] ?? '',
        'from_email' => $settings['from_email'] ?? ''
    ];
}

// Email Template Functions
function getLoginOTPTemplate($otp, $adminName) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 40px 30px; }
        .otp-box { background: #f8f9fa; border: 3px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 10px; font-family: 'Courier New', monospace; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">🔐 Admin Login OTP</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Two-Factor Authentication</p>
        </div>
        
        <div class="content">
            <p>Hello <strong>$adminName</strong>,</p>
            <p>You requested to login to the admin panel. Please use the following OTP code to complete your authentication:</p>
            
            <div class="otp-box">
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Your OTP Code</div>
                <div class="otp-code">$otp</div>
                <div style="font-size: 12px; color: #999; margin-top: 10px;">Valid for 10 minutes</div>
            </div>
            
            <div class="info-box">
                <strong>⚠️ Security Notice:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>This OTP is valid for <strong>10 minutes</strong> only</li>
                    <li>Never share this code with anyone</li>
                    <li>Our team will never ask for your OTP</li>
                    <li>If you didn't request this, please ignore this email</li>
                </ul>
            </div>
            
            <p style="color: #666; font-size: 14px; margin-top: 30px;">
                <strong>Need help?</strong> Contact our support team if you're having trouble logging in.
            </p>
        </div>
        
        <div class="footer">
            <p><strong>This is an automated email. Please do not reply.</strong></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function getForgotPasswordOTPTemplate($otp, $adminName) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 40px 30px; }
        .otp-box { background: #fff3e0; border: 3px dashed #ff6f00; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #ff6f00; letter-spacing: 10px; font-family: 'Courier New', monospace; }
        .info-box { background: #ffebee; border-left: 4px solid #f44336; padding: 15px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">🔑 Password Reset Request</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Reset Your Admin Password</p>
        </div>
        
        <div class="content">
            <p>Hello <strong>$adminName</strong>,</p>
            <p>We received a request to reset your admin password. Use the OTP code below to proceed:</p>
            
            <div class="otp-box">
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Password Reset OTP</div>
                <div class="otp-code">$otp</div>
                <div style="font-size: 12px; color: #999; margin-top: 10px;">Valid for 30 minutes</div>
            </div>
            
            <div class="info-box">
                <strong>⚠️ Important Security Information:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li>This code expires in <strong>30 minutes</strong></li>
                    <li>If you didn't request this reset, <strong>ignore this email</strong></li>
                    <li>Your password will remain unchanged until you complete the reset</li>
                    <li>Never share this OTP with anyone, including our support team</li>
                </ul>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>This is an automated email. Please do not reply.</strong></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function getReporterOTPTemplate($otp) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 40px 30px; }
        .otp-box { background: #e8f5e9; border: 3px dashed #4caf50; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #4caf50; letter-spacing: 10px; font-family: 'Courier New', monospace; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">📰 Reporter Login OTP</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Secure Access to Reporter Panel</p>
        </div>
        
        <div class="content">
            <p>Hello Reporter,</p>
            <p>Use this OTP code to access the reporter panel and start creating amazing content:</p>
            
            <div class="otp-box">
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Your Access Code</div>
                <div class="otp-code">$otp</div>
                <div style="font-size: 12px; color: #999; margin-top: 10px;">Valid for 10 minutes</div>
            </div>
            
            <p style="color: #666; font-size: 14px;">This code will expire in 10 minutes. Don't share it with anyone.</p>
        </div>
        
        <div class="footer">
            <p><strong>This is an automated email. Please do not reply.</strong></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function getUserOTPTemplate($otp) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 40px 30px; }
        .otp-box { background: #fff9e6; border: 3px dashed #ffc107; border-radius: 10px; padding: 30px; text-align: center; margin: 30px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #ff6f00; letter-spacing: 10px; font-family: 'Courier New', monospace; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">👋 Welcome!</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Verify Your Email Address</p>
        </div>
        
        <div class="content">
            <p>Thank you for registering!</p>
            <p>Please use this OTP code to verify your email and complete your registration:</p>
            
            <div class="otp-box">
                <div style="font-size: 14px; color: #666; margin-bottom: 10px;">Verification Code</div>
                <div class="otp-code">$otp</div>
                <div style="font-size: 12px; color: #999; margin-top: 10px;">Valid for 15 minutes</div>
            </div>
            
            <p style="color: #666; font-size: 14px;">Once verified, you'll have full access to comment, save articles, and more!</p>
        </div>
        
        <div class="footer">
            <p><strong>This is an automated email. Please do not reply.</strong></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function getNewsletterTemplate() {
    global $db;
    $articles = $db->fetchAll("SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    
    $articlesList = '';
    foreach ($articles as $article) {
        $articlesList .= <<<HTML
        <div style="border-bottom: 1px solid #eee; padding: 20px 0;">
            <h3 style="margin: 0 0 10px 0; color: #333;">
                <a href="http://localhost/article.php?slug={$article['slug']}" style="color: #667eea; text-decoration: none;">{$article['title']}</a>
            </h3>
            <p style="color: #666; margin: 0 0 10px 0;">{$article['excerpt']}</p>
            <a href="http://localhost/article.php?slug={$article['slug']}" style="color: #667eea; text-decoration: none; font-size: 14px;">Read More →</a>
        </div>
HTML;
    }
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; color: white; }
        .content { padding: 30px; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">📧 Weekly Newsletter</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Latest News & Updates</p>
        </div>
        
        <div class="content">
            <p>Hello Subscriber,</p>
            <p>Here are the latest articles from our news website:</p>
            
            $articlesList
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="http://localhost" style="display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">Visit Website</a>
            </div>
        </div>
        
        <div class="footer">
            <p><a href="#" style="color: #666;">Unsubscribe</a> | <a href="#" style="color: #666;">Update Preferences</a></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function getArticleNewsletterTemplate($article) {
    $imageUrl = $article['thumbnail'] ? "http://localhost/uploads/articles/{$article['thumbnail']}" : 'http://localhost/assets/img/default.jpg';
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 30px; }
        .article-image { width: 100%; height: auto; border-radius: 8px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">📰 New Article Published</h2>
        </div>
        
        <div class="content">
            <h1 style="color: #333; margin: 0 0 15px 0;">{$article['title']}</h1>
            
            <img src="$imageUrl" alt="{$article['title']}" class="article-image">
            
            <p style="color: #666; font-size: 16px; line-height: 1.8;">{$article['excerpt']}</p>
            
            <div style="text-align: center;">
                <a href="http://localhost/article.php?slug={$article['slug']}" class="btn">Read Full Article</a>
            </div>
        </div>
        
        <div class="footer">
            <p><a href="#" style="color: #666;">Unsubscribe</a> | <a href="#" style="color: #666;">Update Preferences</a></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

function getContactFormTemplate() {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 30px; text-align: center; color: white; }
        .content { padding: 30px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 style="margin: 0;">💬 Contact Form Test</h1>
        </div>
        
        <div class="content">
            <h2>New Contact Form Submission</h2>
            
            <div class="info-box">
                <p><strong>Name:</strong> Test User</p>
                <p><strong>Email:</strong> test@example.com</p>
                <p><strong>Subject:</strong> Test Contact Form</p>
                <p><strong>Date:</strong> December 3, 2025</p>
            </div>
            
            <h3>Message:</h3>
            <div class="info-box">
                <p>This is a test message from the SMTP test script. If you received this, your Contact SMTP configuration is working correctly!</p>
            </div>
        </div>
        
        <div class="footer">
            <p><strong>This is a test email from SMTP diagnostics.</strong></p>
            <p>© 2025 News Website. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-envelope-check-fill"></i> SMTP Test & Diagnostics
            </h1>
            <a href="smtp-multi-config.php" class="btn btn-primary">
                <i class="bi bi-gear-fill"></i> Configure SMTP
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <i class="bi bi-exclamation-triangle-fill"></i>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($testResults)): ?>
        <div class="card mb-4 border-<?= $testResults[0]['status'] ? 'success' : 'danger' ?>">
            <div class="card-header bg-<?= $testResults[0]['status'] ? 'success' : 'danger' ?> text-white">
                <h5 class="mb-0">
                    <i class="bi bi-<?= $testResults[0]['status'] ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                    Test Results
                </h5>
            </div>
            <div class="card-body">
                <?php foreach ($testResults as $result): ?>
                <div class="alert alert-<?= $result['status'] ? 'success' : 'danger' ?>">
                    <h5><strong><?= htmlspecialchars($result['type']) ?></strong></h5>
                    <p class="mb-2">
                        <strong>Purpose:</strong> <?= htmlspecialchars($result['purpose']) ?><br>
                        <strong>Recipient:</strong> <?= htmlspecialchars($result['email']) ?><br>
                        <?php if (isset($result['otp'])): ?>
                        <strong>OTP Sent:</strong> <code style="font-size: 16px; background: rgba(0,0,0,0.1); padding: 5px 10px; border-radius: 4px;"><?= $result['otp'] ?></code><br>
                        <?php endif; ?>
                        <?php if (isset($result['article'])): ?>
                        <strong>Article:</strong> <?= htmlspecialchars($result['article']) ?><br>
                        <?php endif; ?>
                        <strong>Status:</strong> <?= $result['message'] ?>
                    </p>
                    <?php if ($result['status']): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> <strong>Check your email inbox at <?= htmlspecialchars($result['email']) ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- SMTP Status Overview -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-<?= $smtp_status['auth']['enabled'] ? 'success' : 'danger' ?>">
                    <div class="card-header bg-<?= $smtp_status['auth']['enabled'] ? 'success' : 'danger' ?> text-white">
                        <h6 class="mb-0"><i class="bi bi-shield-lock-fill"></i> Auth SMTP</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="badge bg-<?= $smtp_status['auth']['enabled'] ? 'success' : 'danger' ?>">
                                <?= $smtp_status['auth']['enabled'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Host:</strong> <?= htmlspecialchars($smtp_status['auth']['host'] ?: 'Not configured') ?></p>
                        <p class="mb-1"><strong>Port:</strong> <?= htmlspecialchars($smtp_status['auth']['port'] ?: '-') ?></p>
                        <p class="mb-0"><strong>From:</strong> <?= htmlspecialchars($smtp_status['auth']['from_email'] ?: 'Not set') ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-<?= $smtp_status['newsletter']['enabled'] ? 'success' : 'danger' ?>">
                    <div class="card-header bg-<?= $smtp_status['newsletter']['enabled'] ? 'success' : 'danger' ?> text-white">
                        <h6 class="mb-0"><i class="bi bi-envelope-paper-fill"></i> Newsletter SMTP</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="badge bg-<?= $smtp_status['newsletter']['enabled'] ? 'success' : 'danger' ?>">
                                <?= $smtp_status['newsletter']['enabled'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Host:</strong> <?= htmlspecialchars($smtp_status['newsletter']['host'] ?: 'Not configured') ?></p>
                        <p class="mb-1"><strong>Port:</strong> <?= htmlspecialchars($smtp_status['newsletter']['port'] ?: '-') ?></p>
                        <p class="mb-0"><strong>From:</strong> <?= htmlspecialchars($smtp_status['newsletter']['from_email'] ?: 'Not set') ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-<?= $smtp_status['contact']['enabled'] ? 'success' : 'danger' ?>">
                    <div class="card-header bg-<?= $smtp_status['contact']['enabled'] ? 'success' : 'danger' ?> text-white">
                        <h6 class="mb-0"><i class="bi bi-chat-dots-fill"></i> Contact SMTP</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Status:</strong> 
                            <span class="badge bg-<?= $smtp_status['contact']['enabled'] ? 'success' : 'danger' ?>">
                                <?= $smtp_status['contact']['enabled'] ? 'Enabled' : 'Disabled' ?>
                            </span>
                        </p>
                        <p class="mb-1"><strong>Host:</strong> <?= htmlspecialchars($smtp_status['contact']['host'] ?: 'Not configured') ?></p>
                        <p class="mb-1"><strong>Port:</strong> <?= htmlspecialchars($smtp_status['contact']['port'] ?: '-') ?></p>
                        <p class="mb-0"><strong>From:</strong> <?= htmlspecialchars($smtp_status['contact']['from_email'] ?: 'Not set') ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Forms -->
        <div class="row">
            <!-- Auth SMTP Tests -->
            <div class="col-md-6">
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-shield-lock-fill"></i> Auth SMTP Tests</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Test Email Address</label>
                                <input type="email" name="test_email" class="form-control" placeholder="your@email.com" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="test_type" value="auth_login_otp" class="btn btn-primary">
                                    <i class="bi bi-person-badge"></i> Test Admin Login OTP
                                </button>
                                <button type="submit" name="test_type" value="auth_forgot_otp" class="btn btn-warning">
                                    <i class="bi bi-key-fill"></i> Test Forgot Password OTP
                                </button>
                                <button type="submit" name="test_type" value="auth_reporter_otp" class="btn btn-success">
                                    <i class="bi bi-newspaper"></i> Test Reporter OTP
                                </button>
                                <button type="submit" name="test_type" value="auth_user_otp" class="btn btn-info">
                                    <i class="bi bi-person-fill"></i> Test User Registration OTP
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Newsletter & Contact Tests -->
            <div class="col-md-6">
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-envelope-paper-fill"></i> Newsletter & Contact Tests</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Test Email Address</label>
                                <input type="email" name="test_email" class="form-control" placeholder="your@email.com" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="test_type" value="newsletter_test" class="btn btn-success">
                                    <i class="bi bi-mailbox"></i> Test Newsletter
                                </button>
                                <button type="submit" name="test_type" value="newsletter_article" class="btn btn-primary">
                                    <i class="bi bi-file-earmark-text"></i> Test Article Newsletter
                                </button>
                                <button type="submit" name="test_type" value="contact_test" class="btn btn-info">
                                    <i class="bi bi-chat-dots"></i> Test Contact Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Troubleshooting Guide -->
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-question-circle-fill"></i> Troubleshooting Guide</h5>
            </div>
            <div class="card-body">
                <h6><strong>If emails are not sending:</strong></h6>
                <ol>
                    <li><strong>Check SMTP Configuration:</strong> Ensure the SMTP config is enabled and all fields are filled correctly</li>
                    <li><strong>Verify Credentials:</strong> Double-check your SMTP username and password</li>
                    <li><strong>Port Settings:</strong> Use port 587 for TLS or 465 for SSL</li>
                    <li><strong>Firewall:</strong> Make sure your server allows outbound connections on SMTP ports</li>
                    <li><strong>Gmail Users:</strong> Use an App Password instead of your regular password</li>
                    <li><strong>Check Logs:</strong> Look at error_log for detailed error messages</li>
                </ol>
                
                <h6 class="mt-3"><strong>Common SMTP Providers:</strong></h6>
                <ul>
                    <li><strong>Gmail:</strong> smtp.gmail.com:587 (TLS) - Requires App Password</li>
                    <li><strong>Outlook:</strong> smtp.office365.com:587 (TLS)</li>
                    <li><strong>Yahoo:</strong> smtp.mail.yahoo.com:465 (SSL)</li>
                    <li><strong>SendGrid:</strong> smtp.sendgrid.net:587 (TLS)</li>
                    <li><strong>Mailgun:</strong> smtp.mailgun.org:587 (TLS)</li>
                </ul>
                
                <div class="alert alert-info mb-0 mt-3">
                    <i class="bi bi-lightbulb-fill"></i>
                    <strong>Pro Tip:</strong> For production, consider using dedicated email services like SendGrid, Mailgun, or Amazon SES for better deliverability.
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>
