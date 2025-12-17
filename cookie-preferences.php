<?php
/**
 * Cookie Preferences Page
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Session.php';

$db = Database::getInstance();
$success = '';

// Get current preferences
$preferences = null;
if (isset($_SESSION['user_id'])) {
    $preferences = $db->fetchOne("SELECT * FROM cookie_preferences WHERE user_id = ?", [$_SESSION['user_id']]);
} else {
    $session_id = session_id();
    $preferences = $db->fetchOne("SELECT * FROM cookie_preferences WHERE session_id = ?", [$session_id]);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'user_id' => $_SESSION['user_id'] ?? null,
        'session_id' => session_id(),
        'necessary_cookies' => 1,
        'functional_cookies' => isset($_POST['functional']) ? 1 : 0,
        'analytics_cookies' => isset($_POST['analytics']) ? 1 : 0,
        'marketing_cookies' => isset($_POST['marketing']) ? 1 : 0,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];
    
    if ($preferences) {
        // Update existing
        if (isset($_SESSION['user_id'])) {
            $db->update('cookie_preferences', $data, 'user_id = ?', [$_SESSION['user_id']]);
        } else {
            $db->update('cookie_preferences', $data, 'session_id = ?', [session_id()]);
        }
    } else {
        // Insert new
        $db->insert('cookie_preferences', $data);
    }
    
    $preferences = (object)$data;
    $success = 'Your cookie preferences have been updated successfully.';
    
    // Update localStorage via JavaScript
    echo "<script>
        localStorage.setItem('cookie_preferences', JSON.stringify({
            necessary: true,
            functional: " . ($data['functional_cookies'] ? 'true' : 'false') . ",
            analytics: " . ($data['analytics_cookies'] ? 'true' : 'false') . ",
            marketing: " . ($data['marketing_cookies'] ? 'true' : 'false') . ",
            timestamp: '" . date('c') . "'
        }));
    </script>";
}

$page_title = 'Cookie Preferences';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="mb-4">
                        <i class="bi bi-shield-check"></i> Cookie Preferences
                    </h2>
                    
                    <p class="text-muted">
                        We use cookies to enhance your browsing experience, serve personalized content, 
                        and analyze our traffic. You can choose which types of cookies you want to allow.
                    </p>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <button class="btn-close" data-bs-dismiss="alert"></button>
                        <i class="bi bi-check-circle"></i> <?= $success ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <!-- Necessary Cookies -->
                        <div class="card mb-3 border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">
                                            <i class="bi bi-check-circle text-primary"></i> 
                                            Necessary Cookies
                                        </h5>
                                        <p class="card-text text-muted">
                                            These cookies are essential for the website to function properly. 
                                            They enable basic features like page navigation, secure areas, and authentication.
                                        </p>
                                        <small class="text-muted">
                                            <strong>Examples:</strong> Session cookies, security tokens, login status
                                        </small>
                                    </div>
                                    <div class="form-check form-switch ms-3">
                                        <input type="checkbox" class="form-check-input" checked disabled>
                                        <label class="form-check-label text-success">Always Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Functional Cookies -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">
                                            <i class="bi bi-gear"></i> Functional Cookies
                                        </h5>
                                        <p class="card-text text-muted">
                                            These cookies enable enhanced functionality and personalization, 
                                            such as language preferences, theme settings, and saved articles.
                                        </p>
                                        <small class="text-muted">
                                            <strong>Examples:</strong> Language preference, dark mode, saved preferences
                                        </small>
                                    </div>
                                    <div class="form-check form-switch ms-3">
                                        <input type="checkbox" name="functional" class="form-check-input" 
                                               <?= ($preferences && $preferences->functional_cookies) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Analytics Cookies -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">
                                            <i class="bi bi-graph-up"></i> Analytics Cookies
                                        </h5>
                                        <p class="card-text text-muted">
                                            These cookies help us understand how visitors interact with our website 
                                            by collecting and reporting information anonymously.
                                        </p>
                                        <small class="text-muted">
                                            <strong>Examples:</strong> Google Analytics, page views, user behavior
                                        </small>
                                    </div>
                                    <div class="form-check form-switch ms-3">
                                        <input type="checkbox" name="analytics" class="form-check-input"
                                               <?= ($preferences && $preferences->analytics_cookies) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Marketing Cookies -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title">
                                            <i class="bi bi-megaphone"></i> Marketing Cookies
                                        </h5>
                                        <p class="card-text text-muted">
                                            These cookies are used to track visitors across websites to display 
                                            relevant ads and measure their effectiveness.
                                        </p>
                                        <small class="text-muted">
                                            <strong>Examples:</strong> Facebook Pixel, Google Ads, remarketing
                                        </small>
                                    </div>
                                    <div class="form-check form-switch ms-3">
                                        <input type="checkbox" name="marketing" class="form-check-input"
                                               <?= ($preferences && $preferences->marketing_cookies) ? 'checked' : '' ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle"></i> Save Preferences
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="acceptAll()">
                                Accept All Cookies
                            </button>
                        </div>
                    </form>
                    
                    <hr class="my-4">
                    
                    <div class="alert alert-info mb-0">
                        <h6><i class="bi bi-info-circle"></i> Your Privacy Matters</h6>
                        <p class="mb-2">
                            We respect your privacy and are committed to protecting your personal data. 
                            You can change your cookie preferences at any time by visiting this page.
                        </p>
                        <p class="mb-0">
                            <a href="<?= BASE_URL ?>/page/privacy-policy" class="alert-link">Privacy Policy</a> | 
                            <a href="<?= BASE_URL ?>/page/terms-of-service" class="alert-link">Terms of Service</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Current Status -->
            <?php if ($preferences): ?>
            <div class="card mt-3 shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-clock-history"></i> Current Cookie Status</h6>
                    <div class="row mt-3">
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                                <div class="small mt-1">Necessary</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <i class="bi bi-<?= $preferences->functional_cookies ? 'check-circle text-success' : 'x-circle text-danger' ?>" 
                                   style="font-size: 2rem;"></i>
                                <div class="small mt-1">Functional</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <i class="bi bi-<?= $preferences->analytics_cookies ? 'check-circle text-success' : 'x-circle text-danger' ?>" 
                                   style="font-size: 2rem;"></i>
                                <div class="small mt-1">Analytics</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-center">
                                <i class="bi bi-<?= $preferences->marketing_cookies ? 'check-circle text-success' : 'x-circle text-danger' ?>" 
                                   style="font-size: 2rem;"></i>
                                <div class="small mt-1">Marketing</div>
                            </div>
                        </div>
                    </div>
                    <p class="text-muted small mb-0 mt-3">
                        Last updated: <?= date('F j, Y g:i A', strtotime($preferences->updated_at)) ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function acceptAll() {
    document.querySelector('input[name="functional"]').checked = true;
    document.querySelector('input[name="analytics"]').checked = true;
    document.querySelector('input[name="marketing"]').checked = true;
    document.querySelector('form').submit();
}
</script>

<?php include 'includes/footer.php'; ?>