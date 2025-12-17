<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== CHECKING ALL REEL ARTICLES ===\n\n";

$reels = $db->fetchAll("SELECT id, title, thumbnail, media_video_url, media_video_file FROM articles WHERE content_type = 'reel'");

foreach ($reels as $reel) {
    echo "Article ID: {$reel['id']}\n";
    echo "Title: {$reel['title']}\n";
    echo "Thumbnail: {$reel['thumbnail']}\n";
    echo "Video URL: " . ($reel['media_video_url'] ?: 'EMPTY') . "\n";
    echo "Video File: " . ($reel['media_video_file'] ?: 'EMPTY') . "\n";
    
    // Check if thumbnail file exists
    if ($reel['thumbnail']) {
        $thumbPath = __DIR__ . '/uploads/articles/' . $reel['thumbnail'];
        echo "Thumbnail exists: " . (file_exists($thumbPath) ? 'YES' : 'NO') . "\n";
    }
    
    // Check if video file exists
    if ($reel['media_video_file']) {
        $videoPath = __DIR__ . '/uploads/articles/' . $reel['media_video_file'];
        echo "Video file exists: " . (file_exists($videoPath) ? 'YES' : 'NO') . "\n";
    }
    
    echo "---\n\n";
}

// List all video files in uploads
echo "=== AVAILABLE VIDEO FILES ===\n";
$videoFiles = glob(__DIR__ . '/uploads/articles/*.mp4');
foreach ($videoFiles as $file) {
    echo basename($file) . "\n";
}
?>
