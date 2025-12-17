<?php
/**
 * SPLASH SCREEN SETTINGS - ADMIN PANEL
 * Manage Default and Dynamic Splash Screens
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$success = '';
$errors = [];

// Get current settings
$settings = $db->fetchOne("SELECT * FROM splash_screen_settings WHERE id = 1");

if (!$settings) {
    // Initialize default settings
    $db->insert('splash_screen_settings', [
        'id' => 1,
        'is_dynamic_enabled' => 0,
        'default_logo' => 'assets/images/logo.png',
        'default_background_color' => '#FFFFFF',
        'default_text_color' => '#000000',
        'default_tagline' => 'Your Trusted News Source',
        'default_animation_type' => 'fade',
        'target_platforms' => 'all'
    ]);
    $settings = $db->fetchOne("SELECT * FROM splash_screen_settings WHERE id = 1");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateData = [];
    
    // Default Splash Settings
    if (isset($_POST['default_tagline'])) {
        $updateData['default_tagline'] = trim($_POST['default_tagline']);
    }
    if (isset($_POST['default_background_color'])) {
        $updateData['default_background_color'] = trim($_POST['default_background_color']);
    }
    if (isset($_POST['default_text_color'])) {
        $updateData['default_text_color'] = trim($_POST['default_text_color']);
    }
    if (isset($_POST['default_animation_type'])) {
        $updateData['default_animation_type'] = $_POST['default_animation_type'];
    }
    
    // Handle default logo upload
    if (isset($_FILES['default_logo']) && $_FILES['default_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/splash/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['default_logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        
        if (in_array($file_extension, $allowed)) {
            $new_filename = 'default_logo_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['default_logo']['tmp_name'], $upload_dir . $new_filename)) {
                $updateData['default_logo'] = 'uploads/splash/' . $new_filename;
            }
        }
    }
    
    // Dynamic Splash Settings
    if (isset($_POST['is_dynamic_enabled'])) {
        $updateData['is_dynamic_enabled'] = isset($_POST['is_dynamic_enabled']) ? 1 : 0;
    }
    
    if (isset($_POST['dynamic_title'])) {
        $updateData['dynamic_title'] = trim($_POST['dynamic_title']);
    }
    if (isset($_POST['dynamic_subtitle'])) {
        $updateData['dynamic_subtitle'] = trim($_POST['dynamic_subtitle']);
    }
    if (isset($_POST['dynamic_background_color'])) {
        $updateData['dynamic_background_color'] = trim($_POST['dynamic_background_color']);
    }
    if (isset($_POST['dynamic_text_color'])) {
        $updateData['dynamic_text_color'] = trim($_POST['dynamic_text_color']);
    }
    if (isset($_POST['dynamic_button_text'])) {
        $updateData['dynamic_button_text'] = trim($_POST['dynamic_button_text']);
    }
    if (isset($_POST['dynamic_button_action'])) {
        $updateData['dynamic_button_action'] = trim($_POST['dynamic_button_action']);
    }
    if (isset($_POST['dynamic_display_duration'])) {
        $updateData['dynamic_display_duration'] = (int)$_POST['dynamic_display_duration'];
    }
    
    // Handle dynamic image upload
    if (isset($_FILES['dynamic_image']) && $_FILES['dynamic_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/splash/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['dynamic_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_extension, $allowed)) {
            $new_filename = 'dynamic_splash_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['dynamic_image']['tmp_name'], $upload_dir . $new_filename)) {
                $updateData['dynamic_image'] = 'uploads/splash/' . $new_filename;
            }
        }
    }
    
    // Schedule Settings
    if (isset($_POST['is_scheduled'])) {
        $updateData['is_scheduled'] = isset($_POST['is_scheduled']) ? 1 : 0;
    }
    if (isset($_POST['schedule_start_date'])) {
        $updateData['schedule_start_date'] = $_POST['schedule_start_date'] ?: null;
    }
    if (isset($_POST['schedule_end_date'])) {
        $updateData['schedule_end_date'] = $_POST['schedule_end_date'] ?: null;
    }
    
    // Targeting Settings
    if (isset($_POST['target_new_users_only'])) {
        $updateData['target_new_users_only'] = isset($_POST['target_new_users_only']) ? 1 : 0;
    }
    if (isset($_POST['target_platforms'])) {
        $updateData['target_platforms'] = $_POST['target_platforms'];
    }
    
    if (!empty($updateData)) {
        $db->query(
            "UPDATE splash_screen_settings SET " . 
            implode(', ', array_map(fn($k) => "$k = ?", array_keys($updateData))) . 
            " WHERE id = 1",
            array_values($updateData)
        );
        $success = 'Splash screen settings updated successfully!';
        
        // Refresh settings
        $settings = $db->fetchOne("SELECT * FROM splash_screen_settings WHERE id = 1");
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Splash Screen Settings</h4>
                <p class="text-muted">Manage default and dynamic splash screens for your app</p>
            </div>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php foreach ($errors as $error): ?>
                <div><?= $error ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <!-- Default Splash Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-phone me-2"></i>Default Splash Screen</h5>
                        <small>This shows when dynamic splash is disabled or not scheduled</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <?php if ($settings['default_logo']): ?>
                                <div class="mb-2">
                                    <img src="<?= BASE_URL ?>/<?= $settings['default_logo'] ?>" alt="Default Logo" 
                                         style="max-height: 100px; background: #f5f5f5; padding: 10px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="default_logo" class="form-control" accept="image/*">
                            <small class="text-muted">Recommended: PNG with transparent background, 512x512px</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tagline</label>
                            <input type="text" name="default_tagline" class="form-control" 
                                   value="<?= htmlspecialchars($settings['default_tagline'] ?? '') ?>" 
                                   placeholder="Your Trusted News Source">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Background Color</label>
                                <input type="color" name="default_background_color" class="form-control form-control-color" 
                                       value="<?= $settings['default_background_color'] ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Text Color</label>
                                <input type="color" name="default_text_color" class="form-control form-control-color" 
                                       value="<?= $settings['default_text_color'] ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Animation Type</label>
                            <select name="default_animation_type" class="form-select">
                                <option value="fade" <?= $settings['default_animation_type'] === 'fade' ? 'selected' : '' ?>>Fade In</option>
                                <option value="slide" <?= $settings['default_animation_type'] === 'slide' ? 'selected' : '' ?>>Slide Up</option>
                                <option value="zoom" <?= $settings['default_animation_type'] === 'zoom' ? 'selected' : '' ?>>Zoom In</option>
                                <option value="bounce" <?= $settings['default_animation_type'] === 'bounce' ? 'selected' : '' ?>>Bounce</option>
                                <option value="none" <?= $settings['default_animation_type'] === 'none' ? 'selected' : '' ?>>No Animation</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Default Settings
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dynamic Splash Settings -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-stars me-2"></i>Dynamic Splash Screen</h5>
                        <small>Show custom promotional splash screens</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_dynamic_enabled" id="isDynamicEnabled" 
                                       <?= $settings['is_dynamic_enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="isDynamicEnabled">
                                    Enable Dynamic Splash Screen
                                </label>
                            </div>
                        </div>

                        <div id="dynamicSplashOptions" style="<?= !$settings['is_dynamic_enabled'] ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
                            <div class="mb-3">
                                <label class="form-label">Splash Image</label>
                                <?php if ($settings['dynamic_image']): ?>
                                    <div class="mb-2">
                                        <img src="<?= BASE_URL ?>/<?= $settings['dynamic_image'] ?>" alt="Dynamic Splash" 
                                             style="max-height: 200px; max-width: 100%;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="dynamic_image" class="form-control" accept="image/*">
                                <small class="text-muted">Full-screen promotional image (1080x1920px for portrait)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="dynamic_title" class="form-control" 
                                       value="<?= htmlspecialchars($settings['dynamic_title'] ?? '') ?>" 
                                       placeholder="Special Announcement">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Subtitle</label>
                                <textarea name="dynamic_subtitle" class="form-control" rows="2" 
                                          placeholder="Limited time offer or special message"><?= htmlspecialchars($settings['dynamic_subtitle'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Background Color</label>
                                    <input type="color" name="dynamic_background_color" class="form-control form-control-color" 
                                           value="<?= $settings['dynamic_background_color'] ?? '#1a1a1a' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Text Color</label>
                                    <input type="color" name="dynamic_text_color" class="form-control form-control-color" 
                                           value="<?= $settings['dynamic_text_color'] ?? '#ffffff' ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Button Text</label>
                                    <input type="text" name="dynamic_button_text" class="form-control" 
                                           value="<?= htmlspecialchars($settings['dynamic_button_text'] ?? '') ?>" 
                                           placeholder="Explore Now">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Display Duration (seconds)</label>
                                    <input type="number" name="dynamic_display_duration" class="form-control" 
                                           value="<?= $settings['dynamic_display_duration'] ?? 3 ?>" min="1" max="10">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Button Action (URL or Deep Link)</label>
                                <input type="text" name="dynamic_button_action" class="form-control" 
                                       value="<?= htmlspecialchars($settings['dynamic_button_action'] ?? '') ?>" 
                                       placeholder="/special-offer or app://category/politics">
                                <small class="text-muted">Leave empty to skip to home screen</small>
                            </div>

                            <hr>

                            <h6 class="mb-3">Schedule & Targeting</h6>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_scheduled" id="isScheduled" 
                                           <?= $settings['is_scheduled'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isScheduled">
                                        Schedule Display Period
                                    </label>
                                </div>
                            </div>

                            <div class="row" id="scheduleOptions" style="<?= !$settings['is_scheduled'] ? 'opacity: 0.5; pointer-events: none;' : '' ?>">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date & Time</label>
                                    <input type="datetime-local" name="schedule_start_date" class="form-control" 
                                           value="<?= $settings['schedule_start_date'] ? date('Y-m-d\TH:i', strtotime($settings['schedule_start_date'])) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Date & Time</label>
                                    <input type="datetime-local" name="schedule_end_date" class="form-control" 
                                           value="<?= $settings['schedule_end_date'] ? date('Y-m-d\TH:i', strtotime($settings['schedule_end_date'])) : '' ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="target_new_users_only" id="targetNewUsers" 
                                           <?= $settings['target_new_users_only'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="targetNewUsers">
                                        Show only to new users (first time app open)
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Target Platforms</label>
                                <select name="target_platforms" class="form-select">
                                    <option value="all" <?= $settings['target_platforms'] === 'all' ? 'selected' : '' ?>>All Platforms</option>
                                    <option value="android" <?= $settings['target_platforms'] === 'android' ? 'selected' : '' ?>>Android Only</option>
                                    <option value="ios" <?= $settings['target_platforms'] === 'ios' ? 'selected' : '' ?>>iOS Only</option>
                                    <option value="web" <?= $settings['target_platforms'] === 'web' ? 'selected' : '' ?>>Web Only</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <h6>Analytics</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 text-center">
                                            <h4 class="mb-0"><?= number_format($settings['impression_count']) ?></h4>
                                            <small class="text-muted">Impressions</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 text-center">
                                            <h4 class="mb-0"><?= number_format($settings['click_count']) ?></h4>
                                            <small class="text-muted">Button Clicks</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-save me-1"></i> Save Dynamic Settings
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-eye me-2"></i>Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-center mb-3">Default Splash Preview</h6>
                                <div class="border rounded p-4 text-center" style="background: <?= $settings['default_background_color'] ?>; min-height: 400px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <?php if ($settings['default_logo']): ?>
                                        <img src="<?= BASE_URL ?>/<?= $settings['default_logo'] ?>" alt="Logo" style="max-height: 150px; margin-bottom: 20px;">
                                    <?php endif; ?>
                                    <p class="mb-0" style="color: <?= $settings['default_text_color'] ?>; font-size: 18px; font-weight: 500;">
                                        <?= htmlspecialchars($settings['default_tagline'] ?? '') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-center mb-3">Dynamic Splash Preview</h6>
                                <div class="border rounded p-4 text-center" style="background: <?= $settings['dynamic_background_color'] ?? '#1a1a1a' ?>; min-height: 400px; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative;">
                                    <?php if ($settings['dynamic_image']): ?>
                                        <img src="<?= BASE_URL ?>/<?= $settings['dynamic_image'] ?>" alt="Dynamic" style="max-width: 100%; max-height: 250px; margin-bottom: 15px;">
                                    <?php endif; ?>
                                    <?php if ($settings['dynamic_title']): ?>
                                        <h4 style="color: <?= $settings['dynamic_text_color'] ?? '#ffffff' ?>; margin-bottom: 10px;">
                                            <?= htmlspecialchars($settings['dynamic_title']) ?>
                                        </h4>
                                    <?php endif; ?>
                                    <?php if ($settings['dynamic_subtitle']): ?>
                                        <p style="color: <?= $settings['dynamic_text_color'] ?? '#ffffff' ?>; opacity: 0.9;">
                                            <?= htmlspecialchars($settings['dynamic_subtitle']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($settings['dynamic_button_text']): ?>
                                        <button class="btn btn-primary mt-3"><?= htmlspecialchars($settings['dynamic_button_text']) ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('isDynamicEnabled').addEventListener('change', function() {
        const options = document.getElementById('dynamicSplashOptions');
        if (this.checked) {
            options.style.opacity = '1';
            options.style.pointerEvents = 'auto';
        } else {
            options.style.opacity = '0.5';
            options.style.pointerEvents = 'none';
        }
    });

    document.getElementById('isScheduled').addEventListener('change', function() {
        const options = document.getElementById('scheduleOptions');
        if (this.checked) {
            options.style.opacity = '1';
            options.style.pointerEvents = 'auto';
        } else {
            options.style.opacity = '0.5';
            options.style.pointerEvents = 'none';
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
