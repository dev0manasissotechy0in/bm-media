<?php
/**
 * Email Verification Page
 * Verifies user email using token from verification email
 */

require_once 'config/config.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if (empty($token)) {
    $error = 'Invalid verification link.';
} else {
    $db = Database::getInstance();
    
    try {
        // Find verification token
        $verification = $db->fetchOne("
            SELECT * FROM email_verification_tokens 
            WHERE token = ? AND verified_at IS NULL
        ", [$token]);
        
        if (!$verification) {
            $error = 'Invalid or already used verification link.';
        } elseif (strtotime($verification['expires_at']) < time()) {
            $error = 'This verification link has expired. Please request a new one.';
        } else {
            // Mark token as verified
            $db->update('email_verification_tokens', 
                ['verified_at' => date('Y-m-d H:i:s')], 
                'id = ?', 
                [$verification['id']]
            );
            
            // Mark user email as verified
            $db->update('users', 
                ['email_verified' => 1], 
                'id = ?', 
                [$verification['user_id']]
            );
            
            $success = true;
        }
    } catch (Exception $e) {
        $error = 'An error occurred during verification. Please try again.';
    }
}

$page_title = 'Email Verification';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <?php if ($success): ?>
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <div style="font-size: 80px; color: #28a745;">✓</div>
                    </div>
                    <h2 class="text-success mb-3">Email Verified!</h2>
                    <p class="lead mb-4">Your email address has been successfully verified.</p>
                    <p class="text-muted mb-4">You can now login and access all features of Bracodd Media.</p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right"></i> Login Now
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <div style="font-size: 80px; color: #dc3545;">✗</div>
                    </div>
                    <h2 class="text-danger mb-3">Verification Failed</h2>
                    <p class="lead mb-4"><?= htmlspecialchars($error) ?></p>
                    
                    <?php if (strpos($error, 'expired') !== false): ?>
                    <div class="alert alert-info">
                        <p class="mb-3">Your verification link has expired for security reasons.</p>
                        <a href="<?= BASE_URL ?>/resend-verification.php" class="btn btn-primary">
                            <i class="bi bi-envelope"></i> Resend Verification Email
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-house"></i> Go to Homepage
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
