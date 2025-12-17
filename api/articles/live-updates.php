<?php
/**
 * API - Get Live Updates for an Article
 * Returns new updates since the last update ID
 */

// Disable HTML error display for API responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance();

// Get parameters
$article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 0;
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

if (!$article_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Article ID is required'
    ]);
    exit;
}

try {
    // Check if article exists and is live
    $article = $db->fetchOne("
        SELECT id, is_live 
        FROM articles 
        WHERE id = ? AND status = 'published'
    ", [$article_id]);
    
    if (!$article || !$article['is_live']) {
        echo json_encode([
            'success' => false,
            'message' => 'Article not found or not a live article'
        ]);
        exit;
    }
    
    // Fetch new updates since last_id
    $query = "
        SELECT id, update_text, created_at
        FROM article_live_updates 
        WHERE article_id = ?
    ";
    
    $params = [$article_id];
    
    if ($last_id > 0) {
        $query .= " AND id > ?";
        $params[] = $last_id;
    }
    
    $query .= " ORDER BY created_at DESC LIMIT 20";
    
    $updates = $db->fetchAll($query, $params);
    
    echo json_encode([
        'success' => true,
        'count' => count($updates),
        'updates' => $updates
    ]);
    
} catch (Exception $e) {
    error_log("Live updates error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch live updates',
        'error' => $e->getMessage()
    ]);
}
