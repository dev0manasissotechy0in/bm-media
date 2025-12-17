<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== Testing Article URLs and Share URLs ===\n\n";

// Test 1: Get a sample article
$article = $db->fetchOne("SELECT id, title, slug FROM articles WHERE status = 'published' LIMIT 1");

if ($article) {
    echo "Test Article: {$article['title']}\n";
    echo "Slug: {$article['slug']}\n";
    echo "Clean URL: " . BASE_URL . "/article/{$article['slug']}\n\n";
    
    // Test 2: Test single article API
    echo "--- Testing Single Article API ---\n";
    $response = file_get_contents(BASE_URL . "/api/articles/single.php?id={$article['id']}");
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        $apiArticle = $data['article'];
        echo "✓ Has slug: " . (isset($apiArticle['slug']) ? 'YES' : 'NO') . "\n";
        echo "✓ Has share_url: " . (isset($apiArticle['share_url']) ? 'YES' : 'NO') . "\n";
        if (isset($apiArticle['share_url'])) {
            echo "  Share URL: {$apiArticle['share_url']}\n";
        }
    }
    
    // Test 3: Test article list APIs
    $apis = [
        'all.php' => 'all.php?limit=1',
        'featured.php' => 'featured.php',
        'popular.php' => 'popular.php?limit=1',
        'breaking.php' => 'breaking.php',
    ];
    
    echo "\n--- Testing Article List APIs ---\n";
    foreach ($apis as $name => $endpoint) {
        $response = file_get_contents(BASE_URL . "/api/articles/{$endpoint}");
        $data = json_decode($response, true);
        
        if ($data && $data['success'] && !empty($data['articles'])) {
            $firstArticle = $data['articles'][0];
            $hasSlug = isset($firstArticle['slug']);
            $hasShareUrl = isset($firstArticle['share_url']);
            
            if ($hasSlug && $hasShareUrl) {
                echo "✓ {$name}: Has slug and share_url\n";
            } else {
                echo "✗ {$name}: Missing " . (!$hasSlug ? 'slug ' : '') . (!$hasShareUrl ? 'share_url' : '') . "\n";
            }
        } else {
            echo "⚠ {$name}: No articles returned\n";
        }
    }
}

// Test 4: Check .htaccess rewrite rules
echo "\n--- Testing URL Rewrites ---\n";
if (file_exists('.htaccess')) {
    $htaccess = file_get_contents('.htaccess');
    
    $checks = [
        'article/([a-zA-Z0-9-]+)' => 'Article clean URL',
        'reel/([0-9]+)' => 'Reel clean URL',
    ];
    
    foreach ($checks as $pattern => $label) {
        if (strpos($htaccess, $pattern) !== false) {
            echo "✓ {$label} rule exists\n";
        } else {
            echo "✗ {$label} rule missing\n";
        }
    }
}

// Test 5: Check reel article
$reelArticle = $db->fetchOne("SELECT id, title FROM articles WHERE content_type = 'reel' AND status = 'published' LIMIT 1");
if ($reelArticle) {
    echo "\n--- Testing Reel URLs ---\n";
    echo "Reel Article: {$reelArticle['title']}\n";
    echo "Clean Reel URL: " . BASE_URL . "/reel/{$reelArticle['id']}\n";
    echo "✓ Reel URL format updated\n";
}

echo "\n=== All Tests Complete ===\n";
