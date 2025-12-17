<?php
/**
 * Ads Management System
 * Handles Google AdSense and Custom Ads
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$page = $_GET['page'] ?? 'settings';
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';
$tables_exist = false;

// Check if ads tables exist
try {
    $table_check = $db->fetchOne("SHOW TABLES LIKE 'ads_settings'");
    $tables_exist = !empty($table_check);
} catch (Exception $e) {
    $tables_exist = false;
}

// If tables don't exist, show setup instructions
if (!$tables_exist) {
    include 'includes/header.php';
    ?>
    <div class="container-fluid mt-4">
        <div class="alert alert-warning">
            <h4><i class="bi bi-exclamation-triangle"></i> Ads Management Tables Not Found</h4>
            <p>The required database tables for the ads management system have not been created yet.</p>
            
            <h5 class="mt-3">Quick Setup:</h5>
            <ol>
                <li>Open <strong>phpMyAdmin</strong> or your MySQL client</li>
                <li>Select your database: <strong>news_website</strong></li>
                <li>Go to the <strong>SQL</strong> tab</li>
                <li>Copy and paste the SQL from: <code>database/ads_management.sql</code></li>
                <li>Click <strong>Go</strong> to execute</li>
                <li>Refresh this page</li>
            </ol>
            
            <h5 class="mt-3">Or use Command Line:</h5>
            <pre class="bg-dark text-white p-3 rounded">mysql -u root -p news_website < database/ads_management.sql</pre>
            
            <a href="../database/ads_management.sql" class="btn btn-primary mt-3" download>
                <i class="bi bi-download"></i> Download SQL File
            </a>
            
            <a href="ads.php" class="btn btn-secondary mt-3">
                <i class="bi bi-arrow-clockwise"></i> Refresh Page
            </a>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// Get current AdSense settings
$adsense_settings = $db->fetchOne("
    SELECT * FROM ads_settings 
    WHERE type = 'google_adsense'
    LIMIT 1
") ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'settings') {
        // Save AdSense settings
        $type = 'google_adsense';
        $client_id = trim($_POST['client_id'] ?? '');
        $ad_slot_banner = trim($_POST['ad_slot_banner'] ?? '');
        $ad_slot_sidebar = trim($_POST['ad_slot_sidebar'] ?? '');
        $ad_slot_article = trim($_POST['ad_slot_article'] ?? '');
        $enabled = isset($_POST['enabled']) ? 1 : 0;

        if ($adsense_settings) {
            $db->update('ads_settings', [
                'client_id' => $client_id,
                'ad_slot_banner' => $ad_slot_banner,
                'ad_slot_sidebar' => $ad_slot_sidebar,
                'ad_slot_article' => $ad_slot_article,
                'enabled' => $enabled,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'type = ?', ['google_adsense']);
        } else {
            $db->insert('ads_settings', [
                'type' => $type,
                'client_id' => $client_id,
                'ad_slot_banner' => $ad_slot_banner,
                'ad_slot_sidebar' => $ad_slot_sidebar,
                'ad_slot_article' => $ad_slot_article,
                'enabled' => $enabled,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        $success_message = "AdSense settings saved successfully!";
        $adsense_settings = $db->fetchOne("SELECT * FROM ads_settings WHERE type = 'google_adsense'");
    }

    elseif ($page === 'custom' && $action === 'add') {
        // Add custom ad
        $title = trim($_POST['title'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $placement = trim($_POST['placement'] ?? '');
        $position = trim($_POST['position'] ?? 'top');
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($title) && !empty($code) && !empty($placement)) {
            $db->insert('custom_ads', [
                'title' => $title,
                'code' => $code,
                'placement' => $placement,
                'position' => $position,
                'status' => $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $success_message = "Custom ad created successfully!";
        } else {
            $error_message = "Please fill all required fields";
        }
    }

    elseif ($page === 'custom' && $action === 'edit' && !empty($id)) {
        // Update custom ad
        $title = trim($_POST['title'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $placement = trim($_POST['placement'] ?? '');
        $position = trim($_POST['position'] ?? 'top');
        $status = isset($_POST['status']) ? 1 : 0;

        if (!empty($title) && !empty($code) && !empty($placement)) {
            $db->update('custom_ads', [
                'title' => $title,
                'code' => $code,
                'placement' => $placement,
                'position' => $position,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            $success_message = "Custom ad updated successfully!";
        } else {
            $error_message = "Please fill all required fields";
        }
    }

    elseif ($page === 'custom' && $action === 'delete' && !empty($id)) {
        // Delete custom ad
        $db->delete('custom_ads', 'id = ?', [$id]);
        $success_message = "Custom ad deleted successfully!";
    }
}

// Get custom ads
$custom_ads = $db->fetchAll("
    SELECT * FROM custom_ads 
    ORDER BY placement, position, created_at DESC
") ?: [];

// Get ad being edited
$edit_ad = null;
if ($action === 'edit' && !empty($id)) {
    $edit_ad = $db->fetchOne("SELECT * FROM custom_ads WHERE id = ?", [$id]);
}

$page_title = 'Ads Management';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="mt-4"><i class="fas fa-ad me-2"></i>Ads Management</h1>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= $success_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?= $error_message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div class="mb-4">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?= $page === 'settings' ? 'active' : '' ?>" href="?page=settings">
                    <i class="fas fa-cog me-1"></i> Google AdSense Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $page === 'custom' ? 'active' : '' ?>" href="?page=custom">
                    <i class="fas fa-edit me-1"></i> Custom Ads
                </a>
            </li>
        </ul>
    </div>

    <!-- Google AdSense Settings -->
    <?php if ($page === 'settings'): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fab fa-google me-2"></i>Google AdSense Configuration</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Publisher ID (Client ID) <span class="text-danger">*</span></label>
                        <input type="text" name="client_id" class="form-control" 
                               value="<?= htmlspecialchars($adsense_settings['client_id'] ?? '') ?>" 
                               placeholder="ca-pub-xxxxxxxxxxxxxxxx"
                               required>
                        <small class="text-muted d-block mt-2">Format: ca-pub-xxxxxxxxxxxxxxxx (18+ characters)</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="enabled" class="form-check-input" id="enableAds"
                                   <?= ($adsense_settings['enabled'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="enableAds">
                                <strong>Enable AdSense</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="mb-3"><i class="fas fa-layer-group me-2"></i>Ad Unit Configuration</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Banner Ad Slot ID</label>
                        <input type="text" name="ad_slot_banner" class="form-control" 
                               value="<?= htmlspecialchars($adsense_settings['ad_slot_banner'] ?? '') ?>"
                               placeholder="1234567890">
                        <small class="text-muted d-block mt-2">Header/Banner ads (728x90, 970x90, 970x250)</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sidebar Ad Slot ID</label>
                        <input type="text" name="ad_slot_sidebar" class="form-control" 
                               value="<?= htmlspecialchars($adsense_settings['ad_slot_sidebar'] ?? '') ?>"
                               placeholder="1234567890">
                        <small class="text-muted d-block mt-2">Sidebar ads (300x250, 300x600, 160x600)</small>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Article/In-Content Ad Slot ID</label>
                        <input type="text" name="ad_slot_article" class="form-control" 
                               value="<?= htmlspecialchars($adsense_settings['ad_slot_article'] ?? '') ?>"
                               placeholder="1234567890">
                        <small class="text-muted d-block mt-2">In-article/content ads (300x250, 336x280)</small>
                    </div>
                </div>

                <div class="alert alert-info mt-4" style="background-color: #e7f3ff; border-color: #b3d9ff;">
                    <strong>📌 How to get your Publisher ID and Ad Slot IDs:</strong>
                    <ol class="mb-0 mt-2">
                        <li>Go to <a href="https://adsense.google.com" target="_blank" class="alert-link">Google AdSense</a></li>
                        <li>Find your Publisher ID in <strong>Settings → Account → Publisher ID</strong></li>
                        <li>Create ad units in <strong>Ads → Ad units</strong></li>
                        <li>Copy the ad unit ID (last part of the ad unit code)</li>
                        <li>Paste them in the fields above</li>
                        <li>Enable AdSense to activate the ads</li>
                    </ol>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save me-2"></i> Save Settings
                </button>
                <a href="dashboard.php" class="btn btn-secondary btn-lg ms-2">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </a>
            </form>
        </div>
    </div>

    <?php elseif ($page === 'custom'): ?>
    <!-- Custom Ads Management -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Custom Ads List</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Placement</th>
                                <th>Position</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($custom_ads)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No custom ads yet. Create one to get started.
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($custom_ads as $ad): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($ad['title']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars(substr($ad['code'], 0, 50)) ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($ad['placement']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($ad['position']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($ad['status']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><i class="fas fa-times-circle me-1"></i>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?page=custom&action=edit&id=<?= $ad['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-pencil-alt"></i> Edit
                                        </a>
                                        <a href="?page=custom&action=delete&id=<?= $ad['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this ad?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i> 
                        <?= $action === 'edit' && $edit_ad ? 'Edit Custom Ad' : 'Create Custom Ad' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Ad Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" 
                                   value="<?= htmlspecialchars($edit_ad['title'] ?? '') ?>"
                                   placeholder="e.g., Premium Subscription Banner"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Placement <span class="text-danger">*</span></label>
                            <select name="placement" class="form-select" required>
                                <option value="">Select Placement</option>
                                <option value="header" <?= ($edit_ad['placement'] ?? '') === 'header' ? 'selected' : '' ?>>Header</option>
                                <option value="sidebar" <?= ($edit_ad['placement'] ?? '') === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                                <option value="article" <?= ($edit_ad['placement'] ?? '') === 'article' ? 'selected' : '' ?>>Article/In-Content</option>
                                <option value="footer" <?= ($edit_ad['placement'] ?? '') === 'footer' ? 'selected' : '' ?>>Footer</option>
                                <option value="category" <?= ($edit_ad['placement'] ?? '') === 'category' ? 'selected' : '' ?>>Category Page</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <select name="position" class="form-select">
                                <option value="top" <?= ($edit_ad['position'] ?? '') === 'top' ? 'selected' : '' ?>>Top</option>
                                <option value="middle" <?= ($edit_ad['position'] ?? '') === 'middle' ? 'selected' : '' ?>>Middle</option>
                                <option value="bottom" <?= ($edit_ad['position'] ?? '') === 'bottom' ? 'selected' : '' ?>>Bottom</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">HTML/Ad Code <span class="text-danger">*</span></label>
                            <textarea name="code" class="form-control" rows="6" 
                                      placeholder="Paste your ad HTML code here..." 
                                      required><?= htmlspecialchars($edit_ad['code'] ?? '') ?></textarea>
                            <small class="text-muted d-block mt-2">Support HTML, JavaScript, and Image tags</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="status" class="form-check-input" id="adStatus"
                                       <?= ($edit_ad['status'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="adStatus">
                                    <strong>Active</strong>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> 
                            <?= $action === 'edit' ? 'Update' : 'Create' ?> Ad
                        </button>

                        <?php if ($action === 'edit'): ?>
                        <a href="?page=custom" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-times-circle me-2"></i> Cancel
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
