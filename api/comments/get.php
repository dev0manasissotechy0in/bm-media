<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance()->getConnection();

$article_id = isset($_GET['article_id']) ? intval($_GET['article_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

if (!$article_id) {
    echo json_encode(['success' => false, 'message' => 'Article ID is required']);
    exit;
}

try {
    $query = "SELECT 
                c.id,
                c.article_id,
                c.user_id,
                c.name as user_name,
                c.email as user_email,
                c.comment,
                c.created_at
              FROM comments c
              WHERE c.article_id = ?
              ORDER BY c.created_at DESC
              LIMIT ?";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ii", $article_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    while ($comment = $result->fetch_assoc()) {
        $comments[] = [
            'id' => $comment['id'],
            'article_id' => $comment['article_id'],
            'comment' => $comment['comment'],
            'created_at' => $comment['created_at'],
            'user' => [
                'id' => $comment['user_id'],
                'name' => $comment['user_name'],
                'email' => $comment['user_email']
            ]
        ];
    }
    
    echo json_encode(['success' => true, 'comments' => $comments]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
