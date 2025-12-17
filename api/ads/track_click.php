<?php
/**
 * Ads API - Track Click
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';
require_once '../../includes/Ads.php';

try {
    $ad_id = $_POST['ad_id'] ?? null;
    
    if (!$ad_id) {
        throw new Exception('Ad ID is required');
    }
    
    trackAdClick($ad_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Click tracked successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
