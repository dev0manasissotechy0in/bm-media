<?php
/**
 * CASE THREADS - UNFOLLOW API ENDPOINT
 * Unfollow a case thread
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
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
    $userId = 1; // Replace with actual user authentication
    
    // Delete follow record
    $result = $db->query("DELETE FROM case_follows WHERE user_id = ? AND case_id = ?", [$userId, $caseId]);
    
    if ($result) {
        // Update case followers count
        $db->query("UPDATE case_threads SET total_followers = GREATEST(0, total_followers - 1) WHERE id = ?", [$caseId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Successfully unfollowed case'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'NOT_FOLLOWING',
            'message' => 'You are not following this case'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Database error in case unfollow: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to unfollow case'
    ]);
} catch (Exception $e) {
    error_log("Error in case unfollow: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
