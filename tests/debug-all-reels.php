<?php
require_once 'config/config.php';
$db = Database::getInstance();

// Get ALL reels to see what data we have
$all_reels = [];

$standalone_reels_raw = $db->fetchAll("
    SELECT 
        r.id,
        r.title,
        r.video_file,
        'standalone' as reel_type
    FROM reels r
    WHERE r.status = 'active'
    ORDER BY r.created_at DESC
    LIMIT 10
");

$standalone_reels = array_map(function($reel) {
    $reel['slug'] = generateSlug($reel['title']);
    return $reel;
}, $standalone_reels_raw);

$reel_articles = $db->fetchAll("
    SELECT 
        a.id,
        a.title,
        a.slug,
        a.media_reel_file as video_file,
        'article' as reel_type
    FROM articles a
    WHERE a.content_type = 'reel' 
        AND a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT 10
");

$all_reels = array_merge($standalone_reels, $reel_articles);

echo "<h2>All Reels Debug:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Title</th><th>Slug</th><th>Type</th><th>Video File</th><th>Generated URL</th></tr>";

foreach ($all_reels as $reel) {
    $url = '';
    if ($reel['reel_type'] == 'article' && !empty($reel['slug'])) {
        $url = '/reel/' . $reel['slug'] . '/' . $reel['id'];
    } elseif ($reel['reel_type'] == 'standalone' && !empty($reel['slug'])) {
        $url = '/reel/' . $reel['slug'] . '/' . $reel['id'];
    }
    
    echo "<tr>";
    echo "<td>{$reel['id']}</td>";
    echo "<td>" . htmlspecialchars($reel['title']) . "</td>";
    echo "<td>" . htmlspecialchars($reel['slug'] ?? 'N/A') . "</td>";
    echo "<td>{$reel['reel_type']}</td>";
    echo "<td>" . htmlspecialchars($reel['video_file'] ?? 'N/A') . "</td>";
    echo "<td>$url</td>";
    echo "</tr>";
}

echo "</table>";
?>
