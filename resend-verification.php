<?php
/**
 * Resend Email Verification Link
 */

require_once 'config/config.php';
require_once 'includes/EmailService.php';

$success = false;
$error = '';

// Get email from session if available (from failed login attempt)
$prefill_email = $_SESSION['unverified_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        $db = Database::getInstance();
        
        // Find user
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
        
        if (!$user) {
            $error = 'Email address not found.';
        } elseif ($user['email_verified']) {
            $error = 'This email is already verified. You can login now.';
        } else {
            // Delete old tokens
            $db->delete('email_verification_tokens', 'user_id = ?', [$user['id']]);
            
            // Generate new token
            $verificationToken = bin2hex(random_bytes(32));
            
            $db->insert('email_verification_tokens', [
                'user_id' => $user['id'],
                'token' => $verificationToken,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
            ]);
            
            // Send verification email
            $emailService = new EmailService();
            $verificationLink = BASE_URL . '/verify-email.php?token=' . $verificationToken;
            
            $emailBody = file_get_contents(__DIR__ . '/email-templates/verify-email.html');
            $emailBody = str_replace('{{USER_NAME}}', htmlspecialchars($user['full_name']), $emailBody);
            $emailBody = str_replace('{{VERIFICATION_LINK}}', $verificationLink, $emailBody);
            
            $emailService->sendEmail('auth', $email, 'Verify Your Email Address', $emailBody, $user['full_name']);
            
            $success = true;
        }
    }
}

$page_title = 'Resend Verification Email';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-envelope-check" style="font-size: 3rem; color: #6366f1;"></i>
                        <h2 class="mt-3">Resend Verification Email</h2>
                        <p class="text-muted">Enter your email to receive a new verification link</p>
                    </div>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <strong>Email Sent!</strong><br>
                        Please check your inbox for the verification link. It will expire in 24 hours.
                    </div>
                    <div class="text-center mt-4">
                        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">
                            Go to Login
                        </a>
                    </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? $prefill_email) ?>" required autofocus>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                <i class="bi bi-send"></i> Send Verification Email
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="<?= BASE_URL ?>/login.php" class="text-decoration-none">
                                <i class="bi bi-arrow-left"></i> Back to Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
