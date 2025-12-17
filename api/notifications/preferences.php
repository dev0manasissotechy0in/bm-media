<?php
/**
 * NOTIFICATIONS - UPDATE PREFERENCES API ENDPOINT
 * Update notification preferences for a case follow
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // TODO: Get user ID from authentication token
    $userId = 1;
    
    // GET - Fetch current preferences
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $caseId = isset($_GET['case_id']) ? (int)$_GET['case_id'] : 0;
        
        if ($caseId <= 0) {
            echo json_encode([
                'success' => false,
                'error' => 'INVALID_CASE_ID',
                'message' => 'Valid case ID is required'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                notify_new_articles,
                notify_timeline_events,
                notify_documents,
                notify_verdicts
            FROM case_follows
            WHERE user_id = ? AND case_id = ?
        ");
        $stmt->execute([$userId, $caseId]);
        $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$preferences) {
            echo json_encode([
                'success' => false,
                'error' => 'NOT_FOLLOWING',
                'message' => 'Not following this case'
            ]);
            exit;
        }
        
        // Convert to boolean
        foreach ($preferences as $key => $value) {
            $preferences[$key] = (bool)$value;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $preferences
        ]);
        exit;
    }
    
    // POST - Update preferences
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
    
    // Check if following
    $checkStmt = $pdo->prepare("SELECT id FROM case_follows WHERE user_id = ? AND case_id = ?");
    $checkStmt->execute([$userId, $caseId]);
    
    if (!$checkStmt->fetch()) {
        echo json_encode([
            'success' => false,
            'error' => 'NOT_FOLLOWING',
            'message' => 'Not following this case'
        ]);
        exit;
    }
    
    // Update preferences
    $updates = [];
    $params = [];
    
    if (isset($input['notify_new_articles'])) {
        $updates[] = 'notify_new_articles = ?';
        $params[] = $input['notify_new_articles'] ? 1 : 0;
    }
    if (isset($input['notify_timeline_events'])) {
        $updates[] = 'notify_timeline_events = ?';
        $params[] = $input['notify_timeline_events'] ? 1 : 0;
    }
    if (isset($input['notify_documents'])) {
        $updates[] = 'notify_documents = ?';
        $params[] = $input['notify_documents'] ? 1 : 0;
    }
    if (isset($input['notify_verdicts'])) {
        $updates[] = 'notify_verdicts = ?';
        $params[] = $input['notify_verdicts'] ? 1 : 0;
    }
    
    if (empty($updates)) {
        echo json_encode([
            'success' => false,
            'error' => 'NO_UPDATES',
            'message' => 'No preferences to update'
        ]);
        exit;
    }
    
    $params[] = $userId;
    $params[] = $caseId;
    
    $query = "UPDATE case_follows SET " . implode(', ', $updates) . " WHERE user_id = ? AND case_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Notification preferences updated'
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in notification preferences: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to update preferences'
    ]);
} catch (Exception $e) {
    error_log("Error in notification preferences: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
