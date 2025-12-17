<?php
/**
 * API - Toggle Podcast Notification
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    $input = json_decode(file_get_contents('php://input'), true);
    $podcast_id = isset($input['podcast_id']) ? (int)$input['podcast_id'] : 0;
    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : true;

    if (!$podcast_id) {
        throw new Exception('Podcast ID is required');
    }

    // Check if notification setting exists
    $existing = $db->fetchOne("SELECT id FROM user_podcast_notifications WHERE user_id = ? AND podcast_id = ?", [$user_id, $podcast_id]);

    if ($existing) {
        // Update
        $db->update('user_podcast_notifications', ['enabled' => $enabled ? 1 : 0], 'id = ?', [$existing['id']]);
    } else {
        // Insert
        $db->insert('user_podcast_notifications', [
            'user_id' => $user_id,
            'podcast_id' => $podcast_id,
            'enabled' => $enabled ? 1 : 0
        ]);
    }

    echo json_encode([
        'success' => true,
        'enabled' => $enabled,
        'message' => $enabled ? 'Notifications enabled for this podcast' : 'Notifications disabled for this podcast'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
