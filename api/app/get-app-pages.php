<?php
/**
 * Get Custom Pages for Mobile App
 * Returns pages that should be shown in app profile section
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

try {
    $db = Database::getInstance();
    
    // Get pages that should be shown in app
    $pages = $db->fetchAll("
        SELECT 
            id,
            title,
            slug,
            page_type,
            category_id,
            meta_description as description,
            order_id
        FROM custom_pages 
        WHERE show_in_app = 1 
        AND status = 'published'
        ORDER BY order_id ASC, title ASC
    ");
    
    // Transform pages to include full URL
    $appPages = array_map(function($page) {
        return [
            'id' => (int)$page['id'],
            'title' => $page['title'],
            'slug' => $page['slug'],
            'url' => BASE_URL . '/page.php?slug=' . $page['slug'],
            'type' => $page['page_type'],
            'description' => $page['description'] ?? '',
            'order' => (int)$page['order_id']
        ];
    }, $pages);
    
    echo json_encode([
        'success' => true,
        'pages' => $appPages,
        'count' => count($appPages)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch app pages',
        'error' => $e->getMessage()
    ]);
}
