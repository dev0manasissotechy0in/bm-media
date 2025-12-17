<?php
require_once 'config/config.php';
require_once 'includes/Settings.php';

// Redirect if already logged in
if (Security::isLoggedIn('user')) {
    redirect(BASE_URL . '/user/dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        Session::setFlash('error', 'Invalid request. Please try again.', 'danger');
    } else {
        $email = Security::sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            Session::setFlash('error', 'Please fill in all fields.', 'danger');
        } else {
            // Rate limiting
            if (!Security::checkRateLimit('login_' . getUserIP(), 5, 300)) {
                Session::setFlash('error', 'Too many login attempts. Please try again later.', 'danger');
            } else {
                $db = Database::getInstance();
                $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
                
                if ($user && Security::verifyPassword($password, $user['password'])) {
                    // Check email verification
                    if (!$user['email_verified']) {
                        // Store email in session for resend page
                        Session::set('unverified_email', $user['email']);
                        Session::setFlash('error', 'Your email is not verified. Redirecting to verification page...', 'warning');
                        redirect(BASE_URL . '/resend-verification.php');
                    } else {
                        // Login successful
                        Session::set('user_id', $user['id']);
                        Session::set('user_email', $user['email']);
                        Session::set('user_name', $user['full_name']);
                        
                        // Update last login
                        $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
                        
                        Session::setFlash('success', 'Login successful!', 'success');
                        redirect(BASE_URL . '/user/dashboard.php');
                    }
                } else {
                    Session::setFlash('error', 'Invalid email or password.', 'danger');
                }
            }
        }
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Login</h2>
                    
                    <?php
                    $flash = Session::getFlash('error');
                    if ($flash):
                    ?>
                    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
                        <?= htmlspecialchars($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= Security::getCSRFTokenField() ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                    </form>
                    
                    <div class="text-center mb-3">
                        <a href="<?= BASE_URL ?>/forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                    </div>
                    
                    <hr>
                    
                    <div class="text-center mb-3">
                        <p class="mb-2">Alternative login options:</p>
                        <div class="d-grid gap-2">
                            <?php if (Settings::isOtpEnabled()): ?>
                            <a href="<?= BASE_URL ?>/login-otp.php" class="btn btn-success">
                                <i class="bi bi-shield-lock"></i> Login with OTP (No Password)
                            </a>
                            <?php endif; ?>
                            
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
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="<?= BASE_URL ?>/register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
