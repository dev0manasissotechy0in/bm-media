<?php
/**
 * Quick SMTP Fix for Hostinger - Port 465 requires SSL
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance();

try {
    // Fix: Port 465 with SSL (as per Hostinger documentation)
    $db->query(
        "UPDATE settings SET setting_value = '465' WHERE setting_key = 'auth_smtp_port'"
    );
    
    $db->query(
        "UPDATE settings SET setting_value = 'ssl' WHERE setting_key = 'auth_smtp_encryption'"
    );
    
    echo "✅ Configuration Fixed!<br>";
    echo "Port: 465<br>";
    echo "Encryption: SSL<br><br>";
    echo '<a href="test-email-send.php">Test Email Sending Now</a>';
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
