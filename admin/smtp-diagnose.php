<?php
/**
 * SMTP Connection Diagnostic Tool
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(60);

require_once '../config/config.php';

echo "<h1>SMTP Connection Diagnostic</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }</style>";

// Get Auth SMTP configuration
$db = Database::getInstance();
$config = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE smtp_purpose = 'auth'");

$settings = [];
foreach ($config as $setting) {
    $key = str_replace('auth_smtp_', '', $setting['setting_key']);
    $settings[$key] = $setting['setting_value'];
}

echo "<h2>1. Configuration Check</h2>";
echo "<pre>";
echo "Host: " . ($settings['host'] ?? 'NOT SET') . "\n";
echo "Port: " . ($settings['port'] ?? 'NOT SET') . "\n";
echo "Username: " . ($settings['username'] ?? 'NOT SET') . "\n";
echo "Password: " . (isset($settings['password']) && !empty($settings['password']) ? '***SET***' : 'NOT SET') . "\n";
echo "Encryption: " . ($settings['encryption'] ?? 'NOT SET') . "\n";
echo "Enabled: " . ($settings['enabled'] ?? '0') . "\n";
echo "</pre>";

if (empty($settings['host']) || empty($settings['username']) || empty($settings['password'])) {
    echo "<p class='error'>❌ SMTP configuration is incomplete. Please configure it first.</p>";
    exit;
}

echo "<h2>2. Network Connection Test</h2>";

// Test basic connectivity to SMTP host
$host = $settings['host'];
$port = $settings['port'];

echo "<p class='info'>Testing connection to $host:$port...</p>";

$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if ($fp) {
    echo "<p class='success'>✅ Successfully connected to $host:$port</p>";
    fclose($fp);
} else {
    echo "<p class='error'>❌ Cannot connect to $host:$port</p>";
    echo "<p class='error'>Error: $errstr ($errno)</p>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>Firewall blocking outbound connections on port $port</li>";
    echo "<li>SMTP host is incorrect or down</li>";
    echo "<li>Port number is wrong (465 for SSL, 587 for TLS)</li>";
    echo "</ul>";
    exit;
}

echo "<h2>3. PHPMailer SMTP Test</h2>";

require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px; font-size: 12px;'>[$level] " . htmlspecialchars($str) . "</div>";
    };
    
    $mail->isSMTP();
    $mail->Host = $settings['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['username'];
    $mail->Password = $settings['password'];
    
    // Auto-detect encryption based on port
    if ($port == 465) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        echo "<p class='info'>Using SSL encryption (port 465)</p>";
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        echo "<p class='info'>Using TLS encryption (port 587)</p>";
    }
    
    $mail->Port = $port;
    $mail->Timeout = 10;
    
    echo "<p class='info'>Attempting SMTP authentication...</p>";
    
    // Try to connect and authenticate
    $mail->smtpConnect();
    
    echo "<p class='success'>✅ SMTP Authentication Successful!</p>";
    
    $mail->smtpClose();
    
    echo "<h2>4. Test Email Send</h2>";
    
    // Now try sending a test email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $settings['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['username'];
    $mail->Password = $settings['password'];
    $mail->SMTPSecure = ($port == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $port;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom($settings['from_email'] ?? $settings['username'], $settings['from_name'] ?? 'Test');
    $mail->addAddress($settings['username']); // Send to self
    
    $mail->isHTML(true);
    $mail->Subject = 'SMTP Test - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>Test Email</h1><p>This is a test email from the SMTP diagnostic tool.</p>';
    
    if ($mail->send()) {
        echo "<p class='success'>✅ Test email sent successfully to " . htmlspecialchars($settings['username']) . "</p>";
        echo "<p>Check your inbox to confirm delivery.</p>";
    } else {
        echo "<p class='error'>❌ Failed to send test email</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ SMTP Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>Mailer Error: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
    
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ul>";
    echo "<li>Verify your SMTP username and password are correct</li>";
    echo "<li>Check if your email provider requires app-specific passwords</li>";
    echo "<li>Ensure the encryption method matches the port (SSL for 465, TLS for 587)</li>";
    echo "<li>Contact your hosting provider to ensure SMTP ports are not blocked</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li><strong>Gmail:</strong> Use port 587 with TLS, enable 2FA and use App Password</li>";
echo "<li><strong>Outlook/Office365:</strong> Use smtp.office365.com:587 with TLS</li>";
echo "<li><strong>Hostinger:</strong> Use your domain's SMTP with the credentials from cPanel</li>";
echo "<li><strong>Local Testing:</strong> Use Mailtrap.io (fake SMTP for testing)</li>";
echo "</ul>";

echo "<p><a href='smtp-multi-config.php'>← Back to SMTP Configuration</a> | <a href='smtp-test.php'>Go to Full Test Page →</a></p>";
?>
