<?php
/**
 * API - Save/Unsave Podcast
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
    echo json_encode(['success' => false, 'message' => 'Please login to save podcasts']);
    exit;
}

$db = Database::getInstance();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $podcast_id = isset($input['podcast_id']) ? (int)$input['podcast_id'] : 0;
    $user_id = $_SESSION['user_id'];

    if (!$podcast_id) {
        throw new Exception('Podcast ID is required');
    }

    // Check if already saved
    $existing = $db->fetchOne("SELECT id FROM user_saved_podcasts WHERE user_id = ? AND podcast_id = ?", [$user_id, $podcast_id]);
    
    if ($existing) {
        // Unsave
        $db->delete('user_saved_podcasts', 'user_id = ? AND podcast_id = ?', [$user_id, $podcast_id]);
        $db->query("UPDATE podcasts SET saves_count = saves_count - 1 WHERE id = ?", [$podcast_id]);
        $saved = false;
        $message = 'Podcast removed from saved list';
    } else {
        // Save
        $db->insert('user_saved_podcasts', ['user_id' => $user_id, 'podcast_id' => $podcast_id]);
        $db->query("UPDATE podcasts SET saves_count = saves_count + 1 WHERE id = ?", [$podcast_id]);
        $saved = true;
        $message = 'Podcast saved successfully';
    }

    echo json_encode([
        'success' => true,
        'saved' => $saved,
        'message' => $message
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
