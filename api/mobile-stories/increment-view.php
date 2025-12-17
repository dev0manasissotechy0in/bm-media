<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get story ID from request
    $data = json_decode(file_get_contents('php://input'), true);
    $storyId = $data['story_id'] ?? null;
    
    if (!$storyId) {
        throw new Exception('Story ID is required');
    }
    
    // Increment view count
    $query = "UPDATE mobile_stories SET views_count = views_count + 1 WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$storyId]);
    
    // Get updated count
    $query = "SELECT views_count FROM mobile_stories WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$storyId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'views_count' => $result['views_count'] ?? 0
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating view count: ' . $e->getMessage()
    ]);
}
