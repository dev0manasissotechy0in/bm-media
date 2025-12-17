<?php
/**
 * Quick Test for FCM v1 Lite
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config.php';
require_once 'includes/Settings.php';
require_once 'includes/FCMv1HelperLite.php';

header('Content-Type: text/plain');

echo "=== FCM v1 Lite Test ===\n\n";

// Check service account file
$serviceAccountPath = __DIR__ . '/config/brackoddmedia-56b89-firebase-adminsdk-k7hcs-e9da41db1c.json';
echo "Service Account Path: $serviceAccountPath\n";
echo "File Exists: " . (file_exists($serviceAccountPath) ? 'YES ✓' : 'NO ✗') . "\n\n";

if (file_exists($serviceAccountPath)) {
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
    echo "Project ID: " . ($serviceAccount['project_id'] ?? 'N/A') . "\n";
    echo "Client Email: " . ($serviceAccount['client_email'] ?? 'N/A') . "\n";
    echo "Private Key: " . (isset($serviceAccount['private_key']) ? 'Present ✓' : 'Missing ✗') . "\n\n";
}

// Check settings
echo "Push Notifications Enabled: " . (Settings::get('enable_push_notifications') === '1' ? 'YES ✓' : 'NO ✗') . "\n";
echo "FCM Server Key Set: " . (!empty(Settings::get('fcm_server_key')) ? 'YES ✓' : 'NO ✗') . "\n\n";

// Initialize FCM
try {
    $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
    echo "FCM v1 Lite Initialized: YES ✓\n\n";
    
    // Try to send a test notification
    echo "Attempting to send test notification...\n";
    $response = $fcm->sendToTopic(
        'all',
        'Test Notification',
        'This is a test from PHP',
        ['type' => 'test', 'timestamp' => date('Y-m-d H:i:s')],
        ['badge' => '🧪 Test', 'channel_id' => 'default']
    );
    
    if ($response) {
        echo "SUCCESS ✓\n";
        echo "Response: " . $response . "\n";
    } else {
        echo "FAILED ✗\n";
        echo "Check Apache error log for details.\n";
    }
    
} catch (Exception $e) {
    echo "ERROR ✗\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
