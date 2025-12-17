<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Security::isLoggedIn('user')) {
    echo json_encode(['success' => false, 'message' => 'Please login to save articles']);
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
    // Check if already saved
    $existing = $db->fetchOne("
        SELECT id FROM user_saved_articles 
        WHERE article_id = ? AND user_id = ?
    ", [$article_id, $user_id]);

    if ($existing) {
        // Unsave
        $db->delete('user_saved_articles', 'article_id = ? AND user_id = ?', [$article_id, $user_id]);
        
        // Decrement count
        $db->query("UPDATE articles SET saves_count = GREATEST(0, saves_count - 1) WHERE id = ?", [$article_id]);
        
        $saved = false;
        $message = 'Article removed from saved list';
    } else {
        // Save
        $db->insert('user_saved_articles', [
            'article_id' => $article_id,
            'user_id' => $user_id
        ]);
        
        // Increment count
        $db->query("UPDATE articles SET saves_count = saves_count + 1 WHERE id = ?", [$article_id]);
        
        $saved = true;
        $message = 'Article saved successfully';
    }

    echo json_encode([
        'success' => true,
        'saved' => $saved,
        'message' => $message
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update save status: ' . $e->getMessage()
    ]);
}
