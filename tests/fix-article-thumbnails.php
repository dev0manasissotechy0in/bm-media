<?php
/**
 * Fix Article Thumbnails
 * This script updates article thumbnails to match actual files in uploads/articles
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

// Get all articles with thumbnails
$articles = $db->fetchAll("SELECT id, title, thumbnail, content_type FROM articles WHERE thumbnail IS NOT NULL AND thumbnail != ''");

echo "Found " . count($articles) . " articles with thumbnails\n\n";

$fixed = 0;
$notFound = 0;

foreach ($articles as $article) {
    $thumbnail = $article['thumbnail'];
    
    // Skip if already a full URL
    if (preg_match('/^https?:\/\//', $thumbnail)) {
        echo "Article {$article['id']}: Skipping URL - {$thumbnail}\n";
        continue;
    }
    
    // Remove any existing paths
    $filename = basename($thumbnail);
    
    // Check if file exists
    $fullPath = __DIR__ . '/uploads/articles/' . $filename;
    
    if (file_exists($fullPath)) {
        echo "Article {$article['id']}: OK - {$filename}\n";
        $fixed++;
    } else {
        echo "Article {$article['id']}: NOT FOUND - {$filename}\n";
        
        // Try to find a similar file (with reel_ prefix for reels)
        if ($article['content_type'] === 'video') {
            $reelFilename = 'reel_' . $filename;
            $reelPath = __DIR__ . '/uploads/articles/' . $reelFilename;
            
            // Also try changing extension to .mp4
            $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
            $videoFilename = $baseFilename . '.mp4';
            $videoPath = __DIR__ . '/uploads/articles/' . $videoFilename;
            
            $reelVideoFilename = 'reel_' . $videoFilename;
            $reelVideoPath = __DIR__ . '/uploads/articles/' . $reelVideoFilename;
            
            if (file_exists($reelPath)) {
                echo "  -> Found reel file: {$reelFilename}\n";
                $db->query("UPDATE articles SET thumbnail = ? WHERE id = ?", [$reelFilename, $article['id']]);
                $fixed++;
            } elseif (file_exists($videoPath)) {
                echo "  -> Found video file: {$videoFilename}\n";
                $db->query("UPDATE articles SET thumbnail = ? WHERE id = ?", [$videoFilename, $article['id']]);
                $fixed++;
            } elseif (file_exists($reelVideoPath)) {
                echo "  -> Found reel video file: {$reelVideoFilename}\n";
                $db->query("UPDATE articles SET thumbnail = ? WHERE id = ?", [$reelVideoFilename, $article['id']]);
                $fixed++;
            } else {
                $notFound++;
            }
        } else {
            // For non-video content, try to find any matching file
            $pattern = __DIR__ . '/uploads/articles/' . pathinfo($filename, PATHINFO_FILENAME) . '.*';
            $matches = glob($pattern);
            
            if (!empty($matches)) {
                $newFilename = basename($matches[0]);
                echo "  -> Found alternative: {$newFilename}\n";
                $db->query("UPDATE articles SET thumbnail = ? WHERE id = ?", [$newFilename, $article['id']]);
                $fixed++;
            } else {
                $notFound++;
            }
        }
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total articles: " . count($articles) . "\n";
echo "Fixed/OK: {$fixed}\n";
echo "Not found: {$notFound}\n";
?>
