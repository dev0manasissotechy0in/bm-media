<?php
/**
 * Advanced Settings - REDIRECTED TO MERGED SETTINGS PAGE
 * This file now redirects to settings.php which contains all settings in tabs
 */

require_once '../config/config.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect to merged settings page
header('Location: settings.php');
exit;

$db = Database::getInstance();
$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_update = [
        // Social Login
        'google_login_enabled' => isset($_POST['google_login_enabled']) ? '1' : '0',
        'facebook_login_enabled' => isset($_POST['facebook_login_enabled']) ? '1' : '0',
        'google_client_id' => trim($_POST['google_client_id'] ?? ''),
        'google_client_secret' => trim($_POST['google_client_secret'] ?? ''),
        'facebook_app_id' => trim($_POST['facebook_app_id'] ?? ''),
        'facebook_app_secret' => trim($_POST['facebook_app_secret'] ?? ''),
        
        // Email Settings
        'smtp_host' => trim($_POST['smtp_host'] ?? ''),
        'smtp_port' => trim($_POST['smtp_port'] ?? ''),
        'smtp_username' => trim($_POST['smtp_username'] ?? ''),
        'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
        'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
        'site_email' => trim($_POST['site_email'] ?? ''),
        
        // OTP Settings
        'otp_enabled' => isset($_POST['otp_enabled']) ? '1' : '0',
        'otp_expiry_minutes' => (int)($_POST['otp_expiry_minutes'] ?? 10),
        
        // Social Media Links
        'social_facebook' => trim($_POST['social_facebook'] ?? ''),
        'social_twitter' => trim($_POST['social_twitter'] ?? ''),
        'social_instagram' => trim($_POST['social_instagram'] ?? ''),
        'social_youtube' => trim($_POST['social_youtube'] ?? '')
    ];
    
    // Update SMTP password only if provided
    if (!empty($_POST['smtp_password'])) {
        $settings_to_update['smtp_password'] = $_POST['smtp_password'];
    }
    
    foreach ($settings_to_update as $key => $value) {
        Settings::set($key, $value);
    }
    
    $success = 'Settings updated successfully!';
}

// Load current settings
Settings::load();

$page_title = 'Advanced Settings';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-gear-fill"></i> Advanced Settings</h1>
            <a href="settings.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Basic Settings
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#social-login">Social Login</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#otp">OTP Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#email">Email Settings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#social-media">Social Media</a>
                </li>
            </ul>

            <div class="tab-content">
                <!-- Social Login Tab -->
                <div class="tab-pane fade show active" id="social-login">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-google"></i> Google Login</h5>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="google_login_enabled" 
                                       id="googleLoginEnabled" <?= Settings::get('google_login_enabled') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="googleLoginEnabled">
                                    <strong>Enable Google Login</strong>
                                </label>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Google Client ID</label>
                                    <input type="text" name="google_client_id" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('google_client_id', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Google Client Secret</label>
                                    <input type="password" name="google_client_secret" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('google_client_secret', '')) ?>">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="card-title"><i class="bi bi-facebook"></i> Facebook Login</h5>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="facebook_login_enabled" 
                                       id="facebookLoginEnabled" <?= Settings::get('facebook_login_enabled') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="facebookLoginEnabled">
                                    <strong>Enable Facebook Login</strong>
                                </label>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Facebook App ID</label>
                                    <input type="text" name="facebook_app_id" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('facebook_app_id', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Facebook App Secret</label>
                                    <input type="password" name="facebook_app_secret" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('facebook_app_secret', '')) ?>">
                                </div>
                            </div>

                            <div class="alert alert-info mt-3">
                                <strong>Setup Instructions:</strong>
                                <ul class="mb-0">
                                    <li><strong>Google:</strong> Create OAuth credentials at <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                                    <li><strong>Facebook:</strong> Create app at <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                                    <li>Add redirect URI: <code><?= BASE_URL ?>/auth/google-callback.php</code> and <code><?= BASE_URL ?>/auth/facebook-callback.php</code></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OTP Settings Tab -->
                <div class="tab-pane fade" id="otp">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-shield-check"></i> OTP Verification</h5>
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" name="otp_enabled" 
                                       id="otpEnabled" <?= Settings::get('otp_enabled', '1') === '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="otpEnabled">
                                    <strong>Enable OTP Verification</strong>
                                    <small class="d-block text-muted">Require users to verify email with OTP during registration and login</small>
                                </label>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">OTP Expiry Time (minutes)</label>
                                    <input type="number" name="otp_expiry_minutes" class="form-control" min="1" max="60"
                                           value="<?= htmlspecialchars(Settings::get('otp_expiry_minutes', '10')) ?>">
                                    <small class="text-muted">How long the OTP code remains valid</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Settings Tab -->
                <div class="tab-pane fade" id="email">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-envelope"></i> SMTP Configuration</h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Host</label>
                                    <input type="text" name="smtp_host" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('smtp_host', 'smtp.gmail.com')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Port</label>
                                    <input type="number" name="smtp_port" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('smtp_port', '587')) ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Username</label>
                                    <input type="text" name="smtp_username" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('smtp_username', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMTP Password</label>
                                    <input type="password" name="smtp_password" class="form-control" 
                                           placeholder="Leave empty to keep current">
                                    <small class="text-muted">For Gmail, use <a href="https://myaccount.google.com/apppasswords" target="_blank">App Password</a></small>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">From Email</label>
                                    <input type="email" name="smtp_from_email" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('smtp_from_email', '')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">From Name</label>
                                    <input type="text" name="smtp_from_name" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('smtp_from_name', 'News Website')) ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Site Contact Email</label>
                                    <input type="email" name="site_email" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('site_email', '')) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Social Media Tab -->
                <div class="tab-pane fade" id="social-media">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-share"></i> Social Media Links</h5>
                            <p class="text-muted">These links will be used in welcome emails and footer</p>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-facebook"></i> Facebook</label>
                                    <input type="url" name="social_facebook" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('social_facebook', '')) ?>"
                                           placeholder="https://facebook.com/yourpage">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-twitter"></i> Twitter</label>
                                    <input type="url" name="social_twitter" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('social_twitter', '')) ?>"
                                           placeholder="https://twitter.com/yourhandle">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-instagram"></i> Instagram</label>
                                    <input type="url" name="social_instagram" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('social_instagram', '')) ?>"
                                           placeholder="https://instagram.com/yourhandle">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label"><i class="bi bi-youtube"></i> YouTube</label>
                                    <input type="url" name="social_youtube" class="form-control" 
                                           value="<?= htmlspecialchars(Settings::get('social_youtube', '')) ?>"
                                           placeholder="https://youtube.com/yourchannel">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 mb-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-check-lg"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
