<?php
/**
 * Market API - Top Movers (Gainers & Losers)
 * Returns top gaining and losing stocks
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $type = $_GET['type'] ?? 'both'; // 'gainers', 'losers', 'both'
    
    $data = [];
    
    // Fetch top gainers
    if ($type === 'gainers' || $type === 'both') {
        $gainers = $db->fetchAll("
            SELECT 
                s.*,
                (s.current_price - s.previous_close) as change_amount,
                ((s.current_price - s.previous_close) / s.previous_close * 100) as change_percentage
            FROM market_stocks s
            WHERE s.status = 'active' AND s.current_price > s.previous_close
            ORDER BY change_percentage DESC
            LIMIT ?
        ", [$limit]);
        
        $data['gainers'] = $gainers;
    }
    
    // Fetch top losers
    if ($type === 'losers' || $type === 'both') {
        $losers = $db->fetchAll("
            SELECT 
                s.*,
                (s.current_price - s.previous_close) as change_amount,
                ((s.current_price - s.previous_close) / s.previous_close * 100) as change_percentage
            FROM market_stocks s
            WHERE s.status = 'active' AND s.current_price < s.previous_close
            ORDER BY change_percentage ASC
            LIMIT ?
        ", [$limit]);
        
        $data['losers'] = $losers;
    }
    
    // Fetch most active stocks (by volume)
    $most_active = $db->fetchAll("
        SELECT 
            s.*,
            (s.current_price - s.previous_close) as change_amount,
            ((s.current_price - s.previous_close) / s.previous_close * 100) as change_percentage
        FROM market_stocks s
        WHERE s.status = 'active'
        ORDER BY s.volume DESC
        LIMIT ?
    ", [$limit]);
    
    $data['most_active'] = $most_active;
    
    // Market sentiment
    $sentiment = $db->fetchOne("
        SELECT 
            COUNT(CASE WHEN current_price > previous_close THEN 1 END) as advancing,
            COUNT(CASE WHEN current_price < previous_close THEN 1 END) as declining,
            COUNT(CASE WHEN current_price = previous_close THEN 1 END) as unchanged,
            COUNT(*) as total
        FROM market_stocks
        WHERE status = 'active'
    ");
    
    $data['sentiment'] = $sentiment;
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch market movers',
        'message' => $e->getMessage()
    ]);
}
