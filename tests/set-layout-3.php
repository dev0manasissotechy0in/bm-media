<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Update to layout-3
$updated = $db->query("UPDATE app_settings SET post_details_layout = 'layout-3' WHERE id = 1");

if ($updated) {
    echo "✅ Successfully updated to 'layout-3'\n\n";
    
    // Verify
    $verify = $db->fetchOne("SELECT post_details_layout FROM app_settings LIMIT 1");
    echo "Current value: " . $verify['post_details_layout'] . "\n\n";
    
    echo "🔄 Hot restart your Flutter app now!\n";
} else {
    echo "❌ Failed to update setting\n";
}
