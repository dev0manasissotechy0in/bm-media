<?php
/**
 * Reporter Email Verification
 */

require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Session.php';
require_once __DIR__ . '/includes/Functions.php';
require_once __DIR__ . '/includes/EmailHelper.php';

$db = Database::getInstance();
$message = '';
$type = 'danger';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find reporter with this token
    $reporter = $db->fetchOne(
        "SELECT * FROM reporters WHERE verification_token = ? AND verification_expires > NOW()",
        [$token]
    );
    
    if ($reporter) {
        // Update reporter as verified
        $updated = $db->update('reporters', [
            'email_verified' => 1,
            'status' => 'active',
            'verification_token' => null,
            'verification_expires' => null
        ], 'id = ?', [$reporter['id']]);
        
        if ($updated) {
            // Send welcome email
            try {
                $emailHelper = new EmailHelper('auth');
                $subject = 'Welcome to Brackodd Media - Reporter Team!';
                
                $body = '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
                        <h1 style="color: white; margin: 0;">Welcome to Brackodd Media! 🎉</h1>
                    </div>
                    
                    <div style="padding: 30px; background: #f8f9fa;">
                        <h2 style="color: #333;">Hello ' . htmlspecialchars($reporter['full_name']) . ',</h2>
                        
                        <p style="font-size: 16px; line-height: 1.6; color: #555;">
                            Congratulations! Your email has been successfully verified, and you are now an official reporter at <strong>Brackodd Media</strong>.
                        </p>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
                            <h3 style="color: #667eea; margin-top: 0;">Reporter Details:</h3>
                            <p style="margin: 5px 0;"><strong>Name:</strong> ' . htmlspecialchars($reporter['full_name']) . '</p>
                            <p style="margin: 5px 0;"><strong>Email:</strong> ' . htmlspecialchars($reporter['email']) . '</p>
                            <p style="margin: 5px 0;"><strong>Reporter ID:</strong> ' . htmlspecialchars($reporter['unique_reporter_id']) . '</p>
                            <p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: #28a745;">Active</span></p>
                        </div>
                        
                        <div style="background: #e7f3ff; padding: 15px; border-left: 4px solid #667eea; margin: 20px 0;">
                            <p style="margin: 0; font-size: 14px;">
                                <strong>📌 Note:</strong> Your profile is now active. Our admin team will review your documents and may contact you for further assignments.
                            </p>
                        </div>
                        
                        <p style="font-size: 16px; line-height: 1.6; color: #555;">
                            We are excited to have you on board and look forward to working with you!
                        </p>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="' . BASE_URL . '" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
                                Visit Brackodd Media
                            </a>
                        </div>
                    </div>
                    
                    <div style="background: #333; color: white; padding: 20px; text-align: center; font-size: 12px;">
                        <p style="margin: 5px 0;">&copy; ' . date('Y') . ' Brackodd Media. All rights reserved.</p>
                        <p style="margin: 5px 0;">Thank you for being part of our journalism team!</p>
                    </div>
                </div>';
                
                $emailHelper->send($reporter['email'], $subject, $body);
            } catch (Exception $e) {
                // Log error but don't fail verification
                error_log('Welcome email failed: ' . $e->getMessage());
            }
            
            $message = 'Email verified successfully! Welcome to Brackodd Media as a reporter.';
            $type = 'success';
        } else {
            $message = 'Verification failed. Please try again or contact support.';
        }
    } else {
        $message = 'Invalid or expired verification link. Please contact admin for a new verification email.';
    }
} else {
    $message = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Brackodd Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .verification-card {
            max-width: 500px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .verification-icon {
            font-size: 5rem;
            margin: 30px 0 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <div class="text-center">
                <?php if ($type === 'success'): ?>
                <i class="bi bi-check-circle-fill text-success verification-icon"></i>
                <?php else: ?>
                <i class="bi bi-x-circle-fill text-danger verification-icon"></i>
                <?php endif; ?>
            </div>
            
            <div class="p-4">
                <h3 class="text-center mb-4">Email Verification</h3>
                <div class="alert alert-<?= $type ?> text-center">
                    <?= $message ?>
                </div>
                
                <?php if ($type === 'success'): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill"></i>
                    A welcome email has been sent to your registered email address with your reporter details.
                </div>
                <?php endif; ?>
                
                <div class="text-center mt-4">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary">
                        <i class="bi bi-house"></i> Go to Homepage
                    </a>
                    <?php if ($type === 'danger'): ?>
                    <a href="<?= BASE_URL ?>/contact.php" class="btn btn-outline-secondary">
                        <i class="bi bi-envelope"></i> Contact Support
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
