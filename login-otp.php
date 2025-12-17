<?php
/**
 * OTP Login Page
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Session.php';
require_once 'includes/OTPService.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

$db = Database::getInstance();
$otpService = new OTPService();
$errors = [];
$success = '';
$step = $_POST['step'] ?? $_GET['step'] ?? 'email';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'email') {
        // Step 1: Send OTP
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        } else {
            $result = $otpService->sendOTP($email, 'login');
            
            if ($result['success']) {
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_sent_at'] = time();
                $step = 'verify';
                $success = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
    } 
    elseif ($step === 'verify') {
        // Step 2: Verify OTP
        $email = $_SESSION['otp_email'] ?? '';
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($otp)) {
            $errors[] = 'Please enter the OTP code';
        } elseif (strlen($otp) !== 6) {
            $errors[] = 'OTP must be 6 characters';
        } else {
            $result = $otpService->verifyOTP($email, $otp, 'login');
            
            if ($result['success']) {
                $user = $result['user'];
                
                // Check email verification
                if (!$user['email_verified']) {
                    $errors[] = 'Please verify your email before logging in. Check your inbox for the verification link.';
                } else {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Clean up OTP session data
                    unset($_SESSION['otp_email']);
                    unset($_SESSION['otp_sent_at']);
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    } else {
                        header('Location: ' . BASE_URL);
                    }
                    exit;
                }
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}

$page_title = 'Login with OTP';
include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="bi bi-shield-lock" style="font-size: 3rem; color: #007bff;"></i>
                        <h3 class="mt-3">Login with OTP</h3>
                        <p class="text-muted">Enter your email to receive a one-time password</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($step === 'email'): ?>
                    <!-- Step 1: Enter Email -->
                    <form method="post">
                        <input type="hidden" name="step" value="email">
                        
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-lg" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   placeholder="your@email.com" required autofocus>
                            <small class="text-muted">We'll send a 6-character code to your email</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-envelope"></i> Send OTP Code
                        </button>
                    </form>
                    
                    <?php else: ?>
                    <!-- Step 2: Verify OTP -->
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        OTP sent to <strong><?= htmlspecialchars($_SESSION['otp_email']) ?></strong>
                        <br><small>Code expires in 10 minutes</small>
                    </div>
                    
                    <form method="post" id="otpForm">
                        <input type="hidden" name="step" value="verify">
                        
                        <div class="mb-3">
                            <label class="form-label">Enter OTP Code</label>
                            <input type="text" name="otp" class="form-control form-control-lg text-center" 
                                   style="letter-spacing: 0.5em; font-size: 1.5rem;"
                                   maxlength="6" placeholder="ABC123" required autofocus 
                                   oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')">
                            <small class="text-muted">6-character code (letters and numbers)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="bi bi-check-circle"></i> Verify & Login
                        </button>
                    </form>
                    
                    <form method="post" class="text-center">
                        <input type="hidden" name="step" value="email">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['otp_email']) ?>">
                        <button type="submit" class="btn btn-link">
                            <i class="bi bi-arrow-counterclockwise"></i> Didn't receive? Send again
                        </button>
                    </form>
                    
                    <div class="text-center mt-2">
                        <a href="?step=email" class="btn btn-link">
                            <i class="bi bi-arrow-left"></i> Change email address
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="text-muted mb-2">Or sign in with:</p>
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline-primary">
                                <i class="bi bi-key"></i> Traditional Login (Password)
                            </a>
                        </div>
                        
                        <p class="text-muted mt-3 mb-0">
                            Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3 shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Why OTP Login?</h6>
                    <ul class="small mb-0">
                        <li>No need to remember passwords</li>
                        <li>More secure than traditional login</li>
                        <li>Quick and easy verification</li>
                        <li>Works on any device</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Auto-submit when 6 characters entered
document.addEventListener('DOMContentLoaded', function() {
    const otpInput = document.querySelector('input[name="otp"]');
    if (otpInput) {
        otpInput.addEventListener('input', function() {
            if (this.value.length === 6) {
                // Auto-submit after brief delay
                setTimeout(() => {
                    document.getElementById('otpForm').submit();
                }, 500);
            }
        });
    }
});

// Countdown timer for OTP expiry
<?php if ($step === 'verify' && isset($_SESSION['otp_sent_at'])): ?>
let expiryTime = <?= $_SESSION['otp_sent_at'] + 600 ?>; // 10 minutes
setInterval(function() {
    let now = Math.floor(Date.now() / 1000);
    let remaining = expiryTime - now;
    
    if (remaining <= 0) {
        alert('OTP has expired. Please request a new one.');
        window.location.href = '?step=email';
    }
}, 1000);
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>