<?php
// Disable HTML error display for API responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Article ID is required']);
    exit;
}

$article_id = intval($_GET['id']);

try {
    $query = "SELECT 
                a.id,
                a.title,
                a.description as summary,
                a.description,
                a.content,
                a.thumbnail as image_url,
                a.media_video_url as video_url,
                NULL as audio_url,
                NULL as source_url,
                a.content_type,
                'free' as price_status,
                a.is_featured as featured,
                a.is_live,
                a.is_breaking,
                TRUE as comments,
                a.status,
                a.views_count as views,
                a.likes_count as likes,
                a.published_at as created_at,
                a.updated_at,
                c.id as category_id,
                c.name as category_name,
                c.icon as category_image,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.id
                    WHEN a.author_type = 'reporter' THEN r.id
                END as author_id,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.full_name
                    WHEN a.author_type = 'reporter' THEN r.full_name
                END as author_name,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.email
                    WHEN a.author_type = 'reporter' THEN r.email
                END as author_email,
                NULL as author_bio,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.profile_photo
                    WHEN a.author_type = 'reporter' THEN r.profile_photo
                END as author_avatar
              FROM articles a
              LEFT JOIN categories c ON a.category_id = c.id
              LEFT JOIN admin_users au ON a.author_id = au.id AND a.author_type = 'admin'
              LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
              WHERE a.id = ? AND a.status = 'published'";
    
    $article = $db->fetchOne($query, [$article_id]);
    
    if ($article) {
        // Build full content from article_sections
        $sections = $db->fetchAll("SELECT section_type, subtitle, content, media_url, media_alt 
                                    FROM article_sections 
                                    WHERE article_id = ? 
                                    ORDER BY order_id ASC", [$article_id]);
        
        $fullContent = '';
        foreach ($sections as $section) {
            if ($section['section_type'] == 'subtitle' && !empty($section['subtitle'])) {
                $fullContent .= '<h3>' . htmlspecialchars($section['subtitle']) . '</h3>';
            }
            if (!empty($section['content'])) {
                $fullContent .= '<p>' . nl2br(htmlspecialchars($section['content'])) . '</p>';
            }
            if (!empty($section['media_url'])) {
                if ($section['section_type'] == 'text_image') {
                    $fullContent .= '<img src="' . getImageUrl($section['media_url'], 'articles') . '" alt="' . htmlspecialchars($section['media_alt'] ?? '') . '" style="max-width:100%;height:auto;">';
                } elseif ($section['section_type'] == 'text_video') {
                    $fullContent .= '<video controls style="max-width:100%;height:auto;"><source src="' . getImageUrl($section['media_url'], 'articles') . '" type="video/mp4"></video>';
                }
            }
        }
        
        // Set content field - priority: article_sections > content column > description
        if (!empty($fullContent)) {
            $article['content'] = $fullContent;
        } elseif (!empty($article['content'])) {
            // Content exists in the main content field, keep it as is
            $article['content'] = $article['content'];
        } else {
            // Fallback to description
            $article['content'] = $article['description'];
        }
        
        // Get article slug for share URL
        $slug = $db->fetchOne("SELECT slug FROM articles WHERE id = ?", [$article_id])['slug'] ?? '';
        
        // Get tags
        $tags = $db->fetchAll("SELECT t.id, t.name 
                      FROM tags t
                      INNER JOIN article_tags at ON t.id = at.tag_id
                      WHERE at.article_id = ?", [$article_id]);
        
        $tag_ids = [];
        foreach ($tags as $tag) {
            $tag_ids[] = $tag['id'];
        }
        
        // Get live updates if it's a live article
        $timeline_updates = [];
        if ($article['is_live']) {
            $live_updates = $db->fetchAll("
                SELECT id, update_text as text, created_at as time
                FROM article_live_updates 
                WHERE article_id = ? 
                ORDER BY created_at DESC
            ", [$article_id]);
            
            foreach ($live_updates as $update) {
                $timeline_updates[] = [
                    'id' => $update['id'],
                    'text' => $update['text'],
                    'time' => $update['time']
                ];
            }
        }
        
        // Format response
        $article['image_url'] = getImageUrl($article['image_url'], 'articles');
        $article['video_url'] = $article['video_url'] ?: null;
        $article['audio_url'] = $article['audio_url'] ?: null;
        $article['source_url'] = $article['source_url'] ? $article['source_url'] : null;
        $article['featured'] = (bool)$article['featured'];
        $article['is_live'] = (bool)$article['is_live'];
        $article['is_breaking'] = (bool)$article['is_breaking'];
        $article['timeline_updates'] = $timeline_updates;
        $article['comments'] = (bool)$article['comments'];
        $article['tag_ids'] = $tag_ids;
        $article['slug'] = $slug;
        $article['share_url'] = BASE_URL . '/article/' . $slug;
        
        // Ensure all IDs are strings for Flutter compatibility
        $article['id'] = (string)$article['id'];
        
        // Format author
        if ($article['author_id']) {
            $article['author'] = [
                'id' => (string)$article['author_id'],
                'name' => $article['author_name'],
                'email' => $article['author_email'],
                'bio' => $article['author_bio'],
                'avatar' => $article['author_avatar'] ? BASE_URL . '/' . $article['author_avatar'] : null
            ];
        } else {
            $article['author'] = null;
        }
        
        // Format category
        if ($article['category_id']) {
            $article['category'] = [
                'id' => (string)$article['category_id'],
                'name' => $article['category_name'],
                'image_url' => getImageUrl($article['category_image'], 'categories')
            ];
        } else {
            $article['category'] = null;
        }
        
        // Remove redundant fields
        unset($article['author_id'], $article['author_name'], $article['author_email'], 
              $article['author_bio'], $article['author_avatar']);
        unset($article['category_id'], $article['category_name'], $article['category_image']);
        
        echo json_encode(['success' => true, 'article' => $article]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Article not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
