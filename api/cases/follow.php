<?php
/**
 * CASE THREADS - FOLLOW API ENDPOINT
 * Follow a case thread
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $caseId = isset($input['case_id']) ? (int)$input['case_id'] : 0;
    
    if ($caseId <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'INVALID_CASE_ID',
            'message' => 'Valid case ID is required'
        ]);
        exit;
    }
    
    // TODO: Get user ID from authentication token
    // For now, we'll use a placeholder
    $userId = 1; // Replace with actual user authentication
    
    // Check if case exists
    $case = $db->fetchOne("SELECT id, total_followers FROM case_threads WHERE id = ?", [$caseId]);
    
    if (!$case) {
        echo json_encode([
            'success' => false,
            'error' => 'CASE_NOT_FOUND',
            'message' => 'Case not found'
        ]);
        exit;
    }
    
    // Check if already following
    $followCheck = $db->fetchOne("SELECT id FROM case_follows WHERE user_id = ? AND case_id = ?", [$userId, $caseId]);
    
    if ($followCheck) {
        echo json_encode([
            'success' => true,
            'message' => 'Already following this case'
        ]);
        exit;
    }
    
    // Get notification preferences from input (default all to true)
    $notifyArticles = isset($input['notify_new_articles']) ? (bool)$input['notify_new_articles'] : true;
    $notifyTimeline = isset($input['notify_timeline_events']) ? (bool)$input['notify_timeline_events'] : true;
    $notifyDocuments = isset($input['notify_documents']) ? (bool)$input['notify_documents'] : true;
    $notifyVerdicts = isset($input['notify_verdicts']) ? (bool)$input['notify_verdicts'] : true;
    
    // Insert follow record
    $db->query("
        INSERT INTO case_follows (user_id, case_id, notify_new_articles, notify_timeline_events, notify_documents, notify_verdicts)
        VALUES (?, ?, ?, ?, ?, ?)
    ", [$userId, $caseId, $notifyArticles, $notifyTimeline, $notifyDocuments, $notifyVerdicts]);
    
    // Update case followers count
    $db->query("UPDATE case_threads SET total_followers = total_followers + 1 WHERE id = ?", [$caseId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully followed case'
    ]);
    
} catch (Exception $e) {
    error_log("Error in case follow: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
