<?php
require_once 'config/config.php';
require_once 'includes/Settings.php';
require_once 'includes/EmailHelper.php';

// Redirect if already logged in
if (Security::isLoggedIn('user')) {
    redirect(BASE_URL . '/user/dashboard.php');
}

$email = Security::sanitize($_GET['email'] ?? '');
if (empty($email)) {
    Session::setFlash('error', 'Invalid verification request.', 'danger');
    redirect(BASE_URL . '/register.php');
}

// Check if there's pending registration
$pending_registration = Session::get('pending_registration');
if (!$pending_registration || $pending_registration['email'] !== $email) {
    Session::setFlash('error', 'Invalid or expired registration session.', 'danger');
    redirect(BASE_URL . '/register.php');
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'Invalid request. Please try again.', 'danger');
    } else {
        $otp_code = Security::sanitize($_POST['otp_code'] ?? '');
        
        if (empty($otp_code)) {
            Session::setFlash('error', 'Please enter the OTP code.', 'danger');
        } else {
            $db = Database::getInstance();
            
            // Verify OTP
            $otp_record = $db->fetchOne(
                "SELECT * FROM user_otps 
                WHERE email = ? 
                AND otp_code = ? 
                AND purpose = 'registration' 
                AND is_verified = 0 
                AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1",
                [$email, $otp_code]
            );
            
            if ($otp_record) {
                // Mark OTP as verified
                $db->update('user_otps', ['is_verified' => 1], 'id = ?', [$otp_record['id']]);
                
                // Create user account
                $user_id = $db->insert('users', [
                    'full_name' => $pending_registration['full_name'],
                    'email' => $pending_registration['email'],
                    'phone' => $pending_registration['phone'],
                    'password' => $pending_registration['password'],
                    'auth_provider' => 'email',
                    'status' => 'active',
                    'email_verified' => 1
                ]);
                
                if ($user_id) {
                    // Send welcome email
                    $emailHelper = new EmailHelper();
                    $emailHelper->sendWelcomeEmail($email, $pending_registration['full_name']);
                    
                    // Clear pending registration
                    Session::remove('pending_registration');
                    
                    // Auto login
                    Session::set('user_id', $user_id);
                    Session::set('user_email', $email);
                    Session::set('user_name', $pending_registration['full_name']);
                    
                    Session::setFlash('success', 'Registration successful! Welcome to ' . SITE_NAME, 'success');
                    redirect(BASE_URL . '/user/dashboard.php');
                } else {
                    Session::setFlash('error', 'Failed to create account. Please try again.', 'danger');
                }
            } else {
                Session::setFlash('error', 'Invalid or expired OTP. Please try again.', 'danger');
            }
        }
    }
}

// Handle resend OTP
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    if (!Security::checkRateLimit('resend_otp_' . getUserIP(), 3, 300)) {
        Session::setFlash('error', 'Too many resend attempts. Please wait a few minutes.', 'danger');
    } else {
        $db = Database::getInstance();
        
        // Generate new OTP
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiry_minutes = Settings::get('otp_expiry_minutes', '10');
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));
        
        // Delete old OTPs
        $db->delete('user_otps', 'email = ? AND purpose = ?', [$email, 'registration']);
        
        // Store new OTP
        $db->insert('user_otps', [
            'email' => $email,
            'otp_code' => $otp,
            'otp_type' => 'email',
            'purpose' => 'registration',
            'expires_at' => $expires_at
        ]);
        
        // Send OTP
        $emailHelper = new EmailHelper();
        if ($emailHelper->sendOTP($email, $otp, $pending_registration['full_name'])) {
            Session::setFlash('success', 'New OTP sent to your email.', 'success');
        } else {
            Session::setFlash('error', 'Failed to send OTP. Please try again.', 'danger');
        }
    }
    redirect(BASE_URL . '/verify-otp.php?email=' . urlencode($email));
}

$page_title = 'Verify OTP';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-check text-primary" style="font-size: 3rem;"></i>
                        <h2 class="mt-3">Verify Your Email</h2>
                        <p class="text-muted">We've sent a 6-digit code to<br><strong><?= htmlspecialchars($email) ?></strong></p>
                    </div>
                    
                    <?php
                    $flash = Session::getFlash('error');
                    if ($flash):
                    ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                        <?= $flash['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php
                    $flash_success = Session::getFlash('success');
                    if ($flash_success):
                    ?>
                    <div class="alert alert-<?= $flash_success['type'] ?> alert-dismissible fade show">
                        <?= $flash_success['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= Security::getCSRFTokenField() ?>
                        
                        <div class="mb-4">
                            <label for="otp_code" class="form-label text-center d-block">Enter OTP Code</label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg text-center" 
                                id="otp_code" 
                                name="otp_code" 
                                maxlength="6" 
                                pattern="[0-9]{6}"
                                placeholder="000000"
                                required
                                autofocus
                                style="letter-spacing: 0.5rem; font-size: 1.5rem; font-weight: bold;">
                            <small class="text-muted d-block text-center mt-2">
                                Code expires in <?= Settings::get('otp_expiry_minutes', '10') ?> minutes
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3">Verify & Continue</button>
                    </form>
                    
                    <div class="text-center">
                        <p class="mb-2 text-muted">Didn't receive the code?</p>
                        <a href="<?= BASE_URL ?>/verify-otp.php?email=<?= urlencode($email) ?>&resend=1" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-repeat"></i> Resend OTP
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/register.php" class="text-muted">
                            <i class="bi bi-arrow-left"></i> Back to Registration
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-focus and format OTP input
document.getElementById('otp_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
});

// Auto-submit when 6 digits entered
document.getElementById('otp_code').addEventListener('input', function(e) {
    if (this.value.length === 6) {
        // Optional: auto-submit after brief delay
        setTimeout(() => {
            if (this.value.length === 6) {
                this.form.submit();
            }
        }, 500);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
