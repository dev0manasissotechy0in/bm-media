<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== BREAKING NEWS ARTICLES ===\n";
$breaking = $db->fetchAll("SELECT id, title, is_breaking, status FROM articles WHERE is_breaking = 1");
echo "Count: " . count($breaking) . "\n";
foreach ($breaking as $article) {
    echo "ID: {$article['id']}, Title: {$article['title']}, Status: {$article['status']}\n";
}

echo "\n=== VIDEO/REEL ARTICLES ===\n";
$reels = $db->fetchAll("SELECT id, title, content_type, thumbnail, status FROM articles WHERE content_type = 'video' LIMIT 10");
echo "Count: " . count($reels) . "\n";
foreach ($reels as $article) {
    echo "ID: {$article['id']}, Title: {$article['title']}, Thumbnail: {$article['thumbnail']}, Status: {$article['status']}\n";
}

echo "\n=== AUDIO/PODCAST ARTICLES ===\n";
$podcasts = $db->fetchAll("SELECT id, title, content_type, status FROM articles WHERE content_type = 'audio' LIMIT 10");
echo "Count: " . count($podcasts) . "\n";
foreach ($podcasts as $article) {
    echo "ID: {$article['id']}, Title: {$article['title']}, Status: {$article['status']}\n";
}
?>
