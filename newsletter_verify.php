<?php
/**
 * Newsletter Verification Page
 */

require_once 'config/config.php';
require_once 'includes/Newsletter.php';

$token = $_GET['token'] ?? null;
$message = '';
$status = '';

if ($token) {
    $result = verifySubscription($token);
    $message = $result['message'];
    $status = $result['success'] ? 'success' : 'error';
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    <?php if ($status === 'success'): ?>
                        <div class="text-success mb-3">
                            <i class="bi bi-check-circle" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="mb-3">Email Verified!</h3>
                        <p class="text-muted"><?= htmlspecialchars($message) ?></p>
                        <p>You will now receive our newsletter updates.</p>
                    <?php elseif ($status === 'error'): ?>
                        <div class="text-danger mb-3">
                            <i class="bi bi-x-circle" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="mb-3">Verification Failed</h3>
                        <p class="text-muted"><?= htmlspecialchars($message) ?></p>
                    <?php else: ?>
                        <div class="text-warning mb-3">
                            <i class="bi bi-exclamation-triangle" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="mb-3">Invalid Link</h3>
                        <p class="text-muted">The verification link is invalid or has expired.</p>
                    <?php endif; ?>
                    
                    <a href="<?= BASE_URL ?>" class="btn btn-primary mt-3">
                        <i class="bi bi-house"></i> Go to Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
