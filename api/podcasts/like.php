<?php
/**
 * API - Like/Unlike Podcast or Episode
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

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login to like podcasts']);
    exit;
}

$db = Database::getInstance();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $podcast_id = isset($input['podcast_id']) ? (int)$input['podcast_id'] : 0;
    $episode_id = isset($input['episode_id']) ? (int)$input['episode_id'] : 0;
    $user_id = $_SESSION['user_id'];

    if (!$podcast_id && !$episode_id) {
        throw new Exception('Podcast ID or Episode ID is required');
    }

    // Check if already liked
    if ($episode_id) {
        $existing = $db->fetchOne("SELECT id FROM user_podcast_likes WHERE user_id = ? AND episode_id = ?", [$user_id, $episode_id]);
        
        if ($existing) {
            // Unlike
            $db->delete('user_podcast_likes', 'user_id = ? AND episode_id = ?', [$user_id, $episode_id]);
            $db->query("UPDATE podcast_episodes SET likes_count = likes_count - 1 WHERE id = ?", [$episode_id]);
            $liked = false;
        } else {
            // Like
            $db->insert('user_podcast_likes', ['user_id' => $user_id, 'episode_id' => $episode_id]);
            $db->query("UPDATE podcast_episodes SET likes_count = likes_count + 1 WHERE id = ?", [$episode_id]);
            $liked = true;
        }
    } else {
        $existing = $db->fetchOne("SELECT id FROM user_podcast_likes WHERE user_id = ? AND podcast_id = ?", [$user_id, $podcast_id]);
        
        if ($existing) {
            // Unlike
            $db->delete('user_podcast_likes', 'user_id = ? AND podcast_id = ?', [$user_id, $podcast_id]);
            $db->query("UPDATE podcasts SET likes_count = likes_count - 1 WHERE id = ?", [$podcast_id]);
            $liked = false;
        } else {
            // Like
            $db->insert('user_podcast_likes', ['user_id' => $user_id, 'podcast_id' => $podcast_id]);
            $db->query("UPDATE podcasts SET likes_count = likes_count + 1 WHERE id = ?", [$podcast_id]);
            $liked = true;
        }
    }

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'message' => $liked ? 'Podcast liked' : 'Podcast unliked'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
