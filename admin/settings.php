<?php
/**
 * Admin Settings - Complete Settings Management
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';
require_once '../includes/Settings.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        // General Settings
        'site_name' => trim($_POST['site_name'] ?? ''),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'site_keywords' => trim($_POST['site_keywords'] ?? ''),
        'contact_email' => trim($_POST['contact_email'] ?? ''),
        'articles_per_page' => (int)($_POST['articles_per_page'] ?? 10),
        'enable_comments' => isset($_POST['enable_comments']) ? '1' : '0',
        'require_approval' => isset($_POST['require_approval']) ? '1' : '0',
        
        // Social Media Links
        'facebook_url' => trim($_POST['facebook_url'] ?? ''),
        'twitter_url' => trim($_POST['twitter_url'] ?? ''),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'youtube_url' => trim($_POST['youtube_url'] ?? ''),
        
        // Advanced Settings
        'google_analytics' => trim($_POST['google_analytics'] ?? ''),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        
        // Notification Settings
        'fcm_server_key' => trim($_POST['fcm_server_key'] ?? ''),
        'enable_push_notifications' => isset($_POST['enable_push_notifications']) ? '1' : '0',
        
        // Social Login Settings
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
        
        // Branding Settings
        'app_name' => trim($_POST['app_name'] ?? ''),
        'app_tagline' => trim($_POST['app_tagline'] ?? ''),
        'logo_text' => trim($_POST['logo_text'] ?? ''),
        'footer_text' => trim($_POST['footer_text'] ?? ''),
        
        // Mobile App Settings
        'app_version' => trim($_POST['app_version'] ?? '1.0.0'),
        'force_update' => isset($_POST['force_update']) ? '1' : '0',
        'min_app_version' => trim($_POST['min_app_version'] ?? '1.0.0'),
        'show_splash' => isset($_POST['show_splash']) ? '1' : '0',
        'splash_duration' => (int)($_POST['splash_duration'] ?? 3),
        'splash_logo_url' => trim($_POST['splash_logo_url'] ?? ''),
        'splash_background_color' => trim($_POST['splash_background_color'] ?? '#FFFFFF'),
        'splash_text' => trim($_POST['splash_text'] ?? ''),
        'enable_dark_mode' => isset($_POST['enable_dark_mode']) ? '1' : '0',
    ];
    
    // Update SMTP password only if provided
    if (!empty($_POST['smtp_password'])) {
        $settings['smtp_password'] = $_POST['smtp_password'];
    }
    
    // Validation
    if (empty($settings['site_name'])) {
        $errors[] = 'Site name is required';
    }
    
    if (!empty($settings['contact_email']) && !filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid contact email';
    }
    
    if (empty($errors)) {
        // Update or insert settings
        foreach ($settings as $key => $value) {
            $existing = $db->fetchOne("SELECT * FROM settings WHERE setting_key = ?", [$key]);
            
            if ($existing) {
                $db->update('settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
            } else {
                $db->insert('settings', ['setting_key' => $key, 'setting_value' => $value]);
            }
        }
        
        $success = 'Settings saved successfully';
    }
}

// Get current settings
$settings_data = $db->fetchAll("SELECT * FROM settings");
$settings = [];
foreach ($settings_data as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Load settings using Settings class
Settings::load();

// Default values
$defaults = [
    'site_name' => 'News Website',
    'site_description' => 'Latest news and updates',
    'site_keywords' => 'news, updates, articles',
    'contact_email' => '',
    'articles_per_page' => 10,
    'enable_comments' => '1',
    'require_approval' => '1',
    'facebook_url' => '',
    'twitter_url' => '',
    'instagram_url' => '',
    'youtube_url' => '',
    'google_analytics' => '',
    'maintenance_mode' => '0'
];

$settings = array_merge($defaults, $settings);

$page_title = 'Site Settings';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><i class="bi bi-gear-fill"></i> Site Settings</h1>
    
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
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <form method="post">
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#general"><i class="bi bi-gear"></i> General</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#branding"><i class="bi bi-palette"></i> Branding</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#mobile-app"><i class="bi bi-phone"></i> Mobile App</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#social-media"><i class="bi bi-share"></i> Social Media</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#social-login"><i class="bi bi-box-arrow-in-right"></i> Social Login</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#email"><i class="bi bi-envelope"></i> Email</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#security"><i class="bi bi-shield-check"></i> Security</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#notifications"><i class="bi bi-bell"></i> Notifications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#advanced"><i class="bi bi-code-slash"></i> Advanced</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- General Settings Tab -->
            <div class="tab-pane fade show active" id="general">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-cog me-1"></i> General Settings
                            </div>
                            <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Site Name <span class="text-danger">*</span></label>
                            <input type="text" name="site_name" class="form-control" 
                                   value="<?= htmlspecialchars($settings['site_name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Site Description</label>
                            <textarea name="site_description" class="form-control" rows="3"><?= htmlspecialchars($settings['site_description']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">SEO Keywords (comma-separated)</label>
                            <input type="text" name="site_keywords" class="form-control" 
                                   value="<?= htmlspecialchars($settings['site_keywords']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control" 
                                   value="<?= htmlspecialchars($settings['contact_email']) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Articles Per Page</label>
                            <input type="number" name="articles_per_page" class="form-control" 
                                   value="<?= htmlspecialchars($settings['articles_per_page']) ?>" min="5" max="50">
                        </div>
                            </div>
                        </div>
                
                        <!-- Comment Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-comments me-1"></i> Comment Settings
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="enable_comments" class="form-check-input" id="enableComments" 
                                           <?= $settings['enable_comments'] === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enableComments">
                                        Enable Comments
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="checkbox" name="require_approval" class="form-check-input" id="requireApproval" 
                                           <?= $settings['require_approval'] === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="requireApproval">
                                        Require Comment Approval
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-info-circle me-1"></i> Information
                            </div>
                            <div class="card-body">
                                <h6>Configuration Tips</h6>
                                <ul class="small">
                                    <li>Site name appears in browser title and headers</li>
                                    <li>SEO keywords help search engines index your site</li>
                                    <li>Changes take effect immediately</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Branding Tab -->
            <div class="tab-pane fade" id="branding">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-palette me-1"></i> Brand Identity
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">App Name</label>
                                    <input type="text" name="app_name" class="form-control" 
                                           value="<?= htmlspecialchars($settings['app_name'] ?? '') ?>" 
                                           placeholder="Brackodd Media">
                                    <small class="text-muted">Displayed in mobile app header and navigation</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">App Tagline</label>
                                    <input type="text" name="app_tagline" class="form-control" 
                                           value="<?= htmlspecialchars($settings['app_tagline'] ?? '') ?>" 
                                           placeholder="Your trusted news source">
                                    <small class="text-muted">Short description shown in app</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Logo Text (Website)</label>
                                    <input type="text" name="logo_text" class="form-control" 
                                           value="<?= htmlspecialchars($settings['logo_text'] ?? '') ?>" 
                                           placeholder="Site Logo Text">
                                    <small class="text-muted">Text displayed with website logo</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Footer Text</label>
                                    <textarea name="footer_text" class="form-control" rows="2" 
                                              placeholder="© 2025 Your Company. All rights reserved."><?= htmlspecialchars($settings['footer_text'] ?? '') ?></textarea>
                                    <small class="text-muted">Copyright text in website footer</small>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3"><i class="fas fa-images me-1"></i> Logo Management</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Upload logo files to <code>news_app/assets/images/</code> directory and reference them in the app configuration.
                                    Recommended size: 110x60px (transparent PNG)
                                </div>
                                
                                <h6 class="mb-3 mt-4"><i class="fas fa-font me-1"></i> Typography</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Font files are managed in <code>news_app/assets/fonts/</code> directory.
                                    Current font family: <strong>Westack</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-info-circle me-1"></i> Branding Guide
                            </div>
                            <div class="card-body">
                                <h6>Brand Consistency</h6>
                                <ul class="small">
                                    <li>App name appears in mobile navigation header</li>
                                    <li>Logo text is used for website branding</li>
                                    <li>Keep names consistent across platforms</li>
                                    <li>Westack font used for app logo and categories</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>Current Settings</h6>
                                <p class="small mb-1"><strong>App Name:</strong></p>
                                <p class="small text-muted"><?= htmlspecialchars($settings['app_name'] ?? 'Not set') ?></p>
                                
                                <p class="small mb-1 mt-2"><strong>Font Family:</strong></p>
                                <p class="small text-muted">Westack</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile App Tab -->
            <div class="tab-pane fade" id="mobile-app">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-mobile-alt me-1"></i> App Configuration
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">App Version</label>
                                    <input type="text" name="app_version" class="form-control" 
                                           value="<?= htmlspecialchars($settings['app_version'] ?? '1.0.0') ?>" 
                                           placeholder="1.0.0">
                                    <small class="text-muted">Current version of the mobile app</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Minimum App Version</label>
                                    <input type="text" name="min_app_version" class="form-control" 
                                           value="<?= htmlspecialchars($settings['min_app_version'] ?? '1.0.0') ?>" 
                                           placeholder="1.0.0">
                                    <small class="text-muted">Minimum version required to use the app</small>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="force_update" class="form-check-input" id="forceUpdate" 
                                           <?= ($settings['force_update'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="forceUpdate">
                                        Force Update
                                    </label>
                                    <small class="d-block text-muted">Users below minimum version must update to continue</small>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3"><i class="fas fa-cog me-1"></i> App Features</h6>
                                
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="show_splash" class="form-check-input" id="showSplash" 
                                           <?= ($settings['show_splash'] ?? '1') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="showSplash">
                                        Show Splash Screen
                                    </label>
                                </div>
                
                <hr class="my-4">
                
                <h6 class="mb-3"><i class="fas fa-image me-1"></i> Splash Screen Configuration</h6>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Splash Duration (seconds)</label>
                        <input type="number" name="splash_duration" class="form-control" 
                               value="<?= htmlspecialchars($settings['splash_duration'] ?? '3') ?>"
                               min="1" max="10" step="0.5">
                        <small class="text-muted">How long to show splash screen (1-10 seconds)</small>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">Background Color</label>
                        <div class="input-group">
                            <input type="color" name="splash_background_color" class="form-control form-control-color" 
                                   value="<?= htmlspecialchars($settings['splash_background_color'] ?? '#FFFFFF') ?>"
                                   title="Choose splash background color">
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($settings['splash_background_color'] ?? '#FFFFFF') ?>"
                                   readonly>
                        </div>
                        <small class="text-muted">Splash screen background color</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Splash Logo URL</label>
                    <input type="text" name="splash_logo_url" class="form-control" 
                           value="<?= htmlspecialchars($settings['splash_logo_url'] ?? '') ?>"
                           placeholder="assets/images/splash_logo.png">
                    <small class="text-muted">Path to splash screen logo (relative to assets folder)</small>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Splash Text/Tagline</label>
                    <input type="text" name="splash_text" class="form-control" 
                           value="<?= htmlspecialchars($settings['splash_text'] ?? '') ?>"
                           placeholder="Your trusted news source">
                    <small class="text-muted">Text shown below logo on splash screen</small>
                </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <h6 class="mb-3"><i class="fas fa-bell me-1"></i> Push Notifications</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Push notification settings are configured in the <strong>Notifications</strong> tab.
                                    <a href="#" onclick="document.querySelector('[href=\'#notifications\']').click(); return false;">Go to Notifications →</a>
                                </div>
                                
                                <h6 class="mb-3 mt-4"><i class="fas fa-server me-1"></i> API Configuration</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>
                                    API base URL configured in <code>lib/constants/api_constants.dart</code>:<br>
                                    <code class="d-block mt-2">http://192.168.1.3</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-info-circle me-1"></i> App Information
                            </div>
                            <div class="card-body">
                                <h6>Version Control</h6>
                                <ul class="small">
                                    <li>Update app version after each release</li>
                                    <li>Use semantic versioning (MAJOR.MINOR.PATCH)</li>
                                    <li>Force update for critical security fixes</li>
                                </ul>
                                
                                <hr>
                                
                                <h6>Current Status</h6>
                                <p class="small mb-1"><strong>Version:</strong></p>
                                <p class="small text-muted"><?= htmlspecialchars($settings['app_version'] ?? '1.0.0') ?></p>
                                
                                <p class="small mb-1 mt-2"><strong>Force Update:</strong></p>
                                <p class="small text-muted"><?= ($settings['force_update'] ?? '0') === '1' ? 'Enabled' : 'Disabled' ?></p>
                                
                                <p class="small mb-1 mt-2"><strong>Dark Mode:</strong></p>
                                <p class="small text-muted"><?= ($settings['enable_dark_mode'] ?? '1') === '1' ? 'Enabled' : 'Disabled' ?></p>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-header">
                                <i class="fas fa-mobile-alt me-1"></i> Splash Screen Preview
                            </div>
                            <div class="card-body text-center" style="background-color: <?= htmlspecialchars($settings['splash_background_color'] ?? '#FFFFFF') ?>; min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center; border-radius: 10px;">
                                <div class="mb-3">
                                    <i class="fas fa-image fa-3x" style="opacity: 0.5;"></i>
                                </div>
                                <?php if (!empty($settings['splash_text'])): ?>
                                <p class="mb-0"><strong><?= htmlspecialchars($settings['splash_text']) ?></strong></p>
                                <?php endif; ?>
                                <p class="small text-muted mt-3 mb-0">Duration: <?= htmlspecialchars($settings['splash_duration'] ?? '3') ?>s</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Media Tab -->
            <div class="tab-pane fade" id="social-media">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-share-alt me-1"></i> Social Media Links
                    </div>
                    <div class="card-body">
                        <p class="text-muted">These links will appear in the website footer and mobile app</p>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fab fa-facebook"></i> Facebook URL
                                </label>
                                <input type="url" name="facebook_url" class="form-control" 
                                       value="<?= htmlspecialchars($settings['facebook_url']) ?>" 
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fab fa-twitter"></i> Twitter URL
                                </label>
                                <input type="url" name="twitter_url" class="form-control" 
                                       value="<?= htmlspecialchars($settings['twitter_url']) ?>" 
                                       placeholder="https://twitter.com/yourhandle">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fab fa-instagram"></i> Instagram URL
                                </label>
                                <input type="url" name="instagram_url" class="form-control" 
                                       value="<?= htmlspecialchars($settings['instagram_url']) ?>" 
                                       placeholder="https://instagram.com/yourhandle">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fab fa-youtube"></i> YouTube URL
                                </label>
                                <input type="url" name="youtube_url" class="form-control" 
                                       value="<?= htmlspecialchars($settings['youtube_url']) ?>" 
                                       placeholder="https://youtube.com/yourchannel">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Login Tab -->
            <div class="tab-pane fade" id="social-login">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-google"></i> Google Login</h5>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="google_login_enabled" 
                                   id="googleLoginEnabled" <?= Settings::get('google_login_enabled') === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="googleLoginEnabled">
                                <strong>Enable Google Login</strong>
                            </label>
                        </div>
                        <div class="row mb-4">
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

                        <div class="alert alert-info mt-4">
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
            
            <!-- Email Settings Tab -->
            <div class="tab-pane fade" id="email">
                <div class="card mb-4">
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
            
            <!-- Security Tab -->
            <div class="tab-pane fade" id="security">
                <div class="card mb-4">
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
            
            <!-- Notifications Tab -->
            <div class="tab-pane fade" id="notifications">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-bell-fill"></i> Push Notification Settings (FCM)
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" name="enable_push_notifications" 
                                           id="enablePushNotifications" <?= Settings::get('enable_push_notifications', '0') === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="enablePushNotifications">
                                        <strong>Enable Push Notifications</strong>
                                        <small class="d-block text-muted">Allow sending push notifications to mobile app users</small>
                                    </label>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="bi bi-key-fill"></i> Firebase Cloud Messaging (FCM) Server Key
                                    </label>
                                    <textarea name="fcm_server_key" class="form-control font-monospace" rows="4" 
                                              placeholder="Enter your FCM Server Key here..."><?= htmlspecialchars(Settings::get('fcm_server_key', '')) ?></textarea>
                                    <small class="text-muted">
                                        This key is used to send push notifications to mobile app users. 
                                        Get it from <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a> 
                                        → Project Settings → Cloud Messaging → Server Key
                                    </small>
                                </div>

                                <div class="alert alert-info">
                                    <strong><i class="bi bi-info-circle"></i> How to get FCM Server Key:</strong>
                                    <ol class="mb-0 mt-2">
                                        <li>Go to <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a></li>
                                        <li>Select your project</li>
                                        <li>Click on Project Settings (gear icon)</li>
                                        <li>Go to "Cloud Messaging" tab</li>
                                        <li>Copy the "Server key" under "Project credentials"</li>
                                        <li>Paste it in the field above</li>
                                    </ol>
                                </div>

                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="bi bi-check-circle text-success"></i> Current Status
                                        </h6>
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div>
                                                <strong>Push Notifications:</strong>
                                                <span class="badge bg-<?= Settings::get('enable_push_notifications') === '1' ? 'success' : 'secondary' ?>">
                                                    <?= Settings::get('enable_push_notifications') === '1' ? 'Enabled' : 'Disabled' ?>
                                                </span>
                                            </div>
                                            <div>
                                                <strong>FCM Server Key:</strong>
                                                <span class="badge bg-<?= !empty(Settings::get('fcm_server_key')) ? 'success' : 'warning' ?>">
                                                    <?= !empty(Settings::get('fcm_server_key')) ? 'Configured ✓' : 'Not Set' ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (Settings::get('enable_push_notifications') === '1' && !empty(Settings::get('fcm_server_key'))): ?>
                                        <button type="button" class="btn btn-sm btn-success w-100" id="sendTestNotification">
                                            <i class="bi bi-send-fill"></i> Send Test Notification to Mobile
                                        </button>
                                        <div id="testNotificationResult" class="mt-2"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-lightbulb"></i> Tips
                            </div>
                            <div class="card-body">
                                <h6>What are Push Notifications?</h6>
                                <p class="small">Push notifications allow you to send instant updates to your mobile app users even when they're not using the app.</p>
                                
                                <h6 class="mt-3">Use Cases:</h6>
                                <ul class="small">
                                    <li>Breaking news alerts</li>
                                    <li>New article notifications</li>
                                    <li>Live news updates</li>
                                    <li>Personalized content alerts</li>
                                </ul>

                                <h6 class="mt-3">Requirements:</h6>
                                <ul class="small mb-0">
                                    <li>Firebase project setup</li>
                                    <li>FCM enabled in Firebase</li>
                                    <li>Mobile app with FCM SDK</li>
                                </ul>
                            </div>
                        </div>

                        <div class="card mt-3 border-warning">
                            <div class="card-header bg-warning">
                                <i class="bi bi-shield-exclamation"></i> Security Note
                            </div>
                            <div class="card-body">
                                <p class="small mb-0">
                                    <strong>Keep your FCM Server Key secure!</strong> 
                                    Never share it publicly or commit it to version control. 
                                    This key is stored securely in the database.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Advanced Settings Tab -->
            <div class="tab-pane fade" id="advanced">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-code me-1"></i> Advanced Settings
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Google Analytics Tracking ID</label>
                            <input type="text" name="google_analytics" class="form-control" 
                                   value="<?= htmlspecialchars($settings['google_analytics']) ?>" 
                                   placeholder="G-XXXXXXXXXX">
                            <small class="text-muted">Leave empty to disable tracking</small>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" name="maintenance_mode" class="form-check-input" id="maintenanceMode" 
                                   <?= $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="maintenanceMode">
                                <strong>Enable Maintenance Mode</strong><br>
                                <small class="text-muted">Site will display maintenance page to visitors</small>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="text-center mt-4 mb-4">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-check-lg"></i> Save All Settings
            </button>
        </div>
    </form>
</div>

<script>
// Test Notification Sender
document.getElementById('sendTestNotification')?.addEventListener('click', async function() {
    const btn = this;
    const resultDiv = document.getElementById('testNotificationResult');
    
    // Disable button
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i> Sending...';
    
    try {
        const response = await fetch('../api/admin/send-test-notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: '🔔 Test Notification',
                body: 'Hello! This is a test push notification from your admin panel.',
                topic: 'all'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show">
                    <strong><i class="bi bi-check-circle"></i> Success!</strong><br>
                    ${data.message}<br>
                    <small class="text-muted">Sent at: ${data.details.sent_at}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong><i class="bi bi-x-circle"></i> Failed!</strong><br>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show">
                <strong><i class="bi bi-x-circle"></i> Error!</strong><br>
                Network error: ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }
    
    // Re-enable button
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send-fill"></i> Send Test Notification to Mobile';
});
</script>

<?php include 'includes/footer.php'; ?>