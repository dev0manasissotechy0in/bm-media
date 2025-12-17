<?php
/**
 * Track Story View
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

$story_id = $_POST['id'] ?? 0;

if (!$story_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid story ID']);
    exit;
}

$db = Database::getInstance();

// Increment view count
trackView('story', $story_id);

echo json_encode(['success' => true]);
