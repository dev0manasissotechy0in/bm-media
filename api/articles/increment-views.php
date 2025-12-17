<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance()->getConnection();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Article ID is required']);
    exit;
}

$article_id = intval($_GET['id']);

try {
    // Use the new trackView function
    trackView('article', $article_id);
    
    // Get updated count
    $count_query = "SELECT views_count FROM articles WHERE id = ?";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bind_param("i", $article_id);
        $count_stmt->execute();
        $result = $count_stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode(['success' => true, 'views' => $row['views_count']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to increment views']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
