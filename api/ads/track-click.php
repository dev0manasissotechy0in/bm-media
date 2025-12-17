<?php
/**
 * Ad Click Tracking Endpoint
 * Tracks ad clicks for analytics
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    $ad_id = $_POST['ad_id'] ?? $_GET['ad_id'] ?? null;
    $ad_type = $_POST['ad_type'] ?? $_GET['ad_type'] ?? 'custom';
    $placement = $_POST['placement'] ?? $_GET['placement'] ?? '';
    
    if (!$ad_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing ad_id']);
        exit;
    }
    
    // Track click in analytics
    $db->insert('ad_analytics', [
        'ad_id' => $ad_id,
        'ad_type' => $ad_type,
        'placement' => $placement,
        'event_type' => 'click',
        'page_url' => $_SERVER['HTTP_REFERER'] ?? $_SERVER['REQUEST_URI'] ?? '',
        'user_ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Increment click count in custom_ads table if it exists
    if ($ad_type === 'custom') {
        $db->update('custom_ads', 
            ['clicks' => 'clicks + 1'],
            ['id' => $ad_id],
            true // Use raw query for increment
        );
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Click tracked']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error tracking click', 'error' => $e->getMessage()]);
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'UNKNOWN';
}
