<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$podcast_id = isset($data['podcast_id']) ? (int)$data['podcast_id'] : 0;
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$guest_token = isset($data['guest_token']) ? $data['guest_token'] : null;

if ($podcast_id === 0 || ($user_id === null && $guest_token === null)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Check if already liked
    if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT id FROM podcast_likes WHERE podcast_id = ? AND user_id = ?");
        $stmt->execute([$podcast_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM podcast_likes WHERE podcast_id = ? AND guest_token = ?");
        $stmt->execute([$podcast_id, $guest_token]);
    }
    
    $liked = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($liked) {
        // Unlike
        if ($user_id !== null) {
            $stmt = $pdo->prepare("DELETE FROM podcast_likes WHERE podcast_id = ? AND user_id = ?");
            $stmt->execute([$podcast_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM podcast_likes WHERE podcast_id = ? AND guest_token = ?");
            $stmt->execute([$podcast_id, $guest_token]);
        }

        // Decrement like count
        $stmt = $pdo->prepare("UPDATE podcasts SET like_count = GREATEST(0, like_count - 1) WHERE id = ?");
        $stmt->execute([$podcast_id]);

        $is_liked = false;
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO podcast_likes (podcast_id, user_id, guest_token) VALUES (?, ?, ?)");
        $stmt->execute([$podcast_id, $user_id, $guest_token]);

        // Increment like count
        $stmt = $pdo->prepare("UPDATE podcasts SET like_count = like_count + 1 WHERE id = ?");
        $stmt->execute([$podcast_id]);

        $is_liked = true;
    }

    // Get updated like count
    $stmt = $pdo->prepare("SELECT like_count FROM podcasts WHERE id = ?");
    $stmt->execute([$podcast_id]);
    $podcast = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'liked' => $is_liked,
        'like_count' => number_format($podcast['like_count'])
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
