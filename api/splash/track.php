<?php
/**
 * SPLASH SCREEN ANALYTICS API
 * Track button clicks on dynamic splash screen
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
    $action = isset($input['action']) ? $input['action'] : '';
    
    if ($action === 'button_click') {
        // Increment click count
        $stmt = $pdo->prepare("UPDATE splash_screen_settings SET click_count = click_count + 1 WHERE id = 1");
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Click tracked'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'INVALID_ACTION',
            'message' => 'Invalid action specified'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Database error in splash analytics API: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to track analytics'
    ]);
} catch (Exception $e) {
    error_log("Error in splash analytics API: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
