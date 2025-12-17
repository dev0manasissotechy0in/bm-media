<?php
/**
 * Add show_in_app column to custom_pages table
 * Run this once: http://localhost/admin/add-show-in-app-column.php
 */

require_once '../config/config.php';
require_once '../includes/Database.php';

// Check admin authentication (optional - remove if you want to run without login)
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo "<h3>Please login to admin panel first</h3>";
    echo "<a href='index.php'>Go to Admin Login</a>";
    exit;
}

$db = Database::getInstance();

echo "<h2>Adding show_in_app Column to custom_pages Table</h2>";
echo "<pre>";

try {
    // Check if column already exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM custom_pages LIKE 'show_in_app'");
    
    if (!empty($columns)) {
        echo "✅ Column 'show_in_app' already exists!\n";
    } else {
        echo "⏳ Adding 'show_in_app' column...\n";
        
        // Add column
        $db->query("ALTER TABLE custom_pages ADD COLUMN show_in_app BOOLEAN DEFAULT FALSE AFTER show_in_footer");
        
        echo "✅ Column 'show_in_app' added successfully!\n";
    }
    
    // Check if index exists
    $indexes = $db->fetchAll("SHOW INDEX FROM custom_pages WHERE Key_name = 'idx_show_in_app'");
    
    if (!empty($indexes)) {
        echo "✅ Index 'idx_show_in_app' already exists!\n";
    } else {
        echo "⏳ Adding index 'idx_show_in_app'...\n";
        
        // Add index
        $db->query("ALTER TABLE custom_pages ADD INDEX idx_show_in_app (show_in_app)");
        
        echo "✅ Index 'idx_show_in_app' added successfully!\n";
    }
    
    // Update common pages to show in app
    echo "\n⏳ Updating common pages to show in app...\n";
    
    $db->query("
        UPDATE custom_pages 
        SET show_in_app = TRUE 
        WHERE slug IN ('about-us', 'privacy-policy', 'terms-and-conditions', 'terms-conditions', 'contact-us', 'disclaimer', 'cookie-policy')
        AND status = 'published'
    ");
    
    echo "✅ Common pages updated!\n";
    
    // Show current structure
    echo "\n📊 Current Table Structure:\n";
    $structure = $db->fetchAll("DESCRIBE custom_pages");
    foreach ($structure as $col) {
        echo sprintf("  %-20s %-15s %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
    
    // Show pages status
    echo "\n📄 Pages Status:\n";
    $pages = $db->fetchAll("SELECT id, title, slug, show_in_footer, show_in_app, status FROM custom_pages ORDER BY order_id ASC");
    
    echo sprintf("  %-5s %-30s %-25s %-12s %-10s %s\n", "ID", "Title", "Slug", "In Footer", "In App", "Status");
    echo str_repeat("-", 100) . "\n";
    
    foreach ($pages as $page) {
        echo sprintf(
            "  %-5s %-30s %-25s %-12s %-10s %s\n",
            $page['id'],
            substr($page['title'], 0, 28),
            substr($page['slug'], 0, 23),
            $page['show_in_footer'] ? '✅ Yes' : '❌ No',
            $page['show_in_app'] ? '✅ Yes' : '❌ No',
            $page['status']
        );
    }
    
    echo "\n✅ ✅ ✅ ALL DONE! ✅ ✅ ✅\n";
    echo "\n🎯 Next Steps:\n";
    echo "1. Go to: http://localhost/admin/pages.php\n";
    echo "2. You'll see the new 'Show in App' column with blue badges\n";
    echo "3. Click badges to toggle visibility for website footer and mobile app\n";
    echo "4. You can delete this file (add-show-in-app-column.php) after running it\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n📝 Manual SQL (run in phpMyAdmin):\n";
    echo "ALTER TABLE custom_pages ADD COLUMN show_in_app BOOLEAN DEFAULT FALSE AFTER show_in_footer;\n";
    echo "ALTER TABLE custom_pages ADD INDEX idx_show_in_app (show_in_app);\n";
}

echo "</pre>";
echo "<br><br>";
echo "<a href='pages.php' style='display:inline-block; padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Go to Pages Management</a>";
?>
