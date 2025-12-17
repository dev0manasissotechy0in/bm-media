<?php
/**
 * Admin Login Page with OTP, Remember Me, and Forgot Password
 */

// Suppress deprecation warnings before loading any libraries
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '1');

require_once '../config/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check remember me cookie
if (!isset($_SESSION['admin_logged_in']) && isset($_COOKIE['admin_remember'])) {
    $db = Database::getInstance();
    $token = $_COOKIE['admin_remember'];
    $admin = $db->fetchOne("SELECT * FROM admin_users WHERE remember_token = ? AND status = 'active'", [$token]);
    
    if ($admin) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_email'] = $admin['email'];
        
        header('Location: dashboard.php');
        exit;
    }
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$show_otp = false;
$temp_admin_id = $_SESSION['temp_admin_id'] ?? null;

// If there's a temp admin ID in session, show OTP form
if ($temp_admin_id) {
    $show_otp = true;
}

// Handle logout message
if (isset($_GET['logout'])) {
    $success = 'You have been logged out successfully';
}

// Handle password reset message
if (isset($_GET['reset'])) {
    $success = 'Password reset successfully. Please login with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance();
    
    // Step 1: Email/Password verification
    if (isset($_POST['login_step']) && $_POST['login_step'] === 'credentials') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password';
        } else {
            $admin = $db->fetchOne("SELECT * FROM admin_users WHERE email = ? AND status = 'active'", [$email]);
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Generate 6-digit alphanumeric OTP
                $otp = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6));
                $otp_expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Save OTP to database
                $db->update('admin_users', [
                    'otp_code' => $otp,
                    'otp_expires_at' => $otp_expires,
                    'otp_verified' => 0
                ], 'id = ?', [$admin['id']]);
                
                // Store admin ID temporarily
                $_SESSION['temp_admin_id'] = $admin['id'];
                $_SESSION['temp_admin_email'] = $admin['email'];
                $_SESSION['remember_me'] = isset($_POST['remember_me']) ? 1 : 0;
                
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
                                <h2>Admin Login OTP</h2>
                                <p>" . SITE_NAME . "</p>
                            </div>
                            <div class='content'>
                                <p>Hello <strong>" . htmlspecialchars($admin['full_name']) . "</strong>,</p>
                                <p>You have requested to login to the admin panel. Please use the following OTP to complete your login:</p>
                                
                                <div class='otp-box'>$otp</div>
                                
                                <p><strong>Important:</strong></p>
                                <ul>
                                    <li>This OTP is valid for <strong>10 minutes</strong></li>
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
                
                // Try to send email with timeout protection
                $emailSent = false;
                try {
                    set_time_limit(30); // Limit email sending to 30 seconds
                    $emailSent = $emailService->sendEmail(
                        'auth',
                        $admin['email'],
                        'Admin Login OTP - ' . SITE_NAME,
                        $emailBody,
                        $admin['full_name']
                    );
                } catch (Exception $e) {
                    error_log("Email sending failed: " . $e->getMessage());
                    $emailSent = false;
                }
                
                if ($emailSent) {
                    $success = "OTP has been sent to your email address. Please check your inbox and enter the code below.";
                    $show_otp = true;
                } else {
                    // Fallback: Show OTP on screen if email fails
                    $success = "Email service is currently unavailable. Your OTP is: <strong style='font-size: 1.5rem; color: #667eea; letter-spacing: 5px;'>$otp</strong><br><small class='text-muted'>Please enter this code below. <a href='smtp-diagnose.php' target='_blank' class='alert-link'>Diagnose SMTP Issue</a></small>";
                    $show_otp = true;
                }
            } else {
                $error = 'Invalid email or password';
            }
        }
    }
    
    // Step 2: OTP verification
    if (isset($_POST['login_step']) && $_POST['login_step'] === 'otp') {
        $otp = strtoupper(trim($_POST['otp'] ?? ''));
        $temp_admin_id = $_SESSION['temp_admin_id'] ?? null;
        
        if (empty($otp)) {
            $error = 'Please enter the OTP';
            $show_otp = true;
        } elseif (!$temp_admin_id) {
            $error = 'Session expired. Please login again.';
        } else {
            $admin = $db->fetchOne(
                "SELECT * FROM admin_users WHERE id = ? AND otp_code = ? AND otp_expires_at > NOW() AND status = 'active'",
                [$temp_admin_id, $otp]
            );
            
            if ($admin) {
                // Mark OTP as verified
                $db->update('admin_users', [
                    'otp_verified' => 1,
                    'otp_code' => null,
                    'last_login' => date('Y-m-d H:i:s')
                ], 'id = ?', [$admin['id']]);
                
                // Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_email'] = $admin['email'];
                
                // Handle "Remember Me" (30 days)
                if ($_SESSION['remember_me']) {
                    $remember_token = bin2hex(random_bytes(32));
                    $db->update('admin_users', [
                        'remember_token' => $remember_token
                    ], 'id = ?', [$admin['id']]);
                    
                    setcookie('admin_remember', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                }
                
                // Clean up temporary session data
                unset($_SESSION['temp_admin_id']);
                unset($_SESSION['temp_admin_email']);
                unset($_SESSION['remember_me']);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid or expired OTP';
                $show_otp = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= SITE_NAME ?></title>
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
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2 class="mb-0"><i class="bi bi-shield-lock"></i> Admin Panel</h2>
            <p class="mb-0 mt-2 opacity-75"><?= SITE_NAME ?></p>
        </div>
        <div class="login-body">
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
            
            <?php if (!$show_otp): ?>
            <!-- Step 1: Email & Password -->
            <form method="POST" action="">
                <input type="hidden" name="login_step" value="credentials">
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               placeholder="admin@example.com">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required 
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" name="remember_me" class="form-check-input" id="rememberMe" value="1">
                    <label class="form-check-label" for="rememberMe">
                        Keep me signed in for 30 days
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right"></i> Continue to OTP
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="forgot-password.php" class="text-decoration-none">
                    <i class="bi bi-key"></i> Forgot Password?
                </a>
            </div>
            <?php else: ?>
            <!-- Step 2: OTP Verification -->
            <form method="POST" action="">
                <input type="hidden" name="login_step" value="otp">
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Enter the 6-digit OTP sent to your email
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Enter OTP</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="text" name="otp" class="form-control text-center" required 
                               placeholder="ABC123" maxlength="6" style="font-size: 1.5rem; letter-spacing: 0.5rem;"
                               autocomplete="off">
                    </div>
                    <small class="text-muted">OTP is valid for 10 minutes</small>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-check-circle"></i> Verify & Login
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to Login
                </a>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i> Secure Admin Area
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent any external script errors from interfering
        window.addEventListener('error', function(e) {
            // Only log errors, don't prevent form submission
            if (e.filename && e.filename.includes('page-events')) {
                e.stopImmediatePropagation();
                e.preventDefault();
            }
        }, true);
        
        // Auto-focus on OTP input if shown
        <?php if ($show_otp): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const otpInput = document.querySelector('input[name="otp"]');
            if (otpInput) {
                otpInput.focus();
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
