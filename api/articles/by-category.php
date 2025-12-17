<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance();

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

if (!$category_id) {
    echo json_encode(['success' => false, 'message' => 'Category ID is required']);
    exit;
}

try {
    $articles = $db->fetchAll("SELECT 
                a.id,
                a.title,
                a.slug,
                a.description,
                a.thumbnail as image_url,
                a.media_video_url,
                a.media_video_file,
                a.content_type,
                a.is_featured as featured,
                a.is_live,
                a.is_breaking,
                a.status,
                a.views_count as views,
                a.likes_count as likes,
                a.published_at,
                a.created_at,
                a.updated_at,
                c.id as category_id,
                c.name as category_name,
                c.icon as category_icon,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.id
                    WHEN a.author_type = 'reporter' THEN r.id
                END as author_id,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.full_name
                    WHEN a.author_type = 'reporter' THEN r.full_name
                END as author_name
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              LEFT JOIN admin_users au ON a.author_id = au.id AND a.author_type = 'admin'
              LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
              WHERE a.category_id = ? AND a.status = 'published' AND a.show_in_app = 1
              ORDER BY a.published_at DESC, a.created_at DESC
              LIMIT ?", [$category_id, $limit]);
    
    $formattedArticles = array_map(function($article) use ($db) {
        // Get tag IDs
        $tag_ids = [];
        $tags = $db->fetchAll("SELECT tag_id FROM article_tags WHERE article_id = ?", [$article['id']]);
        foreach ($tags as $tag) {
            $tag_ids[] = (string)$tag['tag_id'];
        }
        
        // Fetch timeline updates for live articles
        $timeline_updates = [];
        if ($article['is_live']) {
            $live_updates = $db->fetchAll("
                SELECT id, update_text as text, created_at as time
                FROM article_live_updates 
                WHERE article_id = ? 
                ORDER BY created_at DESC
            ", [$article['id']]);
            
            foreach ($live_updates as $update) {
                $timeline_updates[] = [
                    'id' => $update['id'],
                    'text' => $update['text'],
                    'time' => $update['time']
                ];
            }
        }

        return [
            'id' => (string)$article['id'],
            'title' => $article['title'],
            'slug' => $article['slug'],
            'share_url' => BASE_URL . '/article/' . $article['slug'],
            'description' => $article['description'],
            'image_url' => getImageUrl($article['image_url'], 'articles'),
            'video_url' => $article['media_video_url'] ?: ($article['media_video_file'] ? BASE_URL . '/' . $article['media_video_file'] : null),
            'content_type' => $article['content_type'],
            'featured' => (bool)$article['featured'],
            'is_live' => (bool)$article['is_live'],
            'is_breaking' => (bool)$article['is_breaking'],
            'status' => $article['status'],
            'views' => (int)$article['views'],
            'likes' => (int)$article['likes'],
            'created_at' => $article['published_at'] ?? $article['created_at'],
            'updated_at' => $article['updated_at'],
            'timeline_updates' => $timeline_updates,
            'tag_ids' => $tag_ids,
            'category' => $article['category_id'] ? [
                'id' => (string)$article['category_id'],
                'name' => $article['category_name'],
                'image_url' => $article['category_icon'] ? BASE_URL . '/' . $article['category_icon'] : null
            ] : null,
            'author' => [
                'id' => (string)$article['author_id'],
                'name' => $article['author_name'] ?? 'Unknown'
            ]
        ];
    }, $articles);

    echo json_encode(['success' => true, 'articles' => $formattedArticles]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
