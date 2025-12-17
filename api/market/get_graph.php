<?php
/**
 * Market API - Graph Data
 * Returns historical data for charting
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';

try {
    $db = Database::getInstance();
    
    $stock_id = $_GET['stock_id'] ?? null;
    $index_id = $_GET['index_id'] ?? null;
    $period = $_GET['period'] ?? '1M'; // 1D, 5D, 1M, 3M, 6M, 1Y, 5Y, MAX
    $interval = $_GET['interval'] ?? 'daily'; // minute, hourly, daily, weekly, monthly
    
    if (!$stock_id && !$index_id) {
        throw new Exception('Stock ID or Index ID is required');
    }
    
    // Calculate date range based on period
    $date_condition = '';
    switch ($period) {
        case '1D':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case '5D':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)";
            break;
        case '1M':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            break;
        case '3M':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case '6M':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            break;
        case '1Y':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
        case '5Y':
            $date_condition = "date >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)";
            break;
        default:
            $date_condition = "1=1"; // MAX - all data
    }
    
    if ($stock_id) {
        // Fetch stock history
        $data = $db->fetchAll("
            SELECT 
                date,
                open_price,
                high_price,
                low_price,
                close_price,
                volume
            FROM market_history
            WHERE stock_id = ? AND $date_condition
            ORDER BY date ASC
        ", [$stock_id]);
        
    } else {
        // Fetch index history
        $data = $db->fetchAll("
            SELECT 
                date,
                open_value as open_price,
                high_value as high_price,
                low_value as low_price,
                close_value as close_price,
                volume
            FROM market_index_history
            WHERE index_id = ? AND $date_condition
            ORDER BY date ASC
        ", [$index_id]);
    }
    
    // Format data for charting
    $formatted_data = [
        'dates' => array_column($data, 'date'),
        'open' => array_map('floatval', array_column($data, 'open_price')),
        'high' => array_map('floatval', array_column($data, 'high_price')),
        'low' => array_map('floatval', array_column($data, 'low_price')),
        'close' => array_map('floatval', array_column($data, 'close_price')),
        'volume' => array_map('intval', array_column($data, 'volume'))
    ];
    
    // Calculate statistics
    $close_prices = $formatted_data['close'];
    $statistics = [
        'min' => !empty($close_prices) ? min($close_prices) : 0,
        'max' => !empty($close_prices) ? max($close_prices) : 0,
        'avg' => !empty($close_prices) ? round(array_sum($close_prices) / count($close_prices), 2) : 0,
        'total_volume' => array_sum($formatted_data['volume']),
        'data_points' => count($data)
    ];
    
    // Calculate percentage change for period
    if (count($close_prices) >= 2) {
        $first_price = $close_prices[0];
        $last_price = $close_prices[count($close_prices) - 1];
        $statistics['period_change'] = $first_price > 0 
            ? round((($last_price - $first_price) / $first_price) * 100, 2) 
            : 0;
    } else {
        $statistics['period_change'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_data,
        'statistics' => $statistics,
        'period' => $period,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Stock ID or Index ID is required' ? 400 : 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
