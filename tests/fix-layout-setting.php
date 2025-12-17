<?php
/**
 * Fix app_settings layout value
 * Change 'layout1' to 'random' for proper layout rotation
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Check current setting
$current = $db->fetchOne("SELECT post_details_layout FROM app_settings LIMIT 1");

echo "<h2>Current Layout Setting</h2>";
echo "<p>Current value: <strong>" . htmlspecialchars($current['post_details_layout'] ?? 'NOT SET') . "</strong></p>";

// Update to 'random'
$updated = $db->query("UPDATE app_settings SET post_details_layout = 'random' WHERE id = 1");

if ($updated) {
    echo "<p style='color: green;'>✅ Successfully updated to 'random'</p>";
    
    // Verify
    $verify = $db->fetchOne("SELECT post_details_layout FROM app_settings LIMIT 1");
    echo "<p>New value: <strong>" . htmlspecialchars($verify['post_details_layout']) . "</strong></p>";
    
    echo "<h3>Valid Layout Options:</h3>";
    echo "<ul>";
    echo "<li><strong>random</strong> - Randomly picks Layout 1, 2, or 3 for each article</li>";
    echo "<li><strong>layout-1</strong> - Always use Layout 1 (Expandable AppBar)</li>";
    echo "<li><strong>layout-2</strong> - Always use Layout 2 (Compact)</li>";
    echo "<li><strong>layout-3</strong> - Always use Layout 3 (Full Screen Hero)</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<p><strong>⚠️ IMPORTANT:</strong> Hot restart your Flutter app to load the new setting!</p>";
} else {
    echo "<p style='color: red;'>❌ Failed to update setting</p>";
}

echo "<hr>";
echo "<p><a href='check-layout-settings.php'>← Back to Layout Settings</a></p>";
