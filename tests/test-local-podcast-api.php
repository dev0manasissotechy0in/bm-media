<?php
// Test podcasts API directly
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'api/helpers.php';

$db = Database::getInstance();

$limit = 10;
$page = 1;
$offset = 0;

$podcasts = $db->fetchAll("
    SELECT * FROM podcasts WHERE status = 'published' ORDER BY created_at DESC LIMIT ? OFFSET ?
", [$limit, $offset]);

$totalResult = $db->fetchOne("SELECT COUNT(*) as total FROM podcasts WHERE status = 'published'");
$total = (int)$totalResult['total'];

$output = json_encode([
    'success' => true,
    'count' => count($podcasts),
    'total' => $total,
    'podcasts' => $podcasts
]);

$data = json_decode($output, true);

echo "=== LOCAL API TEST ===\n";
echo "Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
echo "Count: " . $data['count'] . "\n";
echo "Total: " . $data['total'] . "\n\n";

if (!empty($data['articles'])) {
    foreach ($data['articles'] as $podcast) {
        echo "Podcast: {$podcast['title']}\n";
        echo "Audio URL: " . ($podcast['audio_url'] ?: 'EMPTY') . "\n";
        echo "Duration: {$podcast['duration']}s\n\n";
    }
}
?>
