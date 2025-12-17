<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../helpers.php';

$db = Database::getInstance()->getConnection();

try {
    $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    error_log("Category Articles API - Category ID: $categoryId, Limit: $limit, Offset: $offset");
    
    if ($categoryId === 0) {
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        exit;
    }
    
    // First, get all subcategory IDs for this parent category
    $subcategoryQuery = "SELECT id FROM categories WHERE parent_id = ? AND status = 'active'";
    $stmt = $db->prepare($subcategoryQuery);
    $stmt->execute([$categoryId]);
    
    $categoryIds = [$categoryId]; // Include the parent category
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryIds[] = $row['id'];
    }
    
    error_log("Found category IDs: " . implode(', ', $categoryIds));
    
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
    
    // Now get articles from this category and all its subcategories
    $query = "SELECT 
                a.id,
                a.title,
                a.slug,
                a.description,
                a.thumbnail as image_url,
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
            WHERE a.category_id IN ($placeholders) 
                AND a.status = 'published'
                AND a.show_in_app = 1
            ORDER BY a.published_at DESC, a.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($query);
    
    // Bind category IDs and pagination parameters
    $params = array_merge($categoryIds, [$limit, $offset]);
    $stmt->execute($params);
    
    $articles = [];
    while ($article = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Fetch timeline updates for live articles
        $timeline_updates = [];
        if ($article['is_live']) {
            $live_stmt = $db->prepare("SELECT id, update_text as text, created_at as time FROM article_live_updates WHERE article_id = ? ORDER BY created_at DESC");
            $live_stmt->execute([$article['id']]);
            
            while ($update = $live_stmt->fetch(PDO::FETCH_ASSOC)) {
                $timeline_updates[] = [
                    'id' => $update['id'],
                    'text' => $update['text'],
                    'time' => $update['time']
                ];
            }
        }

        $articles[] = [
            'id' => $article['id'],
            'title' => $article['title'],
            'slug' => $article['slug'],
            'share_url' => BASE_URL . '/article/' . $article['slug'],
            'description' => $article['description'],
            'summary' => null,
            'image_url' => getImageUrl($article['image_url'], 'articles'),
            'video_url' => null,
            'audio_url' => null,
            'source' => null,
            'content_type' => $article['content_type'],
            'price_status' => 'free',
            'featured' => (bool)$article['featured'],
            'is_live' => (bool)$article['is_live'],
            'is_breaking' => (bool)$article['is_breaking'],
            'comments' => true,
            'status' => $article['status'],
            'views' => (int)$article['views'],
            'likes' => (int)$article['likes'],
            'created_at' => $article['published_at'] ?? $article['created_at'],
            'updated_at' => $article['updated_at'],
            'timeline_updates' => $timeline_updates,
            'category' => $article['category_id'] ? [
                'id' => $article['category_id'],
                'name' => $article['category_name'],
                'image_url' => $article['category_icon'] ? BASE_URL . '/' . $article['category_icon'] : null
            ] : null,
            'author' => [
                'name' => $article['author_name'] ?? 'Unknown',
                'image_url' => $article['author_photo'] ? BASE_URL . '/' . $article['author_photo'] : null
            ],
            'tag_ids' => []
        ];
    }
    
    error_log("Returning " . count($articles) . " articles for category $categoryId");
    echo json_encode(['success' => true, 'articles' => $articles]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
