<?php
/**
 * Newsletter Unsubscribe Page
 */

require_once 'config/config.php';
require_once 'includes/Newsletter.php';

$token = $_GET['token'] ?? null;
$email = $_GET['email'] ?? null;
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? null;
    $token = $_POST['token'] ?? null;
    
    if ($email) {
        $result = unsubscribeFromNewsletter($email, $token);
        $message = $result['message'];
        $status = $result['success'] ? 'success' : 'error';
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <?php if ($status === 'success'): ?>
                        <div class="text-center">
                            <div class="text-success mb-3">
                                <i class="bi bi-check-circle" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="mb-3">Successfully Unsubscribed</h3>
                            <p class="text-muted"><?= htmlspecialchars($message) ?></p>
                            <p>We're sorry to see you go. You can resubscribe anytime.</p>
                            <a href="<?= BASE_URL ?>" class="btn btn-primary mt-3">
                                <i class="bi bi-house"></i> Go to Homepage
                            </a>
                        </div>
                    <?php elseif ($status === 'error'): ?>
                        <div class="text-center">
                            <div class="text-danger mb-3">
                                <i class="bi bi-x-circle" style="font-size: 4rem;"></i>
                            </div>
                            <h3 class="mb-3">Error</h3>
                            <p class="text-muted"><?= htmlspecialchars($message) ?></p>
                        </div>
                    <?php else: ?>
                        <h3 class="text-center mb-4">Unsubscribe from Newsletter</h3>
                        <p class="text-muted text-center mb-4">
                            We're sorry to see you go. Please confirm your email to unsubscribe.
                        </p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($email ?? '') ?>" 
                                       required placeholder="your@email.com">
                            </div>
                            
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-envelope-slash"></i> Unsubscribe
                            </button>
                            
                            <div class="text-center mt-3">
                                <a href="<?= BASE_URL ?>" class="text-muted">
                                    <small>Cancel and return to homepage</small>
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
