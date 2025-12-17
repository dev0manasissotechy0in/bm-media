<?php
// Test all article list APIs for timeline_updates support

$apis = [
    'all.php' => 'http://localhost/api/articles/all.php?limit=10',
    'featured.php' => 'http://localhost/api/articles/featured.php',
    'popular.php' => 'http://localhost/api/articles/popular.php?limit=10',
    'breaking.php' => 'http://localhost/api/articles/breaking.php',
    'by-category.php' => 'http://localhost/api/articles/by-category.php?category_id=1&limit=10',
    'by-tag.php' => 'http://localhost/api/articles/by-tag.php?tag_id=1&limit=10',
];

echo "=== Testing Timeline Updates in All Article List APIs ===\n\n";

foreach ($apis as $name => $url) {
    echo "Testing: $name\n";
    echo str_repeat('-', 60) . "\n";
    
    $data = json_decode(file_get_contents($url), true);
    
    if (!$data || !isset($data['articles'])) {
        echo "❌ No articles returned\n\n";
        continue;
    }
    
    $totalArticles = count($data['articles']);
    $liveArticles = 0;
    $articlesWithTimeline = 0;
    
    foreach ($data['articles'] as $article) {
        // Check if article has required fields
        $hasIsLive = isset($article['is_live']);
        $hasIsBreaking = isset($article['is_breaking']);
        $hasTimelineUpdates = isset($article['timeline_updates']);
        
        if (!$hasIsLive || !$hasIsBreaking || !$hasTimelineUpdates) {
            echo "❌ Missing fields - is_live: " . ($hasIsLive ? 'YES' : 'NO') . 
                 ", is_breaking: " . ($hasIsBreaking ? 'YES' : 'NO') . 
                 ", timeline_updates: " . ($hasTimelineUpdates ? 'YES' : 'NO') . "\n";
            break;
        }
        
        if ($article['is_live']) {
            $liveArticles++;
            echo "✓ Found live article: {$article['title']}\n";
            echo "  Timeline updates count: " . count($article['timeline_updates']) . "\n";
            if (count($article['timeline_updates']) > 0) {
                $articlesWithTimeline++;
                echo "  Latest update: " . substr($article['timeline_updates'][0]['text'], 0, 50) . "...\n";
            }
        }
    }
    
    if ($liveArticles === 0) {
        // Check if all articles have the fields even if not live
        $firstArticle = $data['articles'][0];
        if (isset($firstArticle['is_live']) && isset($firstArticle['timeline_updates'])) {
            echo "✓ All fields present (no live articles in result)\n";
        }
    }
    
    echo "Total articles: $totalArticles\n";
    echo "Live articles: $liveArticles\n";
    echo "Articles with timeline updates: $articlesWithTimeline\n\n";
}

echo "\n=== Test Complete ===\n";
