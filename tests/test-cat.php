<?php
require_once 'c:/xampp/htdocs/config/config.php';
require_once 'c:/xampp/htdocs/includes/Database.php';

$db = Database::getInstance();

// Get the live article's category
$liveArticle = $db->fetchOne("SELECT id, title, category_id FROM articles WHERE is_live = 1 LIMIT 1");

if ($liveArticle) {
    echo "Live Article: " . $liveArticle['title'] . "\n";
    echo "Category ID: " . $liveArticle['category_id'] . "\n\n";
    
    // Now test the category API
    $url = "http://localhost/api/articles/by-category.php?category_id=" . $liveArticle['category_id'];
    $data = json_decode(file_get_contents($url), true);
    
    if ($data && $data['success']) {
        echo "Category API returned " . count($data['articles']) . " articles\n";
        foreach ($data['articles'] as $article) {
            if ($article['is_live']) {
                echo "\n✓ Found live article: " . $article['title'] . "\n";
                echo "  Has timeline_updates: " . (isset($article['timeline_updates']) ? 'YES' : 'NO') . "\n";
                echo "  Timeline updates count: " . count($article['timeline_updates']) . "\n";
                break;
            }
        }
    } else {
        echo "Error: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "No live article found\n";
}
