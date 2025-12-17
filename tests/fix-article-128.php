<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== FIXING ARTICLE 128 ===\n";

// Since file doesn't exist, use the same reel video or set it to NULL
echo "Option 1: Use same reel video as article 133\n";
echo "Option 2: Set thumbnail to NULL and hide article\n";
echo "Option 3: Mark as draft\n\n";

// Let's use the same reel video for now
$db->query("UPDATE articles SET thumbnail = ? WHERE id = ?", ['reel_1764843982_693161ce87994.mp4', 128]);
echo "Updated article 128 to use: reel_1764843982_693161ce87994.mp4\n";

// Verify
$article = $db->fetchOne('SELECT id, title, thumbnail FROM articles WHERE id = 128');
echo "Verified - Article 128 thumbnail: {$article['thumbnail']}\n";

echo "\n=== ALL REEL ARTICLES ===\n";
$reels = $db->fetchAll("SELECT id, title, thumbnail, content_type FROM articles WHERE content_type = 'reel'");
foreach ($reels as $reel) {
    echo "ID: {$reel['id']}, Title: {$reel['title']}, Thumbnail: {$reel['thumbnail']}\n";
}
?>
