<?php
require_once 'config/config.php';
header('Content-Type: application/json');

$response = file_get_contents('http://localhost/api/articles/videos.php');
$data = json_decode($response, true);

echo "Videos API Test\n";
echo "===============\n\n";
echo "Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
echo "Count: " . $data['count'] . "\n\n";

if (!empty($data['articles'])) {
    echo "Articles:\n";
    foreach ($data['articles'] as $article) {
        echo "\nID: " . $article['id'] . "\n";
        echo "Title: " . $article['title'] . "\n";
        echo "Content Type: " . $article['content_type'] . "\n";
        echo "Image URL: " . $article['image_url'] . "\n";
        echo "Video URL: " . $article['video_url'] . "\n";
        echo "---\n";
    }
}

echo "\n\nPodcasts API Test\n";
echo "=================\n\n";

$response2 = file_get_contents('http://localhost/api/articles/podcasts.php');
$data2 = json_decode($response2, true);

echo "Success: " . ($data2['success'] ? 'YES' : 'NO') . "\n";
echo "Count: " . $data2['count'] . "\n\n";

if (!empty($data2['articles'])) {
    echo "Articles:\n";
    foreach ($data2['articles'] as $article) {
        echo "\nID: " . $article['id'] . "\n";
        echo "Title: " . $article['title'] . "\n";
        echo "Content Type: " . $article['content_type'] . "\n";
        echo "Image URL: " . ($article['image_url'] ?? 'NULL') . "\n";
        echo "Audio URL: " . ($article['audio_url'] ?? 'NULL') . "\n";
        echo "---\n";
    }
}
?>
