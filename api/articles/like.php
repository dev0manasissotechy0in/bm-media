<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Security::isLoggedIn('user')) {
    echo json_encode(['success' => false, 'message' => 'Please login to like articles']);
    exit;
}

$article_id = $_POST['article_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$article_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid article ID']);
    exit;
}

$db = Database::getInstance();

// Check if article exists
$article = $db->fetchOne("SELECT id FROM articles WHERE id = ?", [$article_id]);
if (!$article) {
    echo json_encode(['success' => false, 'message' => 'Article not found']);
    exit;
}

try {
    // Check if already liked
    $existing = $db->fetchOne("
        SELECT id FROM user_article_likes 
        WHERE article_id = ? AND user_id = ?
    ", [$article_id, $user_id]);

    if ($existing) {
        // Unlike
        $db->delete('user_article_likes', 'article_id = ? AND user_id = ?', [$article_id, $user_id]);
        
        // Decrement count
        $db->query("UPDATE articles SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?", [$article_id]);
        
        $liked = false;
    } else {
        // Like
        $db->insert('user_article_likes', [
            'article_id' => $article_id,
            'user_id' => $user_id
        ]);
        
        // Increment count
        $db->query("UPDATE articles SET likes_count = likes_count + 1 WHERE id = ?", [$article_id]);
        
        $liked = true;
    }

    // Get updated count
    $count = $db->fetchOne("SELECT likes_count FROM articles WHERE id = ?", [$article_id])['likes_count'];

    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes_count' => $count,
        'message' => $liked ? 'Article liked' : 'Article unliked'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update like status: ' . $e->getMessage()
    ]);
}
