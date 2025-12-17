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

    // Query to fetch video articles (content_type = 'video')
    $articles = $db->fetchAll("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.description,
            a.content,
            a.thumbnail,
            a.media_video_url,
            a.media_video_file,
            a.content_type,
            a.category_id,
            a.views_count,
            a.likes_count,
            a.comments_count,
            a.comments_enabled,
            a.is_featured,
            a.is_live,
            a.is_breaking,
            a.published_at,
            a.created_at,
            c.name as category_name,
            c.slug as category_slug,
            au.id as author_id,
            CASE 
                WHEN a.author_type = 'admin' THEN au.full_name
                WHEN a.author_type = 'reporter' THEN r.full_name
            END as author_name,
            CASE 
                WHEN a.author_type = 'admin' THEN au.profile_photo
                WHEN a.author_type = 'reporter' THEN r.profile_photo
            END as author_avatar
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admin_users au ON a.author_id = au.id AND a.author_type = 'admin'
        LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
        WHERE a.content_type = 'video'
        AND a.status = 'published'
        AND a.show_in_app = 1
        ORDER BY a.published_at DESC
        LIMIT ? OFFSET ?
    ", [$limit, $offset]);

    // Get total count for pagination
    $totalResult = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM articles 
        WHERE content_type = 'video' 
        AND status = 'published'
        AND show_in_app = 1
    ");
    $total = (int)$totalResult['total'];
    
    $formattedArticles = array_map(function($article) use ($db) {
        // Get video URL from article_sections
        $videoSection = $db->fetchOne("
            SELECT media_url 
            FROM article_sections 
            WHERE article_id = ? AND section_type = 'text_video' 
            ORDER BY order_id ASC 
            LIMIT 1
        ", [$article['id']]);
        
        $videoUrl = null;
        if ($videoSection && !empty($videoSection['media_url'])) {
            $videoUrl = getImageUrl($videoSection['media_url'], 'articles');
        }

        // Get thumbnail URL
        $thumbnailUrl = getImageUrl($article['thumbnail'], 'articles');

        return [
            'id' => (string)$article['id'],
            'title' => $article['title'],
            'slug' => $article['slug'],
            'description' => $article['description'],
            'image_url' => $thumbnailUrl,
            'video_url' => $videoUrl,
            'content_type' => $article['content_type'],
            'share_url' => BASE_URL . '/article/' . $article['slug'],
            'article_url' => BASE_URL . '/article/' . $article['slug'],
            'featured' => (bool)$article['is_featured'],
            'is_live' => (bool)$article['is_live'],
            'is_breaking' => (bool)$article['is_breaking'],
            'category' => [
                'id' => (string)$article['category_id'],
                'name' => $article['category_name'],
                'slug' => $article['category_slug']
            ],
            'views' => (int)$article['views_count'],
            'likes' => (int)$article['likes_count'],
            'comments' => (int)$article['comments_count'],
            'comments_enabled' => (bool)($article['comments_enabled'] ?? true),
            'author' => [
                'id' => (string)$article['author_id'],
                'name' => $article['author_name'] ?? 'Unknown',
                'avatar' => !empty($article['author_avatar']) ? BASE_URL . '/uploads/authors/' . $article['author_avatar'] : null
            ],
            'published_at' => $article['published_at'],
            'created_at' => $article['created_at']
        ];
    }, $articles);

    echo json_encode([
        'success' => true,
        'count' => count($formattedArticles),
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit),
        'articles' => $formattedArticles
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch video articles',
        'error' => $e->getMessage()
    ]);
}
