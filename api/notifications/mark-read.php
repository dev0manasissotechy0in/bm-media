<?php
/**
 * NOTIFICATIONS - MARK AS READ API ENDPOINT
 * Mark one or multiple notifications as read
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // TODO: Get user ID from authentication token
    $userId = isset($input['user_id']) ? (int)$input['user_id'] : 1;
    
    // Can mark single notification or all notifications
    $notificationId = isset($input['notification_id']) ? (int)$input['notification_id'] : 0;
    $markAll = isset($input['mark_all']) && $input['mark_all'] === true;
    
    if (!$notificationId && !$markAll) {
        echo json_encode([
            'success' => false,
            'error' => 'INVALID_REQUEST',
            'message' => 'notification_id or mark_all is required'
        ]);
        exit;
    }
    
    if ($markAll) {
        // Mark all user notifications as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $affectedRows = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "Marked $affectedRows notifications as read"
        ]);
    } else {
        // Mark specific notification as read
        // Verify notification belongs to user
        $checkStmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$notificationId, $userId]);
        
        if (!$checkStmt->fetch()) {
            echo json_encode([
                'success' => false,
                'error' => 'NOT_FOUND',
                'message' => 'Notification not found'
            ]);
            exit;
        }
        
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in mark notification read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to mark notification as read'
    ]);
} catch (Exception $e) {
    error_log("Error in mark notification read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
