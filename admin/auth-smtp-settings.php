<?php
/**
 * Auth SMTP Settings Viewer
 * Quick page to check auth SMTP configuration for mobile login
 */

require_once '../config/config.php';
require_once '../includes/Database.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "Please login to admin panel first";
    exit;
}

$db = Database::getInstance();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_auth_smtp'])) {
    $updates = [
        'auth_smtp_enabled' => $_POST['auth_smtp_enabled'] ?? '0',
        'auth_smtp_host' => trim($_POST['auth_smtp_host'] ?? ''),
        'auth_smtp_port' => trim($_POST['auth_smtp_port'] ?? '587'),
        'auth_smtp_username' => trim($_POST['auth_smtp_username'] ?? ''),
        'auth_smtp_encryption' => $_POST['auth_smtp_encryption'] ?? 'tls',
        'auth_smtp_from_email' => trim($_POST['auth_smtp_from_email'] ?? ''),
        'auth_smtp_from_name' => trim($_POST['auth_smtp_from_name'] ?? ''),
    ];
    
    // Update password only if provided
    if (!empty($_POST['auth_smtp_password'])) {
        $updates['auth_smtp_password'] = $_POST['auth_smtp_password'];
    }
    
    foreach ($updates as $key => $value) {
        $db->query(
            "UPDATE settings SET setting_value = ? WHERE setting_key = ? AND smtp_purpose = 'auth'",
            [$value, $key]
        );
    }
    
    $success = "Auth SMTP settings updated successfully!";
}

// Get current settings
$settings = $db->fetchAll(
    "SELECT setting_key, setting_value FROM settings WHERE smtp_purpose = 'auth' ORDER BY setting_key"
);

$config = [];
foreach ($settings as $setting) {
    $config[$setting['setting_key']] = $setting['setting_value'];
}

