<?php
/**
 * Get Custom Page Details for Mobile App
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Get page by slug or id
    $slug = $_GET['slug'] ?? '';
    $id = $_GET['id'] ?? '';
    
    if (empty($slug) && empty($id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Page slug or id is required'
        ]);
        exit;
    }
    
    // Build query based on what's provided
    if (!empty($slug)) {
        $page = $db->fetchOne("
            SELECT 
                id,
                title,
                slug,
                page_type,
                content,
                category_id,
                tag_id,
                meta_title,
                meta_description,
                views_count,
                created_at,
                updated_at
            FROM custom_pages 
            WHERE slug = ? 
            AND status = 'published'
            AND show_in_app = 1
        ", [$slug]);
    } else {
        $page = $db->fetchOne("
            SELECT 
                id,
                title,
                slug,
                page_type,
                content,
                category_id,
                tag_id,
                meta_title,
                meta_description,
                views_count,
                created_at,
                updated_at
            FROM custom_pages 
            WHERE id = ? 
            AND status = 'published'
            AND show_in_app = 1
        ", [$id]);
    }
    
    if (!$page) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Page not found'
        ]);
        exit;
    }
    
    // Increment views
    $db->query("UPDATE custom_pages SET views_count = views_count + 1 WHERE id = ?", [$page['id']]);
    
    // Format page data
    $pageData = [
        'id' => (int)$page['id'],
        'title' => $page['title'],
        'slug' => $page['slug'],
        'type' => $page['page_type'],
        'content' => $page['content'] ?? '',
        'category_id' => $page['category_id'] ? (int)$page['category_id'] : null,
        'tag_id' => $page['tag_id'] ? (int)$page['tag_id'] : null,
        'meta_title' => $page['meta_title'],
        'meta_description' => $page['meta_description'],
        'views' => (int)$page['views_count'],
        'created_at' => $page['created_at'],
        'updated_at' => $page['updated_at'],
        'share_url' => BASE_URL . '/page.php?slug=' . $page['slug']
    ];
    
    // If page type is category_articles or tag_articles, fetch related articles
    if ($page['page_type'] === 'category_articles' && $page['category_id']) {
        $articles = $db->fetchAll("
            SELECT 
                a.id,
                a.title,
                a.slug,
                a.description,
                a.thumbnail,
                a.content_type,
                a.views_count,
                a.likes_count,
                a.published_at,
                c.name as category_name,
                c.slug as category_slug
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.category_id = ? 
            AND a.status = 'published'
            AND a.show_in_app = 1
            ORDER BY a.published_at DESC
            LIMIT 20
        ", [$page['category_id']]);
        
        $pageData['articles'] = array_map(function($article) {
            return [
                'id' => (string)$article['id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'description' => $article['description'],
                'image_url' => !empty($article['thumbnail']) ? BASE_URL . '/uploads/articles/' . $article['thumbnail'] : null,
                'content_type' => $article['content_type'],
                'views' => (int)$article['views_count'],
                'likes' => (int)$article['likes_count'],
                'published_at' => $article['published_at'],
                'category' => [
                    'name' => $article['category_name'],
                    'slug' => $article['category_slug']
                ]
            ];
        }, $articles);
    } elseif ($page['page_type'] === 'tag_articles' && $page['tag_id']) {
        $articles = $db->fetchAll("
            SELECT 
                a.id,
                a.title,
                a.slug,
                a.description,
                a.thumbnail,
                a.content_type,
                a.views_count,
                a.likes_count,
                a.published_at,
                c.name as category_name,
                c.slug as category_slug
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            INNER JOIN article_tags at ON a.id = at.article_id
            WHERE at.tag_id = ? 
            AND a.status = 'published'
            AND a.show_in_app = 1
            ORDER BY a.published_at DESC
            LIMIT 20
        ", [$page['tag_id']]);
        
        $pageData['articles'] = array_map(function($article) {
            return [
                'id' => (string)$article['id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'description' => $article['description'],
                'image_url' => !empty($article['thumbnail']) ? BASE_URL . '/uploads/articles/' . $article['thumbnail'] : null,
                'content_type' => $article['content_type'],
                'views' => (int)$article['views_count'],
                'likes' => (int)$article['likes_count'],
                'published_at' => $article['published_at'],
                'category' => [
                    'name' => $article['category_name'],
                    'slug' => $article['category_slug']
                ]
            ];
        }, $articles);
    }
    
    echo json_encode([
        'success' => true,
        'page' => $pageData
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch page details',
        'error' => $e->getMessage()
    ]);
}
