<?php
/**
 * Direct Database Check - What's actually in the database
 */

require_once 'config/config.php';
require_once 'includes/Database.php';

$db = Database::getInstance();

echo "<h1>Database Check - Article Thumbnails</h1>";

// Check critical articles
$articles = $db->fetchAll("
    SELECT id, title, thumbnail, content_type, is_live, is_breaking, 
           media_video_url, media_video_file, media_reel_url, media_reel_file
    FROM articles 
    WHERE id IN (125, 128, 133, 134) 
    ORDER BY id
");

echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
echo "<tr style='background:#f0f0f0;'>";
echo "<th>ID</th><th>Title</th><th>Thumbnail</th><th>Type</th><th>Reel File</th><th>Reel URL</th><th>Live</th><th>Breaking</th>";
echo "</tr>";

foreach ($articles as $article) {
    echo "<tr>";
    echo "<td>{$article['id']}</td>";
    echo "<td>" . substr($article['title'], 0, 30) . "...</td>";
    echo "<td><strong>" . ($article['thumbnail'] ?: 'NULL') . "</strong></td>";
    echo "<td>{$article['content_type']}</td>";
    echo "<td>" . ($article['media_reel_file'] ?: 'NULL') . "</td>";
    echo "<td>" . (substr($article['media_reel_url'] ?: 'NULL', 0, 40)) . "</td>";
    echo "<td>" . ($article['is_live'] ? 'YES' : 'No') . "</td>";
    echo "<td>" . ($article['is_breaking'] ? 'YES' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check if files exist
echo "<h2>File Existence Check</h2>";
$uploadDir = ROOT_PATH . '/uploads/articles/';

foreach ($articles as $article) {
    echo "<div style='border:1px solid #ccc; margin:10px 0; padding:10px;'>";
    echo "<strong>Article {$article['id']}: {$article['title']}</strong><br>";
    
    if ($article['thumbnail']) {
        $thumbPath = $uploadDir . $article['thumbnail'];
        $exists = file_exists($thumbPath);
        echo "Thumbnail: <code>{$article['thumbnail']}</code> - ";
        echo $exists ? "<span style='color:green;'>✓ EXISTS</span>" : "<span style='color:red;'>✗ NOT FOUND</span>";
        if ($exists) {
            echo " (" . number_format(filesize($thumbPath) / 1024 / 1024, 2) . " MB)";
        }
        echo "<br>";
    }
    
    if ($article['media_reel_file']) {
        $reelPath = $uploadDir . $article['media_reel_file'];
        $exists = file_exists($reelPath);
        echo "Reel File: <code>{$article['media_reel_file']}</code> - ";
        echo $exists ? "<span style='color:green;'>✓ EXISTS</span>" : "<span style='color:red;'>✗ NOT FOUND</span>";
        if ($exists) {
            echo " (" . number_format(filesize($reelPath) / 1024 / 1024, 2) . " MB)";
        }
        echo "<br>";
    }
    
    echo "</div>";
}

// Check old filename
echo "<h2>Search for Old Thumbnail</h2>";
$oldThumb = '1764843982_693161ce8756a.png';
$oldArticles = $db->fetchAll("SELECT id, title, thumbnail FROM articles WHERE thumbnail = ?", [$oldThumb]);
if (empty($oldArticles)) {
    echo "<p style='color:green;'><strong>✓ Old thumbnail '$oldThumb' NOT found in database (GOOD!)</strong></p>";
    echo "<p><strong>Conclusion:</strong> The app is using cached data. The database is correct.</p>";
} else {
    echo "<p style='color:red;'><strong>✗ Found old thumbnail in database:</strong></p>";
    foreach ($oldArticles as $a) {
        echo "<p>Article {$a['id']}: {$a['title']}</p>";
    }
}

?>