// Check if settings exist
$settings_exist = !empty($settings);
if (!$settings_exist) {
    // Insert default settings
    $defaults = [
        ['auth_smtp_enabled', '0'],
        ['auth_smtp_host', 'smtp.gmail.com'],
        ['auth_smtp_port', '587'],
        ['auth_smtp_username', ''],
        ['auth_smtp_password', ''],
        ['auth_smtp_encryption', 'tls'],
        ['auth_smtp_from_email', ''],
        ['auth_smtp_from_name', 'Authentication'],
    ];
    
    foreach ($defaults as $default) {
        $db->query(
            "INSERT IGNORE INTO settings (setting_key, setting_value, smtp_purpose) VALUES (?, ?, 'auth')",
            $default
        );
    }
    
    // Reload settings
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$page_title = 'Auth SMTP Settings';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-envelope-at"></i> Auth SMTP Configuration
            </h1>
            <a href="settings.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Settings
            </a>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">SMTP Settings for Mobile Login OTP</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="auth_smtp_enabled" 
                                           value="1" id="authSmtpEnabled"
                                           <?= ($config['auth_smtp_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="authSmtpEnabled">
                                        <strong>Enable Auth SMTP</strong>
                                        <small class="d-block text-muted">Turn on to send OTP emails for mobile login</small>
                                    </label>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">SMTP Host *</label>
                                    <input type="text" name="auth_smtp_host" class="form-control" 
                                           value="<?= htmlspecialchars($config['auth_smtp_host'] ?? '') ?>"
                                           placeholder="smtp.gmail.com" required>
                                    <small class="text-muted">Gmail: smtp.gmail.com, SendGrid: smtp.sendgrid.net</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Port *</label>
                                    <input type="number" name="auth_smtp_port" class="form-control" 
                                           value="<?= htmlspecialchars($config['auth_smtp_port'] ?? '587') ?>"
                                           placeholder="587" required>
                                    <small class="text-muted">587 (TLS) or 465 (SSL)</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Encryption</label>
                                <select name="auth_smtp_encryption" class="form-select">
                                    <option value="tls" <?= ($config['auth_smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS (Recommended)</option>
                                    <option value="ssl" <?= ($config['auth_smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SMTP Username *</label>
                                <input type="text" name="auth_smtp_username" class="form-control" 
                                       value="<?= htmlspecialchars($config['auth_smtp_username'] ?? '') ?>"
                                       placeholder="your-email@gmail.com" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SMTP Password *</label>
                                <input type="password" name="auth_smtp_password" class="form-control" 
                                       placeholder="<?= !empty($config['auth_smtp_password']) ? '••••••••' : 'Enter password' ?>">
                                <small class="text-muted">
                                    <?= !empty($config['auth_smtp_password']) ? 'Leave blank to keep current password' : 'For Gmail, use App Password' ?>
                                </small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">From Email *</label>
                                <input type="email" name="auth_smtp_from_email" class="form-control" 
                                       value="<?= htmlspecialchars($config['auth_smtp_from_email'] ?? '') ?>"
                                       placeholder="noreply@yourdomain.com" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">From Name</label>
                                <input type="text" name="auth_smtp_from_name" class="form-control" 
                                       value="<?= htmlspecialchars($config['auth_smtp_from_name'] ?? '') ?>"
                                       placeholder="Your App Name">
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" name="save_auth_smtp" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Settings
                                </button>
                                <a href="check-smtp.php?purpose=auth" class="btn btn-outline-secondary" target="_blank">
                                    <i class="bi bi-envelope-check"></i> Test SMTP
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-info-circle"></i> Current Status
                        </h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <strong>SMTP Status:</strong>
                                <span class="badge bg-<?= ($config['auth_smtp_enabled'] ?? '0') == '1' ? 'success' : 'warning' ?>">
                                    <?= ($config['auth_smtp_enabled'] ?? '0') == '1' ? 'Enabled' : 'Disabled' ?>
                                </span>
                            </li>
                            <li class="mb-2">
                                <strong>Host:</strong> 
                                <?= htmlspecialchars($config['auth_smtp_host'] ?? 'Not set') ?>
                            </li>
                            <li class="mb-2">
                                <strong>Port:</strong> 
                                <?= htmlspecialchars($config['auth_smtp_port'] ?? 'Not set') ?>
                            </li>
                            <li class="mb-2">
                                <strong>Username:</strong> 
                                <?= !empty($config['auth_smtp_username']) ? '✓ Configured' : '✗ Not set' ?>
                            </li>
                            <li class="mb-2">
                                <strong>Password:</strong> 
                                <?= !empty($config['auth_smtp_password']) ? '✓ Configured' : '✗ Not set' ?>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi bi-lightbulb"></i> Quick Setup (Gmail)
                        </h6>
                        <ol class="small mb-0">
                            <li>Enable 2-Step Verification on Google Account</li>
                            <li>Go to <a href="https://myaccount.google.com/apppasswords" target="_blank">App Passwords</a></li>
                            <li>Create new app password for "Mail"</li>
                            <li>Copy the 16-character password</li>
                            <li>Use it in SMTP Password field above</li>
                            <li>Set Host: smtp.gmail.com, Port: 587</li>
                            <li>Enable Auth SMTP and Save</li>
                            <li>Click "Test SMTP" to verify</li>
                        </ol>
                    </div>
                </div>

                <div class="card mt-3 bg-warning">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="bi bi-exclamation-triangle"></i> Important
                        </h6>
                        <ul class="small mb-0">
                            <li>OTP emails will NOT work if SMTP is disabled</li>
                            <li>Mobile login requires working email</li>
                            <li>Debug OTP has been removed for security</li>
                            <li>Test configuration before going live</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Database Records</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>Setting Key</th>
                                <th>Setting Value</th>
                                <th>Purpose</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $all_settings = $db->fetchAll(
                                "SELECT setting_key, setting_value, smtp_purpose FROM settings WHERE smtp_purpose = 'auth' ORDER BY setting_key"
                            );
                            foreach ($all_settings as $setting): 
                            ?>
                            <tr>
                                <td><code><?= htmlspecialchars($setting['setting_key']) ?></code></td>
                                <td>
                                    <?php if ($setting['setting_key'] == 'auth_smtp_password' && !empty($setting['setting_value'])): ?>
                                        <code>••••••••</code>
                                    <?php else: ?>
                                        <code><?= htmlspecialchars($setting['setting_value']) ?></code>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($setting['smtp_purpose']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
