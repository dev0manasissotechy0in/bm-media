<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== FIXING REEL VIDEO URLS ===\n\n";

// Get all reels
$reels = $db->fetchAll("SELECT id, title, thumbnail, media_reel_url, media_reel_file FROM articles WHERE content_type = 'reel'");

foreach ($reels as $reel) {
    echo "Article {$reel['id']}: {$reel['title']}\n";
    echo "Current thumbnail: {$reel['thumbnail']}\n";
    echo "Current reel_url: " . ($reel['media_reel_url'] ?: 'EMPTY') . "\n";
    echo "Current reel_file: " . ($reel['media_reel_file'] ?: 'EMPTY') . "\n";
    
    // Check if thumbnail is a video file
    $ext = strtolower(pathinfo($reel['thumbnail'], PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm', 'mov'])) {
        echo "→ Thumbnail IS a video file\n";
        
        // Update media_reel_file to match thumbnail
        $db->query("UPDATE articles SET media_reel_file = ? WHERE id = ?", [$reel['thumbnail'], $reel['id']]);
        echo "→ Updated media_reel_file to: {$reel['thumbnail']}\n";
    } else {
        echo "→ Thumbnail is an image\n";
        
        // Try to find a video file for this reel
        $baseFilename = pathinfo($reel['thumbnail'], PATHINFO_FILENAME);
        $videoFile = 'reel_' . $baseFilename . '.mp4';
        $videoPath = __DIR__ . '/uploads/articles/' . $videoFile;
        
        if (file_exists($videoPath)) {
            echo "→ Found video file: {$videoFile}\n";
            $db->query("UPDATE articles SET media_reel_file = ? WHERE id = ?", [$videoFile, $reel['id']]);
            echo "→ Updated media_reel_file\n";
        } else {
            echo "→ No video file found\n";
        }
    }
    
    echo "---\n\n";
}

echo "=== VERIFICATION ===\n";
$updated = $db->fetchAll("SELECT id, title, thumbnail, media_reel_url, media_reel_file FROM articles WHERE content_type = 'reel'");
foreach ($updated as $reel) {
    echo "ID {$reel['id']}: Reel File = " . ($reel['media_reel_file'] ?: 'EMPTY') . "\n";
}
?>
