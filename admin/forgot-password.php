<?php
/**
 * Admin Forgot Password
 */

require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'email';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    // Step 1: Send reset OTP
    if (isset($_POST['reset_step']) && $_POST['reset_step'] === 'email') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error = 'Please enter your email address';
        } else {
            $admin = $db->fetchOne("SELECT * FROM admin_users WHERE email = ? AND status = 'active'", [$email]);
            
            if ($admin) {
                // Generate reset token and OTP
                $reset_token = bin2hex(random_bytes(32));
                $otp = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
                $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                
                $db->update('admin_users', [
                    'reset_token' => $reset_token,
                    'reset_token_expires' => $expires,
                    'otp_code' => $otp,
                    'otp_expires_at' => $expires
                ], 'id = ?', [$admin['id']]);
                
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_token'] = $reset_token;
                
                // Send OTP via email
                require_once '../includes/EmailService.php';
                $emailService = new EmailService();
                
                $emailBody = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                            .otp-box { background: white; padding: 20px; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #667eea; border: 2px dashed #667eea; border-radius: 10px; margin: 20px 0; }
                            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Password Reset OTP</h2>
                                <p>" . SITE_NAME . "</p>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($admin['full_name']) . "</strong>,</p>
                                <p>You have requested to reset your admin password. Please use the following OTP to verify your identity:</p>
                                
                                <div class='otp-box'>$otp</div>
                                
                                <p><strong>Important:</strong></p>
                                <ul>
                                    <li>This OTP is valid for <strong>30 minutes</strong></li>
                                    <li>Do not share this OTP with anyone</li>
                                    <li>If you did not request this, please contact the administrator immediately</li>
                                </ul>
                                
                                <p>Best regards,<br>" . SITE_NAME . " Team</p>
                            </div>
                            <div class='footer'>
                                <p>This is an automated email. Please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                
                $emailSent = $emailService->sendEmail(
                    'auth',
                    $admin['email'],
                    'Password Reset OTP - ' . SITE_NAME,
                    $emailBody,
                    $admin['full_name']
                );
                
                if ($emailSent) {
                    $success = "Reset OTP has been sent to your email address. Please check your inbox and enter the code below.";
                    $step = 'verify';
                } else {
                    $error = "Failed to send reset OTP email. Please check SMTP configuration or contact the administrator. <a href='smtp-test.php' class='alert-link'>Test SMTP Settings</a>";
                }
                
            } else {
                $error = 'No account found with this email address';
            }
        }
    }
    
    // Step 2: Verify OTP
    if (isset($_POST['reset_step']) && $_POST['reset_step'] === 'verify') {
        $otp = strtoupper(trim($_POST['otp'] ?? ''));
        $email = $_SESSION['reset_email'] ?? '';
        
        if (empty($otp)) {
            $error = 'Please enter the OTP';
            $step = 'verify';
        } elseif (empty($email)) {
            $error = 'Session expired. Please start again.';
            $step = 'email';
        } else {
            $admin = $db->fetchOne(
                "SELECT * FROM admin_users WHERE email = ? AND otp_code = ? AND otp_expires_at > NOW() AND status = 'active'",
                [$email, $otp]
            );
            
            if ($admin) {
                $_SESSION['reset_verified'] = true;
                $step = 'reset';
            } else {
                $error = 'Invalid or expired OTP';
                $step = 'verify';
            }
        }
    }
    
    // Step 3: Reset password
    if (isset($_POST['reset_step']) && $_POST['reset_step'] === 'reset') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'] ?? '';
        $verified = $_SESSION['reset_verified'] ?? false;
        
        if (!$verified || empty($email)) {
            $error = 'Unauthorized access';
            $step = 'email';
        } elseif (empty($password) || empty($confirm_password)) {
            $error = 'Please fill all fields';
            $step = 'reset';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match';
            $step = 'reset';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters';
            $step = 'reset';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $db->update('admin_users', [
                'password' => $hashed_password,
                'reset_token' => null,
                'reset_token_expires' => null,
                'otp_code' => null,
                'otp_expires_at' => null
            ], 'email = ?', [$email]);
            
            // Clear session
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_verified']);
            
            header('Location: login.php?reset=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .reset-body {
            padding: 40px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-reset:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #e9ecef;
            position: relative;
        }
        .step.active {
            background: #667eea;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="reset-header">
            <h2 class="mb-0"><i class="bi bi-key"></i> Reset Password</h2>
            <p class="mb-0 mt-2 opacity-75">Admin Panel</p>
        </div>
        <div class="reset-body">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step === 'email' ? 'active' : ($step !== 'email' ? 'completed' : '') ?>">
                    <small>1. Email</small>
                </div>
                <div class="step <?= $step === 'verify' ? 'active' : ($step === 'reset' ? 'completed' : '') ?>">
                    <small>2. Verify OTP</small>
                </div>
                <div class="step <?= $step === 'reset' ? 'active' : '' ?>">
                    <small>3. New Password</small>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="bi bi-check-circle"></i> <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($step === 'email'): ?>
            <!-- Step 1: Enter Email -->
            <form method="POST">
                <input type="hidden" name="reset_step" value="email">
                
                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="Enter your registered email">
                    </div>
                    <small class="text-muted">We'll send an OTP to verify your identity</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-reset w-100">
                    <i class="bi bi-send"></i> Send Reset OTP
                </button>
            </form>
            
            <?php elseif ($step === 'verify'): ?>
            <!-- Step 2: Verify OTP -->
            <form method="POST">
                <input type="hidden" name="reset_step" value="verify">
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Check your email for the OTP
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Enter OTP</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="text" name="otp" class="form-control text-center" required
                               placeholder="ABC123" maxlength="6" 
                               style="font-size: 1.5rem; letter-spacing: 0.5rem;"
                               autocomplete="off">
                    </div>
                    <small class="text-muted">OTP is valid for 30 minutes</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-reset w-100">
                    <i class="bi bi-check-circle"></i> Verify OTP
                </button>
            </form>
            
            <?php elseif ($step === 'reset'): ?>
            <!-- Step 3: Reset Password -->
            <form method="POST">
                <input type="hidden" name="reset_step" value="reset">
                
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required
                               placeholder="Enter new password" minlength="8">
                    </div>
                    <small class="text-muted">Minimum 8 characters</small>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control" required
                               placeholder="Confirm new password" minlength="8">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-reset w-100">
                    <i class="bi bi-check-circle"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
