<?php
/**
 * Cricket API - Cricket News
 * Returns latest cricket news and updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $match_id = $_GET['match_id'] ?? null;
    $team_id = $_GET['team_id'] ?? null;
    
    // Build query conditions
    $conditions = ["status = 'published'"];
    $params = [];
    
    if ($match_id) {
        $conditions[] = "match_id = ?";
        $params[] = $match_id;
    }
    
    if ($team_id) {
        $conditions[] = "team_id = ?";
        $params[] = $team_id;
    }
    
    $where_clause = implode(' AND ', $conditions);
    
    // Fetch cricket news
    $news = $db->fetchAll("
        SELECT 
            n.*,
            m.match_name,
            m.venue,
            t.name as team_name,
            t.logo as team_logo
        FROM cricket_news n
        LEFT JOIN cricket_matches m ON n.match_id = m.id
        LEFT JOIN cricket_teams t ON n.team_id = t.id
        WHERE $where_clause
        ORDER BY n.created_at DESC, n.is_featured DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    // Format news items
    foreach ($news as &$item) {
        $item['time_ago'] = getTimeAgo($item['created_at']);
        $item['is_featured'] = (bool)$item['is_featured'];
        
        if (!empty($item['image'])) {
            $item['image_url'] = UPLOADS_URL . '/cricket/news/' . $item['image'];
        }
        
        if (!empty($item['team_logo'])) {
            $item['team_logo_url'] = UPLOADS_URL . '/cricket/teams/' . $item['team_logo'];
        }
    }
    
    // Get total count
    $total_count = $db->fetchOne("
        SELECT COUNT(*) as count 
        FROM cricket_news 
        WHERE $where_clause
    ", $params)['count'];
    
    // Get featured news
    $featured = $db->fetchAll("
        SELECT n.*
        FROM cricket_news n
        WHERE n.status = 'published' AND n.is_featured = 1
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    
    echo json_encode([
        'success' => true,
        'data' => $news,
        'featured' => $featured,
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
        'error' => 'Failed to fetch cricket news',
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
