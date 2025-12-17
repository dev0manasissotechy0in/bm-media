<?php
/**
 * SMTP Configuration Validator & Fixer
 */

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Settings.php';

$db = Database::getInstance();
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_config'])) {
    try {
        $recommended_port = $_POST['recommended_port'];
        $recommended_encryption = $_POST['recommended_encryption'];
        
        // Update settings
        $db->query(
            "UPDATE settings SET setting_value = ? WHERE setting_key = 'auth_smtp_port'",
            [$recommended_port]
        );
        
        $db->query(
            "UPDATE settings SET setting_value = ? WHERE setting_key = 'auth_smtp_encryption'",
            [$recommended_encryption]
        );
        
        $message = '<div class="alert alert-success">✅ Configuration updated! Port: ' . $recommended_port . ', Encryption: ' . strtoupper($recommended_encryption) . '</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">❌ Update failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

$smtp_host = Settings::get('auth_smtp_host', '');
$smtp_port = Settings::get('auth_smtp_port', '587');
$smtp_username = Settings::get('auth_smtp_username', '');
$smtp_password = Settings::get('auth_smtp_password', '');
$smtp_encryption = Settings::get('auth_smtp_encryption', 'tls');
$smtp_from_email = Settings::get('auth_smtp_from_email', '');

// Validate configuration
$issues = [];
$recommended_port = 587;
$recommended_encryption = 'tls';

if ($smtp_port == 465 && strtolower($smtp_encryption) !== 'ssl') {
    $issues[] = 'Port 465 requires SSL encryption (currently: ' . strtoupper($smtp_encryption) . ')';
    $recommended_port = 587;
    $recommended_encryption = 'tls';
}

if ($smtp_port == 587 && strtolower($smtp_encryption) === 'ssl') {
    $issues[] = 'Port 587 requires TLS/STARTTLS encryption (currently: SSL)';
    $recommended_port = 587;
    $recommended_encryption = 'tls';
}

if ($smtp_username !== $smtp_from_email && !empty($smtp_from_email)) {
    $issues[] = 'Username (' . $smtp_username . ') and From Email (' . $smtp_from_email . ') should match';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMTP Configuration Validator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">⚙️ SMTP Configuration Validator</h4>
                    </div>
                    <div class="card-body">
                        <?= $message ?>
                        
                        <div class="card mb-3">
                            <div class="card-header">Current Configuration</div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <th style="width: 150px;">Host:</th>
                                        <td><?= htmlspecialchars($smtp_host) ?></td>
                                        <td><?= !empty($smtp_host) ? '✅' : '❌' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Port:</th>
                                        <td><?= htmlspecialchars($smtp_port) ?></td>
                                        <td><?= in_array($smtp_port, [587, 465, 25]) ? '✅' : '⚠️' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Encryption:</th>
                                        <td><?= htmlspecialchars(strtoupper($smtp_encryption)) ?></td>
                                        <td><?= in_array(strtolower($smtp_encryption), ['tls', 'ssl']) ? '✅' : '❌' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Username:</th>
                                        <td><?= htmlspecialchars($smtp_username) ?></td>
                                        <td><?= !empty($smtp_username) ? '✅' : '❌' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Password:</th>
                                        <td><?= !empty($smtp_password) ? 'Set (length: ' . strlen($smtp_password) . ')' : 'Not set' ?></td>
                                        <td><?= !empty($smtp_password) ? '✅' : '❌' ?></td>
                                    </tr>
                                    <tr>
                                        <th>From Email:</th>
                                        <td><?= htmlspecialchars($smtp_from_email) ?></td>
                                        <td><?= !empty($smtp_from_email) ? '✅' : '❌' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if (!empty($issues)): ?>
                            <div class="alert alert-danger">
                                <h5>❌ Configuration Issues Detected:</h5>
                                <ul class="mb-0">
                                    <?php foreach ($issues as $issue): ?>
                                        <li><?= htmlspecialchars($issue) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="card border-success mb-3">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">✅ Recommended Fix</h5>
                                </div>
                                <div class="card-body">
                                    <p><strong>For Hostinger SMTP, use:</strong></p>
                                    <ul>
                                        <li><strong>Port:</strong> 587</li>
                                        <li><strong>Encryption:</strong> TLS (STARTTLS)</li>
                                    </ul>
                                    <p class="mb-0"><em>Alternative: Port 465 with SSL encryption</em></p>
                                    
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="recommended_port" value="587">
                                        <input type="hidden" name="recommended_encryption" value="tls">
                                        <button type="submit" name="fix_config" class="btn btn-success">
                                            🔧 Fix Configuration (Set Port 587 + TLS)
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <h5>✅ Configuration looks good!</h5>
                                <p class="mb-0">Port and encryption settings are compatible.</p>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="card border-info">
                            <div class="card-header bg-info text-white">
                                📚 Port & Encryption Reference
                            </div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Port</th>
                                            <th>Encryption</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="table-success">
                                            <td><strong>587</strong></td>
                                            <td>TLS (STARTTLS)</td>
                                            <td>✅ Recommended</td>
                                        </tr>
                                        <tr>
                                            <td><strong>465</strong></td>
                                            <td>SSL</td>
                                            <td>✅ Alternative</td>
                                        </tr>
                                        <tr class="table-warning">
                                            <td><strong>25</strong></td>
                                            <td>None / TLS</td>
                                            <td>⚠️ Often blocked by ISPs</td>
                                        </tr>
                                        <tr class="table-danger">
                                            <td><strong>465</strong></td>
                                            <td>TLS</td>
                                            <td>❌ WRONG - causes hanging</td>
                                        </tr>
                                        <tr class="table-danger">
                                            <td><strong>587</strong></td>
                                            <td>SSL</td>
                                            <td>❌ WRONG - causes errors</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="admin/smtp-multi-config.php" class="btn btn-secondary">⚙️ Full SMTP Settings</a>
                    <a href="check-smtp-port.php" class="btn btn-info">🔍 Port Check</a>
                    <a href="test-email-send.php" class="btn btn-success">📧 Test Email</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
