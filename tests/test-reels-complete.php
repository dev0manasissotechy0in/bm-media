<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

echo "=== TESTING REELS FUNCTIONALITY ===\n\n";

// Test 1: Check reels API response
echo "1. Testing Reels API Endpoint:\n";
echo "   URL: http://192.168.1.3/api/articles/reels.php\n";
$response = file_get_contents('http://192.168.1.3/api/articles/reels.php');
$data = json_decode($response, true);

if ($data['success']) {
    echo "   ✓ API Response: SUCCESS\n";
    echo "   ✓ Reels Count: " . $data['count'] . "\n\n";
    
    if ($data['count'] > 0) {
        $reel = $data['articles'][0];
        echo "2. First Reel Details:\n";
        echo "   Title: " . $reel['title'] . "\n";
        echo "   ID: " . $reel['id'] . "\n";
        echo "   Slug: " . $reel['slug'] . "\n";
        echo "   Share URL: " . $reel['share_url'] . "\n";
        echo "   Article URL: " . $reel['article_url'] . "\n";
        echo "   Video URL: " . (strlen($reel['video_url']) > 80 ? substr($reel['video_url'], 0, 80) . '...' : $reel['video_url']) . "\n";
        echo "   Has Article: " . ($reel['has_article'] ? 'YES' : 'NO') . "\n";
        echo "   Content Type: " . $reel['content_type'] . "\n\n";
        
        // Test 3: Verify all required fields
        echo "3. Required Fields Check:\n";
        $requiredFields = [
            'id', 'title', 'slug', 'share_url', 'article_url', 
            'video_url', 'has_article', 'content_type', 'category'
        ];
        
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($reel[$field])) {
                $missingFields[] = $field;
            } else {
                echo "   ✓ {$field}\n";
            }
        }
        
        if (empty($missingFields)) {
            echo "\n   ✓ All required fields present!\n\n";
        } else {
            echo "\n   ✗ Missing fields: " . implode(', ', $missingFields) . "\n\n";
        }
        
        // Test 4: Check URL formats
        echo "4. URL Format Validation:\n";
        if (strpos($reel['share_url'], '/reel/') !== false) {
            echo "   ✓ Share URL format: /reel/{id}\n";
        } else {
            echo "   ✗ Share URL format incorrect\n";
        }
        
        if (strpos($reel['article_url'], '/article/') !== false) {
            echo "   ✓ Article URL format: /article/{slug}\n";
        } else {
            echo "   ✗ Article URL format incorrect\n";
        }
        
        echo "\n";
        
        // Test 5: Test reel page access
        echo "5. Testing Reel Page Access:\n";
        $reelUrl = 'http://192.168.1.3/reel/' . $reel['id'];
        echo "   URL: {$reelUrl}\n";
        
        $headers = @get_headers($reelUrl);
        if ($headers && strpos($headers[0], '200') !== false) {
            echo "   ✓ Reel page accessible\n";
        } else {
            echo "   ✗ Reel page not accessible\n";
        }
        
        echo "\n";
    }
} else {
    echo "   ✗ API Response: FAILED\n";
    echo "   Error: " . ($data['message'] ?? 'Unknown error') . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
echo "\nNext Steps:\n";
echo "1. Test in Flutter app: Run the app and navigate to Reels tab\n";
echo "2. Verify video playback works for reels\n";
echo "3. Check 'Read Article' button appears for reels with content\n";
echo "4. Test share functionality shares reel URL correctly\n";
