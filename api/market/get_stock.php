<?php
/**
 * Market API - Stock Details
 * Returns detailed information about a specific stock
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $stock_id = $_GET['id'] ?? null;
    $symbol = $_GET['symbol'] ?? null;
    
    if (!$stock_id && !$symbol) {
        throw new Exception('Stock ID or Symbol is required');
    }
    
    // Fetch stock details
    if ($stock_id) {
        $stock = $db->fetchOne("
            SELECT * FROM market_stocks 
            WHERE id = ? AND status = 'active'
        ", [$stock_id]);
    } else {
        $stock = $db->fetchOne("
            SELECT * FROM market_stocks 
            WHERE symbol = ? AND status = 'active'
        ", [$symbol]);
    }
    
    if (!$stock) {
        throw new Exception('Stock not found');
    }
    
    // Calculate change
    $change_amount = $stock['current_price'] - $stock['previous_close'];
    $change_percentage = $stock['previous_close'] > 0 
        ? round(($change_amount / $stock['previous_close']) * 100, 2) 
        : 0;
    
    $stock['change_amount'] = $change_amount;
    $stock['change_percentage'] = $change_percentage;
    $stock['is_positive'] = $change_amount >= 0;
    
    // Fetch historical data (last 30 days)
    $history = $db->fetchAll("
        SELECT 
            date,
            open_price,
            high_price,
            low_price,
            close_price,
            volume
        FROM market_history
        WHERE stock_id = ?
        ORDER BY date DESC
        LIMIT 30
    ", [$stock['id']]);
    
    // Calculate 52-week high/low
    $year_data = $db->fetchOne("
        SELECT 
            MAX(high_price) as week_52_high,
            MIN(low_price) as week_52_low
        FROM market_history
        WHERE stock_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 52 WEEK)
    ", [$stock['id']]);
    
    $stock['week_52_high'] = $year_data['week_52_high'] ?? $stock['high_price'];
    $stock['week_52_low'] = $year_data['week_52_low'] ?? $stock['low_price'];
    
    // Calculate moving averages
    $sma_20 = $db->fetchOne("
        SELECT AVG(close_price) as sma_20
        FROM (
            SELECT close_price 
            FROM market_history 
            WHERE stock_id = ? 
            ORDER BY date DESC 
            LIMIT 20
        ) as recent
    ", [$stock['id']]);
    
    $sma_50 = $db->fetchOne("
        SELECT AVG(close_price) as sma_50
        FROM (
            SELECT close_price 
            FROM market_history 
            WHERE stock_id = ? 
            ORDER BY date DESC 
            LIMIT 50
        ) as recent
    ", [$stock['id']]);
    
    $stock['sma_20'] = round($sma_20['sma_20'] ?? 0, 2);
    $stock['sma_50'] = round($sma_50['sma_50'] ?? 0, 2);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stock' => $stock,
            'history' => array_reverse($history),
            'technical_indicators' => [
                'sma_20' => $stock['sma_20'],
                'sma_50' => $stock['sma_50'],
                'week_52_high' => $stock['week_52_high'],
                'week_52_low' => $stock['week_52_low']
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Stock ID or Symbol is required' ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
