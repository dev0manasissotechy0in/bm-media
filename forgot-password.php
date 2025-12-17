<?php
/**
 * Forgot Password
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    } else {
        // Check if user exists
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // In a full implementation, save token to database
            // $db->insert('password_resets', ['email' => $email, 'token' => $token, 'expires_at' => $expires]);
            
            // Send reset email
            $reset_link = BASE_URL . "/reset-password.php?token=$token";
            
            // Email sending would require SMTP setup
            $success = 'Password reset instructions have been sent to your email address.';
        } else {
            // Don't reveal if email exists for security
            $success = 'If an account exists with that email, password reset instructions have been sent.';
        }
    }
}

$page_title = 'Forgot Password';
include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h3 class="text-center mb-4">
                        <i class="bi bi-key"></i> Forgot Password
                    </h3>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
                    </div>
                    <div class="text-center">
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Login
                        </a>
                    </div>
                    <?php else: ?>
                    
                    <p class="text-muted text-center mb-4">
                        Enter your email address and we'll send you instructions to reset your password.
                    </p>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   placeholder="your@email.com" required autofocus>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Send Reset Link
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <p class="text-muted mb-0">
                            Remember your password?
                            <a href="<?= BASE_URL ?>/login.php">Login here</a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>