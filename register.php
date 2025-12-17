<?php
require_once 'config/config.php';
require_once 'includes/Settings.php';
require_once 'includes/EmailHelper.php';

// Redirect if already logged in
if (Security::isLoggedIn('user')) {
    redirect(BASE_URL . '/user/dashboard.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'Invalid request. Please try again.', 'danger');
    } else {
        $full_name = Security::sanitize($_POST['full_name'] ?? '');
        $email = Security::sanitize($_POST['email'] ?? '');
        $phone = Security::sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Validation
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        
        if (empty($email) || !Security::validateEmail($email)) {
            $errors[] = 'Valid email is required.';
        }
        
        if (!empty($phone) && !Security::validatePhone($phone)) {
            $errors[] = 'Valid phone number is required.';
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($errors)) {
            $db = Database::getInstance();
            
            // Check if email already exists
            if ($db->exists('users', 'email = ?', [$email])) {
                $errors[] = 'Email already registered.';
            }
            
            // Check if phone already exists
            if (!empty($phone) && $db->exists('users', 'phone = ?', [$phone])) {
                $errors[] = 'Phone number already registered.';
            }
            
            if (empty($errors)) {
                // Rate limiting
                if (!Security::checkRateLimit('register_' . getUserIP(), 3, 3600)) {
                    $errors[] = 'Too many registration attempts. Please try again later.';
                } else {
                    // Check if OTP is enabled
                    if (Settings::isOtpEnabled()) {
                        // Generate OTP
                        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                        $expiry_minutes = Settings::get('otp_expiry_minutes', '10');
                        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));
                        
                        // Store registration data in session
                        Session::set('pending_registration', [
                            'full_name' => $full_name,
                            'email' => $email,
                            'phone' => $phone,
                            'password' => Security::hashPassword($password)
                        ]);
                        
                        // Delete old OTPs for this email
                        $db->delete('user_otps', 'email = ? AND purpose = ?', [$email, 'registration']);
                        
                        // Store OTP
                        $db->insert('user_otps', [
                            'email' => $email,
                            'otp_code' => $otp,
                            'otp_type' => 'email',
                            'purpose' => 'registration',
                            'expires_at' => $expires_at
                        ]);
                        
                        // Send OTP email using Auth SMTP
                        $emailSent = false;
                        try {
                            $emailHelper = new EmailHelper('auth');
                            $emailSent = $emailHelper->sendOTP($email, $otp, $full_name);
                        } catch (Exception $e) {
                            error_log('OTP Email Error: ' . $e->getMessage());
                            $emailSent = false;
                        }
                        
                        // Redirect to OTP verification page regardless of email status
                        // User can get OTP from database if email fails
                        if ($emailSent) {
                            Session::setFlash('success', 'OTP sent to your email. Please check your inbox.', 'info');
                        } else {
                            Session::setFlash('warning', 'OTP generated but email may not have been sent. Check with administrator or use backup method.', 'warning');
                        }
                        redirect(BASE_URL . '/verify-otp.php?email=' . urlencode($email));
                    } else {
                        // Direct registration without OTP
                        $user_id = $db->insert('users', [
                            'full_name' => $full_name,
                            'email' => $email,
                            'phone' => $phone,
                            'password' => Security::hashPassword($password),
                            'auth_provider' => 'email',
                            'status' => 'active'
                        ]);
                        
                        if ($user_id) {
                            // Send welcome email using Auth SMTP
                            $emailHelper = new EmailHelper('auth');
                            $emailHelper->sendWelcomeEmail($email, $full_name);
                            
                            // Auto login
                            Session::set('user_id', $user_id);
                            Session::set('user_email', $email);
                            Session::set('user_name', $full_name);
                            
                            Session::setFlash('success', 'Registration successful! Welcome to ' . SITE_NAME, 'success');
                            redirect(BASE_URL . '/user/dashboard.php');
                        } else {
                            $errors[] = 'Registration failed. Please try again.';
                        }
                    }
                }
            }
        }
        
        if (!empty($errors)) {
            Session::setFlash('error', implode('<br>', $errors), 'danger');
        }
    }
}

$page_title = 'Register';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Create Account</h2>
                    
                    <?php
                    $flash = Session::getFlash('error');
                    if ($flash):
                    ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                        <?= $flash['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= Security::getCSRFTokenField() ?>
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="<?= $_POST['full_name'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= $_POST['email'] ?? '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number (Optional)</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?= $_POST['phone'] ?? '' ?>" placeholder="10-digit mobile number">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                            <label class="form-check-label" for="agree_terms">
                                I agree to the <a href="<?= BASE_URL ?>/terms.php" target="_blank">Terms & Conditions</a> and <a href="<?= BASE_URL ?>/privacy-policy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Create Account</button>
                    </form>
                    
                    <hr>
                    
                    <?php if (Settings::isGoogleLoginEnabled() || Settings::isFacebookLoginEnabled()): ?>
                    <div class="text-center mb-3">
                        <p class="mb-2">Or register with:</p>
                        <div class="d-grid gap-2">
                            <?php if (Settings::isGoogleLoginEnabled()): ?>
                            <a href="<?= BASE_URL ?>/auth/google-login.php" class="btn btn-outline-danger">
                                <i class="bi bi-google"></i> Google
                            </a>
                            <?php endif; ?>
                            
                            <?php if (Settings::isFacebookLoginEnabled()): ?>
                            <a href="<?= BASE_URL ?>/auth/facebook-login.php" class="btn btn-outline-primary">
                                <i class="bi bi-facebook"></i> Facebook
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <hr>
                    <?php endif; ?>
                    
                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="<?= BASE_URL ?>/login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
