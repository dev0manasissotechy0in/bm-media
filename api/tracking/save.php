<?php
/**
 * Save User Interactions API
 */

header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Session.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$interactions = $input['interactions'] ?? [];

if (empty($interactions)) {
    echo json_encode(['success' => true, 'message' => 'No interactions to save']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$saved_count = 0;

try {
    foreach ($interactions as $interaction) {
        $data = [
            'user_id' => $user_id,
            'session_id' => $interaction['session_id'] ?? null,
            'type' => $interaction['type'] ?? 'page_view',
            'reference_type' => $interaction['reference_type'] ?? null,
            'reference_id' => $interaction['reference_id'] ?? null,
            'page_url' => $interaction['page_url'] ?? null,
            'referrer_url' => $interaction['referrer'] ?? null,
            'device_type' => $interaction['device_type'] ?? null,
            'browser' => $interaction['browser'] ?? null,
            'os' => $interaction['os'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'read_duration' => $interaction['read_duration'] ?? null,
            'scroll_depth' => $interaction['scroll_depth'] ?? null,
            'metadata' => isset($interaction['metadata']) ? $interaction['metadata'] : null
        ];
        
        // Get country from IP (basic implementation)
        $data['country'] = $this->getCountryFromIP($data['ip_address']);
        
        $db->insert('user_interactions', $data);
        $saved_count++;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Saved {$saved_count} interactions"
    ]);
} catch (Exception $e) {
    error_log("Tracking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save interactions']);
}

/**
 * Get country from IP (basic implementation)
 * For production, use a proper GeoIP service
 */
function getCountryFromIP($ip) {
    // This is a placeholder - use a real GeoIP service in production
    // Options: MaxMind GeoIP, ip-api.com, ipstack.com, etc.
    return null;
}
