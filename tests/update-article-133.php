<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== CHECKING ARTICLE 133 ===\n";
$article = $db->fetchOne('SELECT id, title, thumbnail, content_type, status FROM articles WHERE id = 133');

if ($article) {
    echo "ID: {$article['id']}\n";
    echo "Title: {$article['title']}\n";
    echo "Thumbnail: {$article['thumbnail']}\n";
    echo "Content Type: {$article['content_type']}\n";
    echo "Status: {$article['status']}\n";
    
    // Check if file exists
    $thumbnailPath = __DIR__ . '/uploads/articles/' . $article['thumbnail'];
    echo "\nFile check: " . $thumbnailPath . "\n";
    echo "Exists: " . (file_exists($thumbnailPath) ? 'YES' : 'NO') . "\n";
    
    // Update the thumbnail to the correct reel file
    echo "\n=== UPDATING THUMBNAIL ===\n";
    $db->query("UPDATE articles SET thumbnail = ? WHERE id = ?", ['reel_1764843982_693161ce87994.mp4', 133]);
    echo "Updated!\n";
    
    // Verify update
    $updated = $db->fetchOne('SELECT thumbnail FROM articles WHERE id = 133');
    echo "New thumbnail: {$updated['thumbnail']}\n";
} else {
    echo "Article 133 not found!\n";
}
?>
