<?php
/**
 * Test API Responses for Articles
 * Verify what the APIs are actually returning
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "<h1>API Response Test</h1>";

// Test banner.php (Explore tab)
echo "<h2>Banner API Response</h2>";
$bannerUrl = BASE_URL . '/api/articles/banner.php?limit=5';
echo "<p>URL: <a href='$bannerUrl' target='_blank'>$bannerUrl</a></p>";
$bannerData = file_get_contents($bannerUrl);
$banner = json_decode($bannerData, true);
if ($banner && isset($banner['articles'])) {
    foreach ($banner['articles'] as $article) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
        echo "<strong>ID: {$article['id']}</strong> - {$article['title']}<br>";
        echo "Content Type: {$article['content_type']}<br>";
        echo "Image URL: {$article['image_url']}<br>";
        echo "Is Live: " . ($article['is_live'] ? 'Yes' : 'No') . "<br>";
        echo "Is Breaking: " . ($article['is_breaking'] ? 'Yes' : 'No') . "<br>";
        echo "</div>";
    }
}

// Test breaking.php
echo "<h2>Breaking News API Response</h2>";
$breakingUrl = BASE_URL . '/api/articles/breaking.php';
echo "<p>URL: <a href='$breakingUrl' target='_blank'>$breakingUrl</a></p>";
$breakingData = file_get_contents($breakingUrl);
$breaking = json_decode($breakingData, true);
if ($breaking && isset($breaking['articles'])) {
    foreach ($breaking['articles'] as $article) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
        echo "<strong>ID: {$article['id']}</strong> - {$article['title']}<br>";
        echo "Image URL: {$article['image_url']}<br>";
        echo "</div>";
    }
}

// Test reels.php
echo "<h2>Reels API Response</h2>";
$reelsUrl = BASE_URL . '/api/articles/reels.php';
echo "<p>URL: <a href='$reelsUrl' target='_blank'>$reelsUrl</a></p>";
$reelsData = file_get_contents($reelsUrl);
$reels = json_decode($reelsData, true);
if ($reels && isset($reels['articles'])) {
    echo "<p>Total Reels: " . count($reels['articles']) . "</p>";
    foreach ($reels['articles'] as $article) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
        echo "<strong>ID: {$article['id']}</strong> - {$article['title']}<br>";
        echo "Video URL: {$article['video_url']}<br>";
        echo "Image URL: {$article['image_url']}<br>";
        echo "</div>";
    }
} else {
    echo "<pre>" . htmlspecialchars($reelsData) . "</pre>";
}

// Test videos.php
echo "<h2>Videos API Response</h2>";
$videosUrl = BASE_URL . '/api/articles/videos.php';
echo "<p>URL: <a href='$videosUrl' target='_blank'>$videosUrl</a></p>";
$videosData = file_get_contents($videosUrl);
$videos = json_decode($videosData, true);
if ($videos && isset($videos['articles'])) {
    echo "<p>Total Videos: " . count($videos['articles']) . "</p>";
    foreach ($videos['articles'] as $article) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
        echo "<strong>ID: {$article['id']}</strong> - {$article['title']}<br>";
        echo "Video URL: {$article['video_url']}<br>";
        echo "</div>";
    }
}

// Test podcasts.php
echo "<h2>Podcasts API Response</h2>";
$podcastsUrl = BASE_URL . '/api/articles/podcasts.php';
echo "<p>URL: <a href='$podcastsUrl' target='_blank'>$podcastsUrl</a></p>";
$podcastsData = file_get_contents($podcastsUrl);
$podcasts = json_decode($podcastsData, true);
if ($podcasts && isset($podcasts['articles'])) {
    echo "<p>Total Podcasts: " . count($podcasts['articles']) . "</p>";
    foreach ($podcasts['articles'] as $article) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>";
        echo "<strong>ID: {$article['id']}</strong> - {$article['title']}<br>";
        echo "Audio URL: {$article['audio_url']}<br>";
        echo "Image URL: {$article['image_url']}<br>";
        echo "</div>";
    }
}

// Direct database check
echo "<h2>Direct Database Check</h2>";
$articles = $db->fetchAll("SELECT id, title, thumbnail, content_type, is_live, is_breaking FROM articles WHERE id IN (128, 133, 134, 125) ORDER BY id");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Thumbnail</th><th>Type</th><th>Live</th><th>Breaking</th></tr>";
foreach ($articles as $article) {
    echo "<tr>";
    echo "<td>{$article['id']}</td>";
    echo "<td>{$article['title']}</td>";
    echo "<td>{$article['thumbnail']}</td>";
    echo "<td>{$article['content_type']}</td>";
    echo "<td>{$article['is_live']}</td>";
    echo "<td>{$article['is_breaking']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
