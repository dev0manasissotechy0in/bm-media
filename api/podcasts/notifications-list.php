<?php
/**
 * API - Get User's Podcast Notifications
 * Returns list of podcasts user has enabled notifications for
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$db = Database::getInstance();

// Check authentication - support both session and token
$user_id = null;

// Check session first (for website)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}
// Check Authorization header (for mobile app)
else {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7);
    }
    
    if ($token) {
        $session = $db->fetchOne("
            SELECT user_id FROM user_sessions 
            WHERE token = ? AND expires_at > NOW()
        ", [$token]);
        
        if ($session) {
            $user_id = $session['user_id'];
        }
    }
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

try {
    // Get all podcasts with notification status
    $notifications = $db->fetchAll("
        SELECT 
            p.id as podcast_id,
            p.title,
            p.thumbnail,
            p.is_series,
            p.total_episodes,
            COALESCE(upn.enabled, 0) as notifications_enabled
        FROM podcasts p
        LEFT JOIN user_podcast_notifications upn 
            ON p.id = upn.podcast_id AND upn.user_id = ?
        WHERE p.status = 'published'
        ORDER BY upn.enabled DESC, p.created_at DESC
    ", [$user_id]);

    // Format response
    $formatted = array_map(function($notification) {
        return [
            'podcast_id' => (int)$notification['podcast_id'],
            'title' => $notification['title'],
            'thumbnail' => $notification['thumbnail'] ? UPLOADS_URL . '/podcasts/' . $notification['thumbnail'] : null,
            'is_series' => (bool)$notification['is_series'],
            'total_episodes' => (int)$notification['total_episodes'],
            'notifications_enabled' => (bool)$notification['notifications_enabled']
        ];
    }, $notifications);

    echo json_encode([
        'success' => true,
        'notifications' => $formatted
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
