<?php
/**
 * Save Cookie Preferences API
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

$user_id = $_SESSION['user_id'] ?? null;
$session_id = session_id();

// Generate unique session ID if not available
if (empty($session_id)) {
    session_start();
    $session_id = session_id();
}

$data = [
    'user_id' => $user_id,
    'session_id' => $session_id,
    'necessary_cookies' => 1, // Always true
    'functional_cookies' => $input['functional'] ?? 0,
    'analytics_cookies' => $input['analytics'] ?? 0,
    'marketing_cookies' => $input['marketing'] ?? 0,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
];

try {
    // Check if preference already exists
    $existing = null;
    if ($user_id) {
        $existing = $db->fetchOne("SELECT * FROM cookie_preferences WHERE user_id = ?", [$user_id]);
    } else {
        $existing = $db->fetchOne("SELECT * FROM cookie_preferences WHERE session_id = ?", [$session_id]);
    }
    
    if ($existing) {
        // Update existing
        if ($user_id) {
            $db->update('cookie_preferences', $data, 'user_id = ?', [$user_id]);
        } else {
            $db->update('cookie_preferences', $data, 'session_id = ?', [$session_id]);
        }
    } else {
        // Insert new
        $db->insert('cookie_preferences', $data);
    }
    
    echo json_encode(['success' => true, 'message' => 'Preferences saved']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save preferences']);
}
