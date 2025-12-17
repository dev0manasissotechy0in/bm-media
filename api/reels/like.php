<?php
/**
 * Like/Unlike Reel or Article with Reel
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Security::isLoggedIn('user')) {
    echo json_encode(['success' => false, 'message' => 'Please login to like reels']);
    exit;
}

// Handle both POST and JSON input
$input = json_decode(file_get_contents('php://input'), true);
$reel_id = $input['id'] ?? $_POST['id'] ?? 0;
$type = $input['type'] ?? $_POST['type'] ?? 'reel';
$user_id = $_SESSION['user_id'];

if (!$reel_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$db = Database::getInstance();

try {
    if ($type === 'article') {
        // Like/Unlike article
        $existing = $db->fetchOne("
            SELECT id FROM article_likes 
            WHERE article_id = ? AND user_id = ?
        ", [$reel_id, $user_id]);
        
        if ($existing) {
            // Unlike
            $db->delete('article_likes', 'id = ?', [$existing['id']]);
            $db->query("UPDATE articles SET likes_count = likes_count - 1 WHERE id = ?", [$reel_id]);
            $liked = false;
        } else {
            // Like
            $db->insert('article_likes', [
                'article_id' => $reel_id,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->query("UPDATE articles SET likes_count = likes_count + 1 WHERE id = ?", [$reel_id]);
            $liked = true;
        }
        
        $result = $db->fetchOne("SELECT likes_count FROM articles WHERE id = ?", [$reel_id]);
        $count = $result['likes_count'];
        
    } else {
        // Like/Unlike reel
        $existing = $db->fetchOne("
            SELECT id FROM user_reel_likes 
            WHERE reel_id = ? AND user_id = ?
        ", [$reel_id, $user_id]);
        
        if ($existing) {
            // Unlike
            $db->delete('user_reel_likes', 'id = ?', [$existing['id']]);
            $db->query("UPDATE reels SET likes_count = likes_count - 1 WHERE id = ?", [$reel_id]);
            $liked = false;
        } else {
            // Like
            $db->insert('user_reel_likes', [
                'reel_id' => $reel_id,
                'user_id' => $user_id,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->query("UPDATE reels SET likes_count = likes_count + 1 WHERE id = ?", [$reel_id]);
            $liked = true;
        }
        
        $result = $db->fetchOne("SELECT likes_count FROM reels WHERE id = ?", [$reel_id]);
        $count = $result['likes_count'];
    }
    
    echo json_encode([
        'success' => true,
        'liked' => $liked,
        'likes' => formatNumber($count),
        'count' => $count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}


echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $count
]);
