<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$guest_token = isset($_GET['guest_token']) ? $_GET['guest_token'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

if ($user_id === null && $guest_token === null) {
    echo json_encode(['success' => false, 'message' => 'User ID or guest token required']);
    exit;
}

try {
    if ($user_id !== null) {
        $stmt = $pdo->prepare("
            SELECT pp.*, p.title, p.cover_image, p.duration, pc.name as category_name
            FROM podcast_progress pp
            LEFT JOIN podcasts p ON pp.podcast_id = p.id
            LEFT JOIN podcast_categories pc ON p.category_id = pc.id
            WHERE pp.user_id = ? AND p.is_active = 1
            ORDER BY pp.last_played_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
    } else {
        $stmt = $pdo->prepare("
            SELECT pp.*, p.title, p.cover_image, p.duration, pc.name as category_name
            FROM podcast_progress pp
            LEFT JOIN podcasts p ON pp.podcast_id = p.id
            LEFT JOIN podcast_categories pc ON p.category_id = pc.id
            WHERE pp.guest_token = ? AND p.is_active = 1
            ORDER BY pp.last_played_at DESC
            LIMIT ?
        ");
        $stmt->execute([$guest_token, $limit]);
    }

    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'history' => $history
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
