<?php
/**
 * Election API - Live News Updates
 * Returns real-time election news and updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $since = $_GET['since'] ?? null; // Timestamp for incremental updates
    
    // Build query conditions
    $conditions = ["status = 'published'"];
    $params = [];
    
    if ($since) {
        $conditions[] = "created_at > ?";
        $params[] = $since;
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    // Fetch election news
    $news = $db->fetchAll("
        SELECT 
            n.*,
            p.name as party_name,
            p.color_code as party_color,
            cons.name as constituency_name
        FROM election_news n
        LEFT JOIN election_parties p ON n.party_id = p.id
        LEFT JOIN election_constituencies cons ON n.constituency_id = cons.id
        WHERE $where_clause
        ORDER BY n.created_at DESC, n.priority DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    // Format news items
    foreach ($news as &$item) {
        $item['time_ago'] = getTimeAgo($item['created_at']);
        $item['is_breaking'] = (bool)$item['is_breaking'];
        
        if (!empty($item['image'])) {
            $item['image_url'] = UPLOADS_URL . '/election/news/' . $item['image'];
        }
    }
    
    // Get total count
    $total_count = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM election_news 
        WHERE $where_clause
    ", $params)['count'];
    
    // Get breaking news count
    $breaking_count = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM election_news 
        WHERE status = 'published' AND is_breaking = 1
    ")['count'];
    
    echo json_encode([
        'success' => true,
        'data' => $news,
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ],
        'stats' => [
            'breaking_news' => (int)$breaking_count,
            'new_updates' => $since ? count($news) : null
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch election news',
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
