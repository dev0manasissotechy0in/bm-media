<?php
/**
 * Multi-SMTP Configuration
 * Manages separate SMTP servers for Auth, Newsletter, and Contact emails
 */

require_once 'auth_check.php';

$page_title = 'Multi-SMTP Configuration';
$db = Database::getInstance();

$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purpose = $_POST['purpose'] ?? '';
    
    if (in_array($purpose, ['auth', 'newsletter', 'contact'])) {
        $settings = [
            $purpose . '_smtp_enabled' => isset($_POST['enabled']) ? '1' : '0',
            $purpose . '_smtp_host' => trim($_POST['host'] ?? ''),
            $purpose . '_smtp_port' => (int)($_POST['port'] ?? 587),
            $purpose . '_smtp_username' => trim($_POST['username'] ?? ''),
            $purpose . '_smtp_encryption' => $_POST['encryption'] ?? 'tls',
            $purpose . '_smtp_from_email' => trim($_POST['from_email'] ?? ''),
            $purpose . '_smtp_from_name' => trim($_POST['from_name'] ?? '')
        ];
        
        // Only update password if provided
        if (!empty($_POST['password'])) {
            $settings[$purpose . '_smtp_password'] = $_POST['password'];
        }
        
        foreach ($settings as $key => $value) {
            $existing = $db->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
            
            if ($existing) {
                $db->update('settings', 
                    ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 
                    'setting_key = ?', 
                    [$key]
                );
            } else {
                $db->insert('settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'smtp_purpose' => $purpose
                ]);
            }
        }
        
        $success = ucfirst($purpose) . ' SMTP settings saved successfully!';
    }
}

// Load current settings for all purposes
$purposes = ['auth', 'newsletter', 'contact'];
$smtp_configs = [];

