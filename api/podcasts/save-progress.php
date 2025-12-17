<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$podcast_id = isset($data['podcast_id']) ? (int)$data['podcast_id'] : 0;
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
$guest_token = isset($data['guest_token']) ? $data['guest_token'] : null;
$current_time = isset($data['current_time']) ? (int)$data['current_time'] : 0;
$duration = isset($data['duration']) ? (int)$data['duration'] : 0;
$playback_speed = isset($data['playback_speed']) ? (float)$data['playback_speed'] : 1.0;
$volume = isset($data['volume']) ? (float)$data['volume'] : 1.0;

if ($podcast_id === 0 || ($user_id === null && $guest_token === null)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Calculate progress percentage
    $progress_percentage = $duration > 0 ? ($current_time / $duration) * 100 : 0;
    $is_completed = $progress_percentage >= 95 ? 1 : 0;

    // Check if progress record exists
    if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT id FROM podcast_progress WHERE user_id = ? AND podcast_id = ?");
        $stmt->execute([$user_id, $podcast_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM podcast_progress WHERE guest_token = ? AND podcast_id = ?");
        $stmt->execute([$guest_token, $podcast_id]);
    }
    
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        // Update existing progress
        if ($user_id !== null) {
            $stmt = $pdo->prepare("
                UPDATE podcast_progress 
                SET current_time = ?, duration = ?, progress_percentage = ?, 
                    is_completed = ?, playback_speed = ?, volume = ?, 
                    last_played_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND podcast_id = ?
            ");
            $stmt->execute([$current_time, $duration, $progress_percentage, $is_completed, 
                          $playback_speed, $volume, $user_id, $podcast_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE podcast_progress 
                SET current_time = ?, duration = ?, progress_percentage = ?, 
                    is_completed = ?, playback_speed = ?, volume = ?, 
                    last_played_at = CURRENT_TIMESTAMP
                WHERE guest_token = ? AND podcast_id = ?
            ");
            $stmt->execute([$current_time, $duration, $progress_percentage, $is_completed, 
                          $playback_speed, $volume, $guest_token, $podcast_id]);
        }
    } else {
        // Insert new progress record
        $stmt = $pdo->prepare("
            INSERT INTO podcast_progress 
            (user_id, guest_token, podcast_id, current_time, duration, progress_percentage, 
             is_completed, playback_speed, volume, last_played_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$user_id, $guest_token, $podcast_id, $current_time, $duration, 
                      $progress_percentage, $is_completed, $playback_speed, $volume]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Progress saved',
        'progress_percentage' => round($progress_percentage, 2),
        'is_completed' => $is_completed
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
