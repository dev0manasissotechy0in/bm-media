<?php
/**
 * Quick PHPMailer Installation Check
 */

require_once '../config/config.php';

echo "<h1>PHPMailer & SMTP Configuration Check</h1>";

// Check if Composer autoloader exists
if (file_exists('../vendor/autoload.php')) {
    echo "✅ Composer autoloader found<br>";
    require_once '../vendor/autoload.php';
} else {
    echo "❌ Composer autoloader NOT found<br>";
    die();
}

// Check if PHPMailer class exists
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer class loaded successfully<br>";
} else {
    echo "❌ PHPMailer class NOT found<br>";
    die();
}

// Check EmailService
require_once '../includes/EmailService.php';
if (class_exists('EmailService')) {
    echo "✅ EmailService class loaded<br>";
} else {
    echo "❌ EmailService class NOT found<br>";
    die();
}

// Check SMTP configurations
$db = Database::getInstance();
$purposes = ['auth', 'newsletter', 'contact'];

echo "<br><h2>SMTP Configuration Status:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Purpose</th><th>Enabled</th><th>Host</th><th>Port</th><th>Username</th><th>From Email</th></tr>";

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
    
    $enabled = ($settings['enabled'] ?? '0') == '1' ? '✅ Yes' : '❌ No';
    $host = $settings['host'] ?? 'Not set';
    $port = $settings['port'] ?? 'Not set';
    $username = $settings['username'] ?? 'Not set';
    $from_email = $settings['from_email'] ?? 'Not set';
    
    $rowColor = ($settings['enabled'] ?? '0') == '1' ? 'background: #d4edda;' : 'background: #f8d7da;';
    
    echo "<tr style='$rowColor'>";
    echo "<td><strong>" . strtoupper($purpose) . "</strong></td>";
    echo "<td>$enabled</td>";
    echo "<td>$host</td>";
    echo "<td>$port</td>";
    echo "<td>$username</td>";
    echo "<td>$from_email</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><hr><br>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li><a href='smtp-multi-config.php'>Configure SMTP Settings</a> - Set up your email server details</li>";
echo "<li><a href='smtp-test.php'>Test SMTP Connection</a> - Send test emails to verify configuration</li>";
echo "<li><a href='login.php'>Try Admin Login</a> - Test OTP email delivery</li>";
echo "</ol>";

echo "<br><strong>Note:</strong> Make sure to enable and configure at least the 'auth' SMTP for login OTP to work.";
?>
