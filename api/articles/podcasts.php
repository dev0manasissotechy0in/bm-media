<?php
// Disable HTML error display for API responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance();

try {
    // Get limit from query parameter, default to 20
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $limit = max(1, min($limit, 100)); // Between 1 and 100

    // Get page for pagination
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;

    // Query to fetch podcasts from podcasts table
    $podcasts = $db->fetchAll("
        SELECT 
            id,
            title,
            slug,
            description,
            thumbnail,
            cover_image,
            audio_url,
            duration,
            author_name,
            author_bio,
            category,
            language,
            is_series,
            total_episodes,
            views_count,
            likes_count,
            saves_count,
            created_at,
            updated_at
        FROM podcasts
        WHERE status = 'published'
        AND show_in_app = 1
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ", [$limit, $offset]);

    // Get total count for pagination
    $totalResult = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM podcasts 
        WHERE status = 'published'
        AND show_in_app = 1
    ");
    $total = (int)$totalResult['total'];
    
    $formattedPodcasts = array_map(function($podcast) {
        // Get thumbnail URL
        $thumbnailUrl = getImageUrl($podcast['thumbnail'], 'podcasts');
        $coverImageUrl = getImageUrl($podcast['cover_image'], 'podcasts');

        return [
            'id' => (string)$podcast['id'],
            'title' => $podcast['title'],
            'slug' => $podcast['slug'],
            'description' => $podcast['description'],
            'content' => $podcast['description'], // Use description as content
            'image_url' => $thumbnailUrl,
            'thumbnail_url' => $thumbnailUrl,
            'cover_image_url' => $coverImageUrl,
            'audio_url' => $podcast['audio_url'],
            'duration' => (int)$podcast['duration'],
            'content_type' => 'audio',
            'share_url' => BASE_URL . '/podcast/' . $podcast['slug'],
            'article_url' => BASE_URL . '/podcast/' . $podcast['slug'],
            'featured' => false,
            'is_live' => false,
            'is_breaking' => false,
            'is_series' => (bool)$podcast['is_series'],
            'total_episodes' => (int)$podcast['total_episodes'],
            'category' => [
                'id' => '0',
                'name' => $podcast['category'] ?? 'Podcast',
                'slug' => strtolower(str_replace(' ', '-', $podcast['category'] ?? 'podcast'))
            ],
            'views' => (int)$podcast['views_count'],
            'likes' => (int)$podcast['likes_count'],
            'saves' => (int)$podcast['saves_count'],
            'comments' => 0,
            'comments_enabled' => false,
            'author' => [
                'id' => '0',
                'name' => $podcast['author_name'] ?? 'Unknown',
                'avatar' => null,
                'bio' => $podcast['author_bio']
            ],
            'language' => $podcast['language'],
            'published_at' => $podcast['updated_at'],
            'created_at' => $podcast['created_at']
        ];
    }, $podcasts);

    echo json_encode([
        'success' => true,
        'count' => count($formattedPodcasts),
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'articles' => $formattedPodcasts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch podcast articles',
        'error' => $e->getMessage()
    ]);
}
