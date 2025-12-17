<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "<h2>App Settings - Post Details Layout</h2>\n";

$settings = $db->fetchOne("SELECT * FROM app_settings LIMIT 1");

if ($settings) {
    echo "<p><strong>Current Setting:</strong> " . ($settings['post_details_layout'] ?? 'Not set') . "</p>\n";
    
    echo "<h3>Available Options:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>random</strong> - Randomly picks one of 3 layouts for each article</li>\n";
    echo "<li><strong>layout-1</strong> - Always use Details View 1 (AppBar with expandable image)</li>\n";
    echo "<li><strong>layout-2</strong> - Always use Details View 2 (Compact with small image)</li>\n";
    echo "<li><strong>layout-3</strong> - Always use Details View 3 (Full-screen hero image)</li>\n";
    echo "</ul>\n";
    
    echo "<h3>Change Layout Setting:</h3>\n";
    echo "<form method='POST'>\n";
    echo "<select name='layout' required>\n";
    echo "<option value='random'" . (($settings['post_details_layout'] ?? '') == 'random' ? ' selected' : '') . ">Random (varies per article)</option>\n";
    echo "<option value='layout-1'" . (($settings['post_details_layout'] ?? '') == 'layout-1' ? ' selected' : '') . ">Always Layout 1</option>\n";
    echo "<option value='layout-2'" . (($settings['post_details_layout'] ?? '') == 'layout-2' ? ' selected' : '') . ">Always Layout 2</option>\n";
    echo "<option value='layout-3'" . (($settings['post_details_layout'] ?? '') == 'layout-3' ? ' selected' : '') . ">Always Layout 3</option>\n";
    echo "</select>\n";
    echo "<button type='submit' name='update'>Update Setting</button>\n";
    echo "</form>\n";
} else {
    echo "<p style='color:red;'>No app settings found in database!</p>\n";
}

// Handle form submission
if (isset($_POST['update'])) {
    $layout = $_POST['layout'];
    $db->query("UPDATE app_settings SET post_details_layout = ?", [$layout]);
    echo "<p style='color:green;'>✅ Updated to: <strong>$layout</strong></p>\n";
    echo "<p><em>Restart the Flutter app to see changes</em></p>\n";
}
?>
