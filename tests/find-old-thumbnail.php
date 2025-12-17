<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== CHECKING ALL ARTICLES WITH OLD FILENAME ===\n";
$oldFilename = '1764843982_693161ce8756a.png';

// Check articles table
$articles = $db->fetchAll("SELECT id, title, thumbnail, content_type FROM articles WHERE thumbnail LIKE '%1764843982_693161ce%'");
echo "Found " . count($articles) . " articles with pattern '1764843982_693161ce'\n\n";
foreach ($articles as $article) {
    echo "ID: {$article['id']}, Title: {$article['title']}, Thumbnail: {$article['thumbnail']}, Type: {$article['content_type']}\n";
}

echo "\n=== CHECKING BANNER/EXPLORE TAB ===\n";
$banners = $db->fetchAll("SELECT id, title, thumbnail FROM banner_articles LIMIT 10");
echo "Banner articles count: " . count($banners) . "\n";
foreach ($banners as $banner) {
    echo "ID: {$banner['id']}, Thumbnail: {$banner['thumbnail']}\n";
    if (strpos($banner['thumbnail'], '1764843982_693161ce') !== false) {
        echo "  ⚠️ FOUND OLD FILENAME!\n";
    }
}

echo "\n=== CHECKING FEATURED/BREAKING ===\n";
$featured = $db->fetchAll("SELECT id, title, thumbnail FROM articles WHERE (is_featured = 1 OR is_breaking = 1) AND thumbnail LIKE '%1764843982%'");
echo "Featured/Breaking with old pattern: " . count($featured) . "\n";
foreach ($featured as $article) {
    echo "ID: {$article['id']}, Thumbnail: {$article['thumbnail']}\n";
}
?>
