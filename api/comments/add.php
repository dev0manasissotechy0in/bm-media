<?php
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!Security::isLoggedIn('user')) {
    echo json_encode(['success' => false, 'message' => 'Please login to comment']);
    exit;
}

// CSRF validation
if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$article_id = $_POST['article_id'] ?? 0;
$comment_text = trim($_POST['comment_text'] ?? '');
$parent_id = $_POST['parent_id'] ?? null;
$user_id = $_SESSION['user_id'];

// Validation
if (!$article_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid article ID']);
    exit;
}

if (empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Comment text is required']);
    exit;
}

if (strlen($comment_text) < 3) {
    echo json_encode(['success' => false, 'message' => 'Comment must be at least 3 characters']);
    exit;
}

if (strlen($comment_text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot exceed 1000 characters']);
    exit;
}

$db = Database::getInstance();

// Check if article exists
$article = $db->fetchOne("SELECT id FROM articles WHERE id = ? AND status = 'published'", [$article_id]);
if (!$article) {
    echo json_encode(['success' => false, 'message' => 'Article not found']);
    exit;
}

// Check parent comment if replying
if ($parent_id) {
    $parent = $db->fetchOne("SELECT id FROM comments WHERE id = ? AND article_id = ?", [$parent_id, $article_id]);
    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Parent comment not found']);
        exit;
    }
}

// Rate limiting - 100 comments per hour (very lenient for active discussions)
$rate_key = 'comment_' . $user_id;
if (!Security::checkRateLimit($rate_key, 100, 3600)) {
    echo json_encode(['success' => false, 'message' => 'Too many comments. Please wait before commenting again.']);
    exit;
}

// Sanitize comment
$comment_text = Security::sanitize($comment_text);

// Insert comment
$comment_id = $db->insert('comments', [
    'article_id' => $article_id,
    'user_id' => $user_id,
    'parent_id' => $parent_id,
    'comment_text' => $comment_text,
    'status' => COMMENT_AUTO_APPROVE ? 'approved' : 'pending',
    'created_at' => date('Y-m-d H:i:s')
]);

if ($comment_id) {
    // Increment comments count
    $db->query("UPDATE articles SET comments_count = comments_count + 1 WHERE id = ?", [$article_id]);
    
    // Get user info for display
    $user = $db->fetchOne("SELECT full_name, profile_photo FROM users WHERE id = ?", [$user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => COMMENT_AUTO_APPROVE ? 'Comment posted successfully' : 'Comment submitted for approval',
        'comment' => [
            'id' => $comment_id,
            'user_name' => $user['full_name'],
            'profile_photo' => $user['profile_photo'],
            'comment_text' => $comment_text,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => COMMENT_AUTO_APPROVE ? 'approved' : 'pending'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to post comment']);
}
