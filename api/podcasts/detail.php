<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$podcast_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$guest_token = isset($_GET['guest_token']) ? $_GET['guest_token'] : null;

if ($podcast_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid podcast ID']);
    exit;
}

try {
    // Fetch podcast details
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name as category_name, pc.color as category_color
        FROM podcasts p
        LEFT JOIN podcast_categories pc ON p.category_id = pc.id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$podcast_id]);
    $podcast = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$podcast) {
        echo json_encode(['success' => false, 'message' => 'Podcast not found']);
        exit;
    }

    // Fetch user's progress
    $progress = null;
    if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT * FROM podcast_progress WHERE user_id = ? AND podcast_id = ?");
        $stmt->execute([$user_id, $podcast_id]);
    } elseif ($guest_token !== null) {
        $stmt = $pdo->prepare("SELECT * FROM podcast_progress WHERE guest_token = ? AND podcast_id = ?");
        $stmt->execute([$guest_token, $podcast_id]);
    }
    
    if ($stmt) {
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Check if user liked
    $is_liked = false;
    if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM podcast_likes WHERE podcast_id = ? AND user_id = ?");
        $stmt->execute([$podcast_id, $user_id]);
    } elseif ($guest_token !== null) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM podcast_likes WHERE podcast_id = ? AND guest_token = ?");
        $stmt->execute([$podcast_id, $guest_token]);
    }
    
    if ($stmt) {
        $is_liked = $stmt->fetchColumn() > 0;
    }

    echo json_encode([
        'success' => true,
        'podcast' => $podcast,
        'progress' => $progress,
        'is_liked' => $is_liked
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
