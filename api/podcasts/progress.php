<?php
/**
 * API - Update Podcast Progress
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
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$db = Database::getInstance();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $podcast_id = isset($input['podcast_id']) ? (int)$input['podcast_id'] : 0;
    $episode_id = isset($input['episode_id']) ? (int)$input['episode_id'] : null;
    $progress_seconds = isset($input['progress_seconds']) ? (int)$input['progress_seconds'] : 0;
    $completed = isset($input['completed']) ? (bool)$input['completed'] : false;
    $user_id = $_SESSION['user_id'];

    if (!$podcast_id) {
        throw new Exception('Podcast ID is required');
    }

    // Check if progress exists
    $where = "user_id = ? AND podcast_id = ?";
    $params = [$user_id, $podcast_id];
    
    if ($episode_id) {
        $where .= " AND episode_id = ?";
        $params[] = $episode_id;
    } else {
        $where .= " AND episode_id IS NULL";
    }

    $existing = $db->fetchOne("SELECT id FROM user_podcast_progress WHERE $where", $params);

    if ($existing) {
        // Update
        $updateData = [
            'progress_seconds' => $progress_seconds,
            'completed' => $completed ? 1 : 0,
            'last_played_at' => date('Y-m-d H:i:s')
        ];
        $db->update('user_podcast_progress', $updateData, 'id = ?', [$existing['id']]);
    } else {
        // Insert
        $db->insert('user_podcast_progress', [
            'user_id' => $user_id,
            'podcast_id' => $podcast_id,
            'episode_id' => $episode_id,
            'progress_seconds' => $progress_seconds,
            'completed' => $completed ? 1 : 0
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Progress saved'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
