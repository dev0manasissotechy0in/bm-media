<?php
// ============================================================
// MARKET API - GET INDICES AND STOCKS
// ============================================================

header('Content-Type: application/json');
require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    // Get major indices (Sensex, Nifty)
    $indices = $db->fetchAll("
        SELECT * FROM market_indices 
        ORDER BY symbol ASC
    ");
    
    // Get top gainers
    $top_gainers = $db->fetchAll("
        SELECT * FROM market_stocks 
        WHERE status = 'active' AND change_percentage > 0
        ORDER BY change_percentage DESC 
        LIMIT 10
    ");
    
    // Get top losers
    $top_losers = $db->fetchAll("
        SELECT * FROM market_stocks 
        WHERE status = 'active' AND change_percentage < 0
        ORDER BY change_percentage ASC 
        LIMIT 10
    ");
    
    // Get most active stocks
    $most_active = $db->fetchAll("
        SELECT * FROM market_stocks 
        WHERE status = 'active'
        ORDER BY volume DESC 
        LIMIT 10
    ");
    
    // Get latest market news
    $market_news = $db->fetchAll("
        SELECT * FROM finance_news 
        ORDER BY published_at DESC 
        LIMIT 10
    ");
    
    // Calculate market sentiment
    $total_stocks = $db->count('market_stocks', "status = 'active'");
    $stocks_up = $db->count('market_stocks', "status = 'active' AND change_value > 0");
    $stocks_down = $db->count('market_stocks', "status = 'active' AND change_value < 0");
    $stocks_unchanged = $total_stocks - $stocks_up - $stocks_down;
    
    $sentiment = [
        'total' => $total_stocks,
        'advancing' => $stocks_up,
        'declining' => $stocks_down,
        'unchanged' => $stocks_unchanged,
        'advance_decline_ratio' => $stocks_down > 0 ? round($stocks_up / $stocks_down, 2) : 0
    ];
    
    jsonResponse([
        'success' => true,
        'data' => [
            'indices' => $indices,
            'top_gainers' => $top_gainers,
            'top_losers' => $top_losers,
            'most_active' => $most_active,
            'market_news' => $market_news,
            'sentiment' => $sentiment
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'message' => 'Failed to fetch market data'
    ], 500);
}
?>
