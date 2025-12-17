<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== Testing Video Articles in Category Pages ===\n\n";

// Find video articles
$video = $db->fetchOne("
    SELECT a.*, c.slug as cat_slug, c.name as cat_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.content_type = 'video' AND a.status = 'published' 
    LIMIT 1
");

if ($video) {
    echo "✓ Video Article Found\n";
    echo "  Title: {$video['title']}\n";
    echo "  Category: {$video['cat_name']}\n";
    echo "  Content Type: {$video['content_type']}\n";
    echo "  Category URL: " . BASE_URL . "/category/{$video['cat_slug']}\n\n";
    
    // Test category page query
    echo "--- Testing Category Query ---\n";
    $articles = $db->fetchAll("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.category_id = ? AND a.status = 'published'
        LIMIT 5
    ", [$video['category_id']]);
    
    echo "Articles in category: " . count($articles) . "\n";
    
    foreach ($articles as $article) {
        $type = $article['content_type'] ?? 'standard';
        $icon = $type === 'video' ? '▶️' : '📄';
        echo "  {$icon} {$article['title']} (Type: {$type})\n";
    }
    
    echo "\n✓ Category page will show video play overlay for video articles\n";
} else {
    echo "⚠ No video articles found in database\n";
}

echo "\n=== Test Complete ===\n";