foreach ($purposes as $purpose) {
    $config = $db->fetchAll(
        "SELECT setting_key, setting_value FROM settings WHERE smtp_purpose = ?",
        [$purpose]
    );
    
    $settings = [];
    foreach ($config as $setting) {
        $key = str_replace($purpose . '_smtp_', '', $setting['setting_key']);
        $settings[$key] = $setting['setting_value'];
    }
    
    $smtp_configs[$purpose] = $settings;
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-envelope-at-fill"></i> Multi-SMTP Configuration
            </h1>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Multi-SMTP Setup:</strong> Configure separate SMTP servers for different email types:
            <ul class="mb-0 mt-2">
                <li><strong>Auth SMTP:</strong> OTP codes, password resets, authentication emails</li>
                <li><strong>Newsletter SMTP:</strong> Newsletter campaigns and bulk emails</li>
                <li><strong>Contact SMTP:</strong> Contact form notifications and inquiries</li>
            </ul>
        </div>
        
        <!-- Auth SMTP -->
        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-shield-lock-fill"></i> Authentication SMTP</h5>
                <small>Used for OTP verification, login, and password reset emails</small>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="purpose" value="auth">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="auth_enabled" 
                                           <?= ($smtp_configs['auth']['enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="auth_enabled">
                                        <?= ($smtp_configs['auth']['enabled'] ?? '0') == '1' ? 'Enabled' : 'Disabled' ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Encryption</label>
                                <select name="encryption" class="form-select">
                                    <option value="tls" <?= ($smtp_configs['auth']['encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS (Recommended)</option>
                                    <option value="ssl" <?= ($smtp_configs['auth']['encryption'] ?? 'tls') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Port</label>
                                <input type="number" name="port" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['auth']['port'] ?? '587') ?>" required>
                                <small class="text-muted">TLS: 587, SSL: 465</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">SMTP Host</label>
                                <input type="text" name="host" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['auth']['host'] ?? '') ?>" 
                                       placeholder="smtp.example.com" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['auth']['username'] ?? '') ?>" 
                                       placeholder="auth@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Leave blank to keep current">
                                <small class="text-muted">Only enter if changing password</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">From Email</label>
                                <input type="email" name="from_email" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['auth']['from_email'] ?? '') ?>" 
                                       placeholder="noreply@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">From Name</label>
                                <input type="text" name="from_name" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['auth']['from_name'] ?? 'Authentication') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Auth SMTP
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Newsletter SMTP -->
        <div class="card mb-4 border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-envelope-paper-fill"></i> Newsletter SMTP</h5>
                <small>Used for newsletter campaigns and article notifications</small>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="purpose" value="newsletter">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="newsletter_enabled" 
                                           <?= ($smtp_configs['newsletter']['enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="newsletter_enabled">
                                        <?= ($smtp_configs['newsletter']['enabled'] ?? '0') == '1' ? 'Enabled' : 'Disabled' ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Encryption</label>
                                <select name="encryption" class="form-select">
                                    <option value="tls" <?= ($smtp_configs['newsletter']['encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS (Recommended)</option>
                                    <option value="ssl" <?= ($smtp_configs['newsletter']['encryption'] ?? 'tls') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Port</label>
                                <input type="number" name="port" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['newsletter']['port'] ?? '587') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">SMTP Host</label>
                                <input type="text" name="host" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['newsletter']['host'] ?? '') ?>" 
                                       placeholder="smtp.example.com" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['newsletter']['username'] ?? '') ?>" 
                                       placeholder="newsletter@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">From Email</label>
                                <input type="email" name="from_email" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['newsletter']['from_email'] ?? '') ?>" 
                                       placeholder="newsletter@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">From Name</label>
                                <input type="text" name="from_name" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['newsletter']['from_name'] ?? 'Newsletter') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Save Newsletter SMTP
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Contact SMTP -->
        <div class="card mb-4 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-chat-dots-fill"></i> Contact Form SMTP</h5>
                <small>Used for contact form notifications and customer inquiries</small>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="purpose" value="contact">
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="enabled" class="form-check-input" id="contact_enabled" 
                                           <?= ($smtp_configs['contact']['enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="contact_enabled">
                                        <?= ($smtp_configs['contact']['enabled'] ?? '0') == '1' ? 'Enabled' : 'Disabled' ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Encryption</label>
                                <select name="encryption" class="form-select">
                                    <option value="tls" <?= ($smtp_configs['contact']['encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS (Recommended)</option>
                                    <option value="ssl" <?= ($smtp_configs['contact']['encryption'] ?? 'tls') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Port</label>
                                <input type="number" name="port" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['contact']['port'] ?? '587') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">SMTP Host</label>
                                <input type="text" name="host" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['contact']['host'] ?? '') ?>" 
                                       placeholder="smtp.example.com" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['contact']['username'] ?? '') ?>" 
                                       placeholder="contact@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" name="password" class="form-control" 
                                       placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">From Email</label>
                                <input type="email" name="from_email" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['contact']['from_email'] ?? '') ?>" 
                                       placeholder="contact@example.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">From Name</label>
                                <input type="text" name="from_name" class="form-control" 
                                       value="<?= htmlspecialchars($smtp_configs['contact']['from_name'] ?? 'Contact Form') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-save"></i> Save Contact SMTP
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Configuration Status -->
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-diagram-3-fill"></i> Configuration Status</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>SMTP Host</th>
                                <th>Port</th>
                                <th>From Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Auth (OTP)</strong></td>
                                <td>
                                    <?php if (($smtp_configs['auth']['enabled'] ?? '0') == '1'): ?>
                                        <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($smtp_configs['auth']['host'] ?? 'Not configured') ?></td>
                                <td><?= htmlspecialchars($smtp_configs['auth']['port'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($smtp_configs['auth']['from_email'] ?? 'Not configured') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Newsletter</strong></td>
                                <td>
                                    <?php if (($smtp_configs['newsletter']['enabled'] ?? '0') == '1'): ?>
                                        <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($smtp_configs['newsletter']['host'] ?? 'Not configured') ?></td>
                                <td><?= htmlspecialchars($smtp_configs['newsletter']['port'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($smtp_configs['newsletter']['from_email'] ?? 'Not configured') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Contact Form</strong></td>
                                <td>
                                    <?php if (($smtp_configs['contact']['enabled'] ?? '0') == '1'): ?>
                                        <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($smtp_configs['contact']['host'] ?? 'Not configured') ?></td>
                                <td><?= htmlspecialchars($smtp_configs['contact']['port'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($smtp_configs['contact']['from_email'] ?? 'Not configured') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning mb-0 mt-3">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Important:</strong> Make sure to enable each SMTP configuration after entering credentials. Disabled SMTPs will fall back to the general SMTP server or PHP mail().
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php include 'includes/footer.php'; ?>
