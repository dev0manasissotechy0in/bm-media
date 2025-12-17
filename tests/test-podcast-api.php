<?php
// Test the podcasts API
$url = 'https://brackoddmedia.com/api/articles/podcasts.php?limit=10';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "=== PODCASTS API TEST ===\n";
echo "Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
echo "Count: " . $data['count'] . "\n";
echo "Total: " . $data['total'] . "\n\n";

if (!empty($data['articles'])) {
    echo "=== PODCAST DETAILS ===\n";
    foreach ($data['articles'] as $podcast) {
        echo "ID: {$podcast['id']}\n";
        echo "Title: {$podcast['title']}\n";
        echo "Audio URL: " . ($podcast['audio_url'] ?: 'EMPTY') . "\n";
        echo "Thumbnail: " . ($podcast['image_url'] ?: 'EMPTY') . "\n";
        echo "Duration: {$podcast['duration']} seconds\n";
        echo "Is Series: " . ($podcast['is_series'] ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
} else {
    echo "No podcasts returned\n";
}
?>
