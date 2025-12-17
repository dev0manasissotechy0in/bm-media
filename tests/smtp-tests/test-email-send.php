<?php
/**
 * Test Email Sending with Configured SMTP
 * This tests if emails can actually be sent using the configured SMTP settings
 */

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/EmailHelper.php';
require_once __DIR__ . '/includes/Settings.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Sending Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">📧 Email Sending Test</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
                            $testEmail = filter_var($_POST['test_email'], FILTER_VALIDATE_EMAIL);
                            
                            if (!$testEmail) {
                                echo '<div class="alert alert-danger">Invalid email address</div>';
                            } else {
                                echo '<div class="alert alert-info">Testing email sending to: <strong>' . htmlspecialchars($testEmail) . '</strong></div>';
                                
                                try {
                                    // Generate test OTP
                                    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                                    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                                    
                                    // Save to database
                                    $db = Database::getInstance();
                                    $insert_id = $db->insert('otp_codes', [
                                        'email' => $testEmail,
                                        'otp_code' => $otp,
                                        'purpose' => 'login',
                                        'user_type' => 'user',
                                        'expires_at' => $expires_at
                                    ]);
                                    
                                    echo '<div class="alert alert-success">✅ OTP Generated: <code>' . $otp . '</code></div>';
                                    echo '<div class="alert alert-info">📝 Saved to database (ID: ' . $insert_id . ')</div>';
                                    
                                    // Get SMTP configuration
                                    $smtp_host = Settings::get('auth_smtp_host', '');
                                    $smtp_port = Settings::get('auth_smtp_port', '587');
                                    $smtp_username = Settings::get('auth_smtp_username', '');
                                    $smtp_from = Settings::get('auth_smtp_from_email', '');
                                    
                                    echo '<div class="card mb-3 border-info">
                                            <div class="card-header bg-info text-white">SMTP Configuration</div>
                                            <div class="card-body">
                                                <ul class="mb-0">
                                                    <li><strong>Host:</strong> ' . htmlspecialchars($smtp_host) . '</li>
                                                    <li><strong>Port:</strong> ' . htmlspecialchars($smtp_port) . '</li>
                                                    <li><strong>Username:</strong> ' . htmlspecialchars($smtp_username) . '</li>
                                                    <li><strong>From Email:</strong> ' . htmlspecialchars($smtp_from) . '</li>
                                                </ul>
                                            </div>
                                        </div>';
                                    
                                    echo '<div class="alert alert-warning">⏳ Attempting to send email... (This may take 10-30 seconds)</div>';
                                    
                                    // Flush output so user sees progress
                                    if (ob_get_level() > 0) {
                                        ob_flush();
                                    }
                                    flush();
                                    
                                    // Set timeout for email sending
                                    set_time_limit(60);
                                    
                                    // Try to send email
                                    $startTime = microtime(true);
                                    
                                    try {
                                        $emailHelper = new EmailHelper('auth');
                                        $emailSent = $emailHelper->sendOTP($testEmail, $otp, 'Test User', 'user');
                                        
                                        $duration = round(microtime(true) - $startTime, 2);
                                        
                                        if ($emailSent) {
                                            echo '<div class="alert alert-success">
                                                    <h5>✅ Email Sent Successfully!</h5>
                                                    <p class="mb-0">Time taken: ' . $duration . ' seconds</p>
                                                    <p class="mb-0">Check inbox for: <strong>' . htmlspecialchars($testEmail) . '</strong></p>
                                                    <p class="mb-0">Your OTP: <code style="font-size: 1.5em">' . $otp . '</code></p>
                                                </div>';
                                        } else {
                                            echo '<div class="alert alert-danger">
                                                    <h5>❌ Email Sending Failed</h5>
                                                    <p class="mb-0">sendOTP() returned false</p>
                                                    <p class="mb-0">Time taken: ' . $duration . ' seconds</p>
                                                    <p class="mb-0">Your OTP for testing: <code>' . $otp . '</code></p>
                                                </div>';
                                        }
                                    } catch (Exception $e) {
                                        $duration = round(microtime(true) - $startTime, 2);
                                        echo '<div class="alert alert-danger">
                                                <h5>❌ Email Sending Error</h5>
                                                <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                                                <p class="mb-0">Time taken: ' . $duration . ' seconds</p>
                                                <hr>
                                                <h6>Possible Issues:</h6>
                                                <ul>
                                                    <li>SMTP credentials incorrect</li>
                                                    <li>SMTP server not reachable</li>
                                                    <li>Port blocked by firewall</li>
                                                    <li>From email not authorized</li>
                                                    <li>SSL/TLS certificate issues</li>
                                                </ul>
                                                <p class="mb-0">Your OTP for testing: <code>' . $otp . '</code></p>
                                            </div>';
                                    }
                                    
                                } catch (Exception $e) {
                                    echo '<div class="alert alert-danger">
                                            <h5>❌ Test Failed</h5>
                                            <p class="mb-0">' . htmlspecialchars($e->getMessage()) . '</p>
                                        </div>';
                                }
                            }
                        } else {
                            // Show form
                            ?>
                            <div class="alert alert-info">
                                <strong>📧 This tool will:</strong>
                                <ol class="mb-0">
                                    <li>Generate a test OTP</li>
                                    <li>Save it to the database</li>
                                    <li>Attempt to send it via email using configured SMTP</li>
                                    <li>Show you the result</li>
                                </ol>
                            </div>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="test_email" class="form-label">Enter Email Address to Test:</label>
                                    <input type="email" 
                                           class="form-control" 
                                           id="test_email" 
                                           name="test_email" 
                                           value="Manasissotechy@gmail.com"
                                           required>
                                    <div class="form-text">We'll send a test OTP to this email address</div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    📧 Send Test Email
                                </button>
                            </form>
                            
                            <hr class="my-4">
                            
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    Current SMTP Status
                                </div>
                                <div class="card-body">
                                    <?php
                                    $smtp_host = Settings::get('auth_smtp_host', '');
                                    $smtp_port = Settings::get('auth_smtp_port', '587');
                                    $smtp_username = Settings::get('auth_smtp_username', '');
                                    $smtp_enabled = Settings::get('auth_smtp_enabled', '0');
                                    
                                    if (empty($smtp_host)) {
                                        echo '<div class="alert alert-warning mb-0">
                                                ⚠️ SMTP not configured. 
                                                <a href="admin/smtp-multi-config.php" class="alert-link">Configure SMTP</a>
                                            </div>';
                                    } else {
                                        echo '<ul class="mb-0">
                                                <li><strong>Host:</strong> ' . htmlspecialchars($smtp_host) . '</li>
                                                <li><strong>Port:</strong> ' . htmlspecialchars($smtp_port) . '</li>
                                                <li><strong>Username:</strong> ' . htmlspecialchars($smtp_username) . '</li>
                                                <li><strong>Enabled:</strong> ' . ($smtp_enabled == '1' ? '✅ Yes' : '❌ No') . '</li>
                                            </ul>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="admin/smtp-multi-config.php" class="btn btn-secondary">⚙️ Configure SMTP</a>
                    <a href="test-otp-connection.php" class="btn btn-info">🔍 OTP Status</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
