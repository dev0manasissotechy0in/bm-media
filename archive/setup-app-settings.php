<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "<h2>Creating App Settings Table</h2>\n";

try {
    // Create app_settings table
    $db->query("
        CREATE TABLE IF NOT EXISTS app_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            post_details_layout VARCHAR(50) DEFAULT 'random',
            category_tile_layout VARCHAR(50) DEFAULT 'grid',
            home_categories TEXT,
            video_tab BOOLEAN DEFAULT TRUE,
            audio_tab BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    echo "<p style='color:green;'>✅ Table created successfully!</p>\n";
    
    // Check if settings exist
    $existing = $db->fetchOne("SELECT * FROM app_settings LIMIT 1");
    
    if (!$existing) {
        // Insert default settings
        $db->query("
            INSERT INTO app_settings (post_details_layout, category_tile_layout, video_tab, audio_tab) 
            VALUES ('random', 'grid', 1, 1)
        ");
        echo "<p style='color:green;'>✅ Default settings inserted!</p>\n";
    } else {
        echo "<p style='color:blue;'>ℹ️ Settings already exist</p>\n";
    }
    
    echo "<h3>Current Settings:</h3>\n";
    $settings = $db->fetchOne("SELECT * FROM app_settings LIMIT 1");
    echo "<pre>";
    print_r($settings);
    echo "</pre>";
    
    echo "<p><a href='check-layout-settings.php'>Go to Layout Settings Page</a></p>\n";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
