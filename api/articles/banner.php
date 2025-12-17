<?php
/**
 * API - Get Banner Articles (Live first, then Featured)
 * For Explore Tab Banner - Admin controlled via is_live and is_featured flags
 */

// Disable HTML error display for API responses
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
    // Get limit from query parameter
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    // First, get live articles (highest priority)
    $liveArticles = $db->fetchAll("
        SELECT 
            a.id, a.title, a.slug, a.description, a.thumbnail,
            a.content_type, a.views_count, a.likes_count, 
            a.is_featured, a.is_live, a.is_breaking,
            a.published_at as created_at,
            c.id as category_id, c.name as category_name, c.icon as category_icon,
            CASE 
                WHEN a.author_type = 'admin' THEN au.full_name
                WHEN a.author_type = 'reporter' THEN r.full_name
                ELSE 'Unknown'
            END as author_name,
            CASE 
                WHEN a.author_type = 'admin' THEN au.profile_photo
                WHEN a.author_type = 'reporter' THEN r.profile_photo
                ELSE NULL
            END as author_photo
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN admin_users au ON a.author_id = au.id AND a.author_type = 'admin'
        LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
        WHERE a.status = 'published' AND a.is_live = 1 AND a.show_in_app = 1
        ORDER BY a.published_at DESC, a.created_at DESC
        LIMIT ?
    ", [$limit]);
    
    $articles = $liveArticles;
    $remainingLimit = $limit - count($liveArticles);
    
    // If we don't have enough live articles, get featured articles
    if ($remainingLimit > 0) {
        $featuredArticles = $db->fetchAll("
            SELECT 
                a.id, a.title, a.slug, a.description, a.thumbnail,
                a.content_type, a.views_count, a.likes_count, 
                a.is_featured, a.is_live, a.is_breaking,
                a.published_at as created_at,
                c.id as category_id, c.name as category_name, c.icon as category_icon,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.full_name
                    WHEN a.author_type = 'reporter' THEN r.full_name
                    ELSE 'Unknown'
                END as author_name,
                CASE 
                    WHEN a.author_type = 'admin' THEN au.profile_photo
                    WHEN a.author_type = 'reporter' THEN r.profile_photo
                    ELSE NULL
                END as author_photo
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN admin_users au ON a.author_id = au.id AND a.author_type = 'admin'
            LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
            WHERE a.status = 'published' AND a.is_featured = 1 AND a.is_live = 0 AND a.show_in_app = 1
            ORDER BY a.published_at DESC, a.created_at DESC
            LIMIT ?
        ", [$remainingLimit]);
        
        $articles = array_merge($articles, $featuredArticles);
    }
    
    $formattedArticles = array_map(function($article) use ($db) {
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
            'id' => $article['id'],
            'title' => $article['title'],
            'description' => $article['description'],
            'summary' => null,
            'image_url' => getImageUrl($article['thumbnail'], 'articles'),
            'video_url' => null,
            'audio_url' => null,
            'source' => null,
            'content_type' => $article['content_type'],
            'price_status' => 'free',
            'featured' => (bool)$article['is_featured'],
            'is_live' => (bool)$article['is_live'],
            'is_breaking' => (bool)$article['is_breaking'],
            'timeline_updates' => $timeline_updates,
            'comments' => true,
            'status' => $article['is_live'] ? 'live' : 'published',
            'views' => (int)$article['views_count'],
            'likes' => (int)$article['likes_count'],
            'created_at' => $article['created_at'],
            'updated_at' => $article['created_at'],
            'category' => $article['category_id'] ? [
                'id' => $article['category_id'],
                'name' => $article['category_name'],
                'image_url' => getImageUrl($article['category_icon'], 'categories')
            ] : null,
            'author' => [
                'name' => $article['author_name'] ?? 'Unknown',
                'image_url' => getImageUrl($article['author_photo'], 'authors')
            ],
            'tag_ids' => []
        ];
    }, $articles);

    echo json_encode([
        'success' => true,
        'count' => count($formattedArticles),
        'articles' => $formattedArticles
    ]);
    
} catch (Exception $e) {
    error_log("Banner articles error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch banner articles',
        'error' => $e->getMessage()
    ]);
}
