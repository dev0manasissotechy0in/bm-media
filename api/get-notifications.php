<?php
/**
 * API - Get Article Notifications
 * Returns list of article notifications for mobile app
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/config.php';
require_once '../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$db = Database::getInstance();

try {
    // Get pagination parameters
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 50;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
    
    // Fetch recent article notifications from notification_logs
    $notifications = $db->fetchAll("
        SELECT 
            nl.id,
            nl.title,
            nl.body,
            nl.type,
            nl.topic,
            nl.created_at,
            nl.success
        FROM notification_logs nl
        WHERE nl.success = 1 
        AND nl.type IN ('article', 'breaking', 'live_news', 'story')
        ORDER BY nl.created_at DESC
        LIMIT ? OFFSET ?
    ", [$limit, $offset]);
    
    // Format notifications for app
    $formatted = [];
    foreach ($notifications as $notif) {
        // Try to extract article info from notification
        $articleId = null;
        $articleSlug = null;
        $imageUrl = null;
        $notificationType = $notif['type'];
        
        // Get article details if available
        // Try to find related article by title match or from recent published articles
        if ($notificationType === 'article') {
            $article = $db->fetchOne("
                SELECT id, slug, featured_image, created_at
                FROM articles 
                WHERE title = ? 
                AND status = 'published'
                AND ABS(TIMESTAMPDIFF(SECOND, published_at, ?)) < 300
                ORDER BY created_at DESC
                LIMIT 1
            ", [$notif['body'], $notif['created_at']]);
            
            if ($article) {
                $articleId = $article['id'];
                $articleSlug = $article['slug'];
                $imageUrl = !empty($article['featured_image']) 
                    ? SITE_URL . '/uploads/articles/' . $article['featured_image'] 
                    : null;
            }
        }
        
        $formatted[] = [
            'id' => (string)$notif['id'],
            'title' => $notif['title'],
            'description' => $notif['body'],
            'type' => $notificationType,
            'notification_type' => $notificationType,
            'article_id' => $articleId ? (string)$articleId : null,
            'article_slug' => $articleSlug,
            'route' => $articleSlug ? '/article/' . $articleSlug : null,
            'image' => $imageUrl,
            'date' => $notif['created_at'],
            'timestamp' => strtotime($notif['created_at']) * 1000, // milliseconds for Flutter
            'read' => false // Default to unread, app will track locally
        ];
    }
    
    // Get total count
    $total = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM notification_logs 
        WHERE success = 1 
        AND type IN ('article', 'breaking', 'live_news', 'story')
    ")['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'notifications' => $formatted,
        'total' => (int)$total,
        'limit' => $limit,
        'offset' => $offset,
        'has_more' => ($offset + $limit) < $total
    ]);
    
} catch (Exception $e) {
    error_log('Get Notifications API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch notifications',
        'error' => DEBUG_MODE ? $e->getMessage() : 'Internal server error'
    ]);
}
