<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Find articles with this wrong thumbnail
$wrongThumbnails = [
    '1764843982_693161ce8756a.png',
    '1764850032_693179706086e.webp'
];

foreach ($wrongThumbnails as $thumb) {
    echo "=== SEARCHING FOR: $thumb ===\n";
    $articles = $db->fetchAll("SELECT id, title, thumbnail, content_type, status FROM articles WHERE thumbnail LIKE ?", ["%$thumb%"]);
    
    if (empty($articles)) {
        echo "Not found in articles table\n";
    } else {
        foreach ($articles as $article) {
            echo "Article ID: {$article['id']}\n";
            echo "Title: {$article['title']}\n";
            echo "Thumbnail: {$article['thumbnail']}\n";
            echo "Content Type: {$article['content_type']}\n";
            echo "Status: {$article['status']}\n\n";
        }
    }
}

// Also check if these patterns exist in any column
echo "\n=== CHECKING ALL ARTICLES WITH PROBLEMATIC THUMBNAILS ===\n";
$allArticles = $db->fetchAll("SELECT id, title, thumbnail FROM articles WHERE thumbnail LIKE '%1764843982%' OR thumbnail LIKE '%1764850032%'");
foreach ($allArticles as $article) {
    echo "ID: {$article['id']}, Thumbnail: {$article['thumbnail']}\n";
}
?>
