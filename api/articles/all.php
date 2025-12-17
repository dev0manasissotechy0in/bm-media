<?php
/**
 * API - Get All Published Articles
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance();

try {
    $articles = $db->fetchAll("
        SELECT 
            a.id,
            a.title,
            a.slug,
            a.description,
            a.content,
            a.thumbnail,
            a.thumbnail_alt,
            a.media_video_url,
            a.media_video_file,
            a.media_reel_url,
            a.media_reel_file,
            a.content_type,
            a.views_count,
            a.likes_count,
            a.comments_count,
            a.is_featured,
            a.is_live,
            a.is_breaking,
            a.published_at as created_at,
            a.status,
            c.id as category_id,
            c.name as category_name,
            c.slug as category_slug,
            CASE 
                WHEN a.author_type = 'admin' THEN au.id
                WHEN a.author_type = 'reporter' THEN r.id
            END as author_id,
            CASE 
                WHEN a.author_type = 'admin' THEN au.full_name
                WHEN a.author_type = 'reporter' THEN r.full_name
            END as author_name,
            CASE 
                WHEN a.author_type = 'admin' THEN au.profile_photo
                WHEN a.author_type = 'reporter' THEN r.profile_photo
            END as author_photo
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admin_users au ON a.author_id = au.id AND a.author_type = 'admin'
        LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
        WHERE a.status = 'published'
        AND a.show_in_app = 1
        ORDER BY a.published_at DESC
    ");

    // Format articles for Flutter app
    $formattedArticles = array_map(function($article) use ($db) {
        // Get tags for this article
        $tags = $db->fetchAll("
            SELECT t.id, t.name, t.slug
            FROM tags t
            INNER JOIN article_tags at ON t.id = at.tag_id
            WHERE at.article_id = ?
        ", [$article['id']]);

        // Get live updates if it's a live article
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
            'content' => $article['content'],
            'image_url' => getImageUrl($article['thumbnail'], 'articles'),
            'video_url' => $article['media_video_url'] ?: ($article['media_video_file'] ? BASE_URL . '/uploads/articles/' . $article['media_video_file'] : null),
            'content_type' => $article['content_type'],
            'views' => (int)$article['views_count'],
            'likes' => (int)$article['likes_count'],
            'featured' => (bool)$article['is_featured'],
            'is_live' => (bool)$article['is_live'],
            'is_breaking' => (bool)$article['is_breaking'],
            'timeline_updates' => $timeline_updates,
            'status' => $article['status'] === 'published' ? 'live' : $article['status'],
            'created_at' => $article['created_at'],
            'comments' => true,
            'price_status' => 'free',
            'category' => [
                'id' => (string)$article['category_id'],
                'name' => $article['category_name'],
                'slug' => $article['category_slug'],
            ],
            'author' => [
                'id' => (string)$article['author_id'],
                'name' => $article['author_name'] ?: 'Admin',
                'avatar' => getImageUrl($article['author_photo'], 'authors'),
            ],
            'tag_ids' => array_map(function($tag) {
                return (string)$tag['id'];
            }, $tags),
        ];
    }, $articles);

    echo json_encode([
        'success' => true,
        'articles' => $formattedArticles,
        'total' => count($formattedArticles)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching articles',
        'error' => $e->getMessage()
    ]);
}
