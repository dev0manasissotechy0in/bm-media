<?php
/**
 * Track Reel View
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

$reel_id = $_POST['id'] ?? 0;

if (!$reel_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid reel ID']);
    exit;
}

$db = Database::getInstance();

// Increment view count
trackView('reel', $reel_id);

echo json_encode(['success' => true]);
