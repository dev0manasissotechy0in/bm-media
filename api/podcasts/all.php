<?php
/**
 * API - Get All Podcasts
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

$db = Database::getInstance();

try {
    $limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 100)) : 20;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $limit;
    $category = isset($_GET['category']) ? trim($_GET['category']) : null;

    // Build query
    $where = "WHERE p.status = 'published' AND p.show_in_app = 1";
    $params = [];
    
    if ($category) {
        $where .= " AND p.category = ?";
        $params[] = $category;
    }

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
            p.created_at,
            p.updated_at
        FROM podcasts p
        $where
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));

    // Get total count
    $totalResult = $db->fetchOne("SELECT COUNT(*) as total FROM podcasts p $where", $params);
    $total = (int)$totalResult['total'];

    $formattedPodcasts = array_map(function($podcast) {
        return [
            'id' => (string)$podcast['id'],
            'title' => $podcast['title'],
            'slug' => $podcast['slug'],
            'description' => $podcast['description'],
            'thumbnail' => getImageUrl($podcast['thumbnail'], 'podcasts'),
            'cover_image' => getImageUrl($podcast['cover_image'], 'podcasts'),
            'author_name' => $podcast['author_name'],
            'author_bio' => null,
            'category' => $podcast['category'],
            'language' => $podcast['language'],
            'is_series' => (bool)$podcast['is_series'],
            'total_episodes' => (int)$podcast['total_episodes'],
            'views_count' => (int)$podcast['views_count'],
            'likes_count' => (int)$podcast['likes_count'],
            'saves_count' => (int)$podcast['saves_count'],
            'status' => 'published',
            'created_at' => $podcast['created_at'],
            'updated_at' => $podcast['updated_at']
        ];
    }, $podcasts);

    echo json_encode([
        'success' => true,
        'count' => count($formattedPodcasts),
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'podcasts' => $formattedPodcasts
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch podcasts',
        'error' => $e->getMessage()
    ]);
}
