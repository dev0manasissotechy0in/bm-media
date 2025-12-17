<?php
/**
 * API - Get Custom Ads
 * Returns active custom ads for specified placement
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';

$db = Database::getInstance();

try {
    // Get query parameters
    $placement = $_GET['placement'] ?? null;
    $position = $_GET['position'] ?? null;
    
    // Build query
    $query = "SELECT id, title, code, placement, position, impressions, clicks, created_at 
              FROM custom_ads 
              WHERE status = 1";
    
    $params = [];
    
    if ($placement) {
        $query .= " AND placement = ?";
        $params[] = $placement;
    }
    
    if ($position) {
        $query .= " AND position = ?";
        $params[] = $position;
    }
    
    $query .= " ORDER BY placement, position";
    
    // Fetch ads
    $ads = $db->fetchAll($query, $params);
    
    // Format ads for response
    $formatted_ads = [];
    foreach ($ads as $ad) {
        $formatted_ads[] = [
            'id' => (int)$ad['id'],
            'title' => $ad['title'],
            'code' => $ad['code'],
            'placement' => $ad['placement'],
            'position' => $ad['position'],
            'impressions' => (int)$ad['impressions'],
            'clicks' => (int)$ad['clicks'],
            'created_at' => $ad['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'ads' => $formatted_ads,
        'count' => count($formatted_ads)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching ads: ' . $e->getMessage()
    ]);
}
