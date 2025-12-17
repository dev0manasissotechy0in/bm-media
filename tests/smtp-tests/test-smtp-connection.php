<?php
/**
 * SMTP Connection Test
 * Tests if we can connect to SMTP server without sending email
 */

header('Content-Type: text/html; charset=UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Settings.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔌 SMTP Connection Test</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get SMTP configuration
                        $smtp_host = Settings::get('auth_smtp_host', '');
                        $smtp_port = Settings::get('auth_smtp_port', '587');
                        $smtp_username = Settings::get('auth_smtp_username', '');
                        $smtp_password = Settings::get('auth_smtp_password', '');
                        $smtp_encryption = Settings::get('auth_smtp_encryption', 'tls');
                        $smtp_from_email = Settings::get('auth_smtp_from_email', '');
                        $smtp_from_name = Settings::get('auth_smtp_from_name', 'News Website');
                        
                        echo '<div class="card mb-3 border-info">
                                <div class="card-header bg-info text-white">Current SMTP Configuration</div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li><strong>Host:</strong> ' . htmlspecialchars($smtp_host) . '</li>
                                        <li><strong>Port:</strong> ' . htmlspecialchars($smtp_port) . '</li>
                                        <li><strong>Username:</strong> ' . htmlspecialchars($smtp_username) . '</li>
                                        <li><strong>Password:</strong> ' . (!empty($smtp_password) ? '✅ Set (length: ' . strlen($smtp_password) . ')' : '❌ Empty') . '</li>
                                        <li><strong>Encryption:</strong> ' . htmlspecialchars(strtoupper($smtp_encryption)) . '</li>
                                        <li><strong>From Email:</strong> ' . htmlspecialchars($smtp_from_email) . '</li>
                                        <li><strong>From Name:</strong> ' . htmlspecialchars($smtp_from_name) . '</li>
                                    </ul>
                                </div>
                            </div>';
                        
                        if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
                            echo '<div class="alert alert-danger">
                                    ❌ SMTP not fully configured. Please configure in 
                                    <a href="admin/smtp-multi-config.php" class="alert-link">Admin SMTP Settings</a>
                                </div>';
                        } else {
                            echo '<div class="alert alert-warning">⏳ Testing SMTP connection... (Timeout: 10 seconds)</div>';
                            
                            // Flush output
                            if (ob_get_level() > 0) ob_flush();
                            flush();
                            
                            $mail = new PHPMailer(true);
                            
                            try {
                                // Set timeout
                                set_time_limit(15);
                                
                                // Enable verbose debug output
                                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                                $mail->Debugoutput = function($str, $level) {
                                    echo "<div class='alert alert-secondary py-1 px-2 small mb-1'>" . 
                                         htmlspecialchars($str) . "</div>";
                                    if (ob_get_level() > 0) ob_flush();
                                    flush();
                                };
                                
                                // Server settings
                                $mail->isSMTP();
                                $mail->Host = $smtp_host;
                                $mail->SMTPAuth = true;
                                $mail->Username = $smtp_username;
                                $mail->Password = $smtp_password;
                                $mail->Port = $smtp_port;
                                $mail->Timeout = 10;
                                
                                // Set encryption
                                if ($smtp_encryption === 'ssl') {
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                } else {
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                }
                                
                                // Try to connect
                                echo '<div class="alert alert-info">🔄 Attempting connection to ' . 
                                     htmlspecialchars($smtp_host) . ':' . htmlspecialchars($smtp_port) . '...</div>';
                                
                                if (ob_get_level() > 0) ob_flush();
                                flush();
                                
                                $startTime = microtime(true);
                                
                                // Just test connection by calling smtpConnect
                                if ($mail->smtpConnect()) {
                                    $duration = round(microtime(true) - $startTime, 2);
                                    
                                    echo '<div class="alert alert-success mt-3">
                                            <h5>✅ SMTP Connection Successful!</h5>
                                            <p class="mb-0">Connected to ' . htmlspecialchars($smtp_host) . 
                                            ' in ' . $duration . ' seconds</p>
                                            <p class="mb-0">Server is ready to send emails.</p>
                                        </div>';
                                    
                                    // Close connection
                                    $mail->smtpClose();
                                } else {
                                    throw new Exception('Failed to connect to SMTP server');
                                }
                                
                            } catch (Exception $e) {
                                $duration = isset($startTime) ? round(microtime(true) - $startTime, 2) : 0;
                                
                                echo '<div class="alert alert-danger mt-3">
                                        <h5>❌ SMTP Connection Failed</h5>
                                        <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                                        <p class="mb-0"><strong>Time:</strong> ' . $duration . ' seconds</p>
                                        <hr>
                                        <h6>Common Issues:</h6>
                                        <ul>
                                            <li><strong>Wrong credentials:</strong> Check username/password</li>
                                            <li><strong>Port blocked:</strong> Try port 465 (SSL) or 25</li>
                                            <li><strong>Firewall:</strong> Allow outbound SMTP connections</li>
                                            <li><strong>Server settings:</strong> Verify encryption type (TLS/SSL)</li>
                                            <li><strong>Hostinger specific:</strong> Make sure using domain email, not personal email</li>
                                        </ul>
                                    </div>';
                                    
                                // Additional checks
                                echo '<div class="card border-warning mt-3">
                                        <div class="card-header bg-warning">🔍 Additional Diagnostics</div>
                                        <div class="card-body">';
                                
                                // Check if fsockopen works
                                echo '<h6>Testing Socket Connection:</h6>';
                                $fp = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
                                if ($fp) {
                                    echo '<div class="alert alert-success py-2">✅ Socket connection successful (port is open)</div>';
                                    fclose($fp);
                                } else {
                                    echo '<div class="alert alert-danger py-2">❌ Socket connection failed: ' . 
                                         htmlspecialchars($errstr) . ' (Code: ' . $errno . ')</div>';
                                    echo '<p class="mb-0"><strong>This usually means:</strong> Port is blocked or server is not reachable</p>';
                                }
                                
                                echo '</div></div>';
                            }
                        }
                        ?>
                        
                        <hr class="my-4">
                        
                        <div class="card border-secondary">
                            <div class="card-header bg-secondary text-white">
                                📝 Hostinger SMTP Notes
                            </div>
                            <div class="card-body">
                                <ul class="mb-0">
                                    <li>Host should be: <code>smtp.hostinger.com</code></li>
                                    <li>Port: <code>587</code> (TLS) or <code>465</code> (SSL)</li>
                                    <li>Use your <strong>domain email</strong> (e.g., no-reply@yourdomain.com)</li>
                                    <li>Password is your <strong>email password</strong>, not cPanel password</li>
                                    <li>From Email must match Username</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="admin/smtp-multi-config.php" class="btn btn-secondary">⚙️ Configure SMTP</a>
                    <a href="javascript:location.reload()" class="btn btn-primary">🔄 Test Again</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
