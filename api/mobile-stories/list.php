<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    // Get active stories that haven't expired (24 hours check)
    $stories = $db->fetchAll("SELECT 
                m.*,
                c.name as category_name,
                c.slug as category_slug,
                CASE 
                    WHEN m.expires_at IS NOT NULL AND m.expires_at < NOW() THEN 1
                    WHEN m.created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1
                    ELSE 0
                END as is_expired
              FROM mobile_stories m
              LEFT JOIN categories c ON m.category_id = c.id
              WHERE m.status = 'active'
              AND (
                  (m.expires_at IS NOT NULL AND m.expires_at > NOW())
                  OR (m.expires_at IS NULL AND m.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR))
              )
              ORDER BY m.created_at DESC");
    
    // Format image and video URLs
    foreach ($stories as &$story) {
        if (!empty($story['image_url']) && !preg_match('/^https?:\/\//', $story['image_url'])) {
            $story['image_url'] = BASE_URL . '/' . ltrim($story['image_url'], '/');
        }
        if (!empty($story['video_url']) && !preg_match('/^https?:\/\//', $story['video_url'])) {
            $story['video_url'] = BASE_URL . '/' . ltrim($story['video_url'], '/');
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($stories),
        'stories' => $stories
    ], JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching stories: ' . $e->getMessage()
    ]);
}
