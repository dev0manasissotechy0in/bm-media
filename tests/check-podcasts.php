<?php
require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "=== CHECKING PODCASTS TABLE ===\n";
$podcasts = $db->fetchAll("SELECT * FROM podcasts LIMIT 10");
echo "Count: " . count($podcasts) . "\n";
foreach ($podcasts as $podcast) {
    echo "ID: {$podcast['id']}, Title: {$podcast['title']}, Type: {$podcast['type']}, Audio: {$podcast['audio_file']}, Status: {$podcast['status']}\n";
}

echo "\n=== CHECKING PODCAST_EPISODES TABLE ===\n";
$episodes = $db->fetchAll("SELECT * FROM podcast_episodes LIMIT 10");
echo "Count: " . count($episodes) . "\n";
foreach ($episodes as $episode) {
    echo "ID: {$episode['id']}, Title: {$episode['title']}, Series ID: {$episode['series_id']}, Audio: {$episode['audio_file']}, Status: {$episode['status']}\n";
}

echo "\n=== CHECKING ARTICLES WITH AUDIO CONTENT_TYPE ===\n";
$articles = $db->fetchAll("SELECT id, title, content_type, media_audio_file, media_audio_url, status FROM articles WHERE content_type = 'audio'");
echo "Count: " . count($articles) . "\n";
foreach ($articles as $article) {
    echo "ID: {$article['id']}, Title: {$article['title']}, Audio File: {$article['media_audio_file']}, Audio URL: {$article['media_audio_url']}, Status: {$article['status']}\n";
}
