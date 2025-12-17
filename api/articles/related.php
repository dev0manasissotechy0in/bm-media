<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance()->getConnection();

$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$exclude_id = isset($_GET['exclude_id']) ? intval($_GET['exclude_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

try {
    $query = "SELECT 
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
                au.id as author_id,
                au.full_name as author_name
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              LEFT JOIN admin_users au ON a.author_id = au.id
              WHERE a.category_id = ? AND a.id != ? AND a.status = 'published' AND a.show_in_app = 1
              ORDER BY a.published_at DESC, a.created_at DESC
              LIMIT ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$category_id, $exclude_id, $limit]);
    
    $articles = [];
    while ($article = $stmt->fetch()) {
        // Get tag IDs
        $tag_query = "SELECT tag_id FROM article_tags WHERE article_id = ?";
        $tag_stmt = $db->prepare($tag_query);
        $tag_stmt->execute([$article['id']]);
        
        $tag_ids = [];
        while ($tag = $tag_stmt->fetch()) {
            $tag_ids[] = (string)$tag['tag_id'];
        }
        
        // Fetch timeline updates for live articles
        $timeline_updates = [];
        if ($article['is_live']) {
            $live_query = "SELECT id, update_text as text, created_at as time FROM article_live_updates WHERE article_id = ? ORDER BY created_at DESC";
            $live_stmt = $db->prepare($live_query);
            $live_stmt->execute([$article['id']]);
            
            while ($update = $live_stmt->fetch()) {
                $timeline_updates[] = [
                    'id' => $update['id'],
                    'text' => $update['text'],
                    'time' => $update['time']
                ];
            }
        }

        $article['image_url'] = getImageUrl($article['image_url'], 'articles');
        $article['featured'] = (bool)($article['featured'] ?? false);
        $article['is_live'] = (bool)($article['is_live'] ?? false);
        $article['is_breaking'] = (bool)($article['is_breaking'] ?? false);
        $article['comments'] = (bool)($article['comments'] ?? false);
        $article['tag_ids'] = $tag_ids;
        $article['timeline_updates'] = $timeline_updates;
        
        if ($article['author_id']) {
            $article['author'] = [
                'id' => $article['author_id'],
                'name' => $article['author_name']
            ];
        } else {
            $article['author'] = null;
        }
        
        if ($article['category_id']) {
            $article['category'] = [
                'id' => $article['category_id'],
                'name' => $article['category_name'],
                'image_url' => getImageUrl($article['category_icon'], 'categories')
            ];
        } else {
            $article['category'] = null;
        }
        
        unset($article['author_id'], $article['author_name']);
        unset($article['category_id'], $article['category_name'], $article['category_image']);
        
        $articles[] = $article;
    }
    
    echo json_encode(['success' => true, 'articles' => $articles]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
