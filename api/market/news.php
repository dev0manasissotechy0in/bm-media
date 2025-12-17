<?php
/**
 * Market API - Market News
 * Returns latest market and financial news
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $category = $_GET['category'] ?? null;
    $stock_id = $_GET['stock_id'] ?? null;
    
    // Build query conditions
    $conditions = ["status = 'published'"];
    $params = [];
    
    if ($category) {
        $conditions[] = "category = ?";
        $params[] = $category;
    }
    
    if ($stock_id) {
        $conditions[] = "stock_id = ?";
        $params[] = $stock_id;
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    // Fetch market news
    $news = $db->fetchAll("
        SELECT 
            n.*,
            s.symbol as stock_symbol,
            s.company_name
        FROM finance_news n
        LEFT JOIN market_stocks s ON n.stock_id = s.id
        WHERE $where_clause
        ORDER BY n.created_at DESC, n.is_featured DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    // Format news items
    foreach ($news as &$item) {
        $item['time_ago'] = getTimeAgo($item['created_at']);
        $item['is_featured'] = (bool)$item['is_featured'];
        
        if (!empty($item['image'])) {
            $item['image_url'] = UPLOADS_URL . '/market/news/' . $item['image'];
        }
    }
    
    // Get total count
    $total_count = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM finance_news 
        WHERE $where_clause
    ", $params)['count'];
    
    // Get featured news
    $featured = $db->fetchAll("
        SELECT n.*, s.symbol as stock_symbol
        FROM finance_news n
        LEFT JOIN market_stocks s ON n.stock_id = s.id
        WHERE n.status = 'published' AND n.is_featured = 1
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    
    // Get news categories
    $categories = $db->fetchAll("
        SELECT DISTINCT category 
        FROM finance_news 
        WHERE status = 'published' AND category IS NOT NULL
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $news,
        'featured' => $featured,
        'categories' => array_column($categories, 'category'),
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch market news',
        'message' => $e->getMessage()
    ]);
}

/**
 * Helper function to calculate time ago
 */
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M j, Y', $time);
}
