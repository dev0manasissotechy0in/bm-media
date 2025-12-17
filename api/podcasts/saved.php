<?php
/**
 * API - Get User's Saved Podcasts
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit;
}

$db = Database::getInstance();

try {
    $user_id = $_SESSION['user_id'];

    $podcasts = $db->fetchAll("
        SELECT 
            p.id,
            p.title,
            p.slug,
            p.description,
            p.thumbnail,
            p.cover_image,
            p.author_name,
            p.category,
            p.language,
            p.is_series,
            p.total_episodes,
            p.views_count,
            p.likes_count,
            p.saves_count,
            sp.saved_at
        FROM user_saved_podcasts sp
        INNER JOIN podcasts p ON sp.podcast_id = p.id
        WHERE sp.user_id = ? AND p.status = 'published'
        ORDER BY sp.saved_at DESC
    ", [$user_id]);

    $formattedPodcasts = array_map(function($podcast) {
        return [
            'id' => (string)$podcast['id'],
            'title' => $podcast['title'],
            'slug' => $podcast['slug'],
            'description' => $podcast['description'],
            'thumbnail' => getImageUrl($podcast['thumbnail'], 'podcasts'),
            'cover_image' => getImageUrl($podcast['cover_image'], 'podcasts'),
            'author_name' => $podcast['author_name'],
            'category' => $podcast['category'],
            'language' => $podcast['language'],
            'is_series' => (bool)$podcast['is_series'],
            'total_episodes' => (int)$podcast['total_episodes'],
            'views' => (int)$podcast['views_count'],
            'likes' => (int)$podcast['likes_count'],
            'saves' => (int)$podcast['saves_count'],
            'saved_at' => $podcast['saved_at']
        ];
    }, $podcasts);

    echo json_encode([
        'success' => true,
        'count' => count($formattedPodcasts),
        'podcasts' => $formattedPodcasts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch saved podcasts',
        'error' => $e->getMessage()
    ]);
}
