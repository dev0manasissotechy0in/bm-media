<?php
/**
 * Simple SMTP Port Check - No PHPMailer, just socket test
 */

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Settings.php';

$smtp_host = Settings::get('auth_smtp_host', '');
$smtp_port = Settings::get('auth_smtp_port', '587');
$smtp_username = Settings::get('auth_smtp_username', '');
$smtp_password = Settings::get('auth_smtp_password', '');
$smtp_encryption = Settings::get('auth_smtp_encryption', 'tls');

?>
<!DOCTYPE html>
<html>
<head>
    <title>SMTP Port Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔍 SMTP Port Check</h4>
                    </div>
                    <div class="card-body">
                        <div class="card mb-3">
                            <div class="card-header">Current Configuration</div>
                            <div class="card-body">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <th>Host:</th>
                                        <td><?= htmlspecialchars($smtp_host ?: 'Not set') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Port:</th>
                                        <td><?= htmlspecialchars($smtp_port) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Username:</th>
                                        <td><?= htmlspecialchars($smtp_username ?: 'Not set') ?></td>
                                    </tr>
                                    <tr>
                                        <th>Password:</th>
                                        <td><?= !empty($smtp_password) ? '✅ Set' : '❌ Not set' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Encryption:</th>
                                        <td><?= htmlspecialchars(strtoupper($smtp_encryption)) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if (empty($smtp_host)): ?>
                            <div class="alert alert-warning">
                                ⚠️ SMTP host not configured. 
                                <a href="admin/smtp-multi-config.php">Configure it here</a>
                            </div>
                        <?php else: ?>
                            <h5>Port Connectivity Test</h5>
                            <?php
                            // Test different ports
                            $ports_to_test = [
                                587 => 'SMTP with STARTTLS (recommended)',
                                465 => 'SMTP with SSL',
                                25 => 'SMTP (often blocked)'
                            ];
                            
                            foreach ($ports_to_test as $port => $description) {
                                echo '<div class="mb-2">';
                                echo '<strong>Testing port ' . $port . '</strong> (' . $description . '): ';
                                
                                $timeout = 3;
                                $start = microtime(true);
                                $fp = @fsockopen($smtp_host, $port, $errno, $errstr, $timeout);
                                $duration = round(microtime(true) - $start, 2);
                                
                                if ($fp) {
                                    echo '<span class="badge bg-success">✅ OPEN</span> ';
                                    echo '<small class="text-muted">(' . $duration . 's)</small>';
                                    fclose($fp);
                                } else {
                                    echo '<span class="badge bg-danger">❌ BLOCKED</span> ';
                                    echo '<small class="text-muted">Error: ' . htmlspecialchars($errstr) . '</small>';
                                }
                                echo '</div>';
                            }
                            ?>

                            <hr class="my-4">

                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    📋 Recommendations
                                </div>
                                <div class="card-body">
                                    <h6>If all ports show BLOCKED:</h6>
                                    <ul>
                                        <li>Your ISP or firewall is blocking SMTP</li>
                                        <li>Check Windows Firewall settings</li>
                                        <li>Try from a different network</li>
                                        <li>Contact Hostinger support</li>
                                    </ul>

                                    <h6>If port 587 or 465 is OPEN:</h6>
                                    <ul>
                                        <li>The server is reachable ✅</li>
                                        <li>Issue is likely with credentials or configuration</li>
                                        <li>Double-check username/password in 
                                            <a href="admin/smtp-multi-config.php">SMTP settings</a>
                                        </li>
                                    </ul>

                                    <h6>Hostinger SMTP Requirements:</h6>
                                    <ul class="mb-0">
                                        <li><strong>Host:</strong> smtp.hostinger.com</li>
                                        <li><strong>Port:</strong> 587 (TLS) or 465 (SSL)</li>
                                        <li><strong>Username:</strong> Your full email (e.g., no-reply@yourdomain.com)</li>
                                        <li><strong>Password:</strong> Your email account password</li>
                                        <li><strong>Important:</strong> Username and From Email must match!</li>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="admin/smtp-multi-config.php" class="btn btn-primary">⚙️ Configure SMTP</a>
                    <a href="javascript:location.reload()" class="btn btn-secondary">🔄 Test Again</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
