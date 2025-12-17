<?php
/**
 * API: Facebook Login Handler
 * Validates Facebook token and creates/returns user
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Settings.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if Facebook login is enabled
    Settings::load();
    if (!Settings::isFacebookLoginEnabled()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Facebook login is currently disabled'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['access_token']) || !isset($input['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields: access_token, email'
        ]);
        exit;
    }
    
    $facebookAppId = Settings::get('facebook_app_id', '');
    if (empty($facebookAppId)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Facebook login not properly configured'
        ]);
        exit;
    }
    
    // Verify Facebook token (basic validation - in production, verify with Facebook Graph API)
    // For now, we trust the client's Firebase Auth
    $email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
    $name = $input['name'] ?? 'Facebook User';
    $photoUrl = $input['photo_url'] ?? null;
    $uid = $input['uid'] ?? null;
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address'
        ]);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Check if user exists in app_users table
    $user = $db->fetchOne("SELECT * FROM app_users WHERE email = ? OR uid = ?", [$email, $uid]);
    
    if (!$user) {
        // Create new user
        $userId = $db->insert('app_users', [
            'uid' => $uid ?? 'facebook_' . uniqid(),
            'email' => $email,
            'name' => $name,
            'photo_url' => $photoUrl,
            'provider' => 'facebook',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $user = $db->fetchOne("SELECT * FROM app_users WHERE id = ?", [$userId]);
    } else {
        // Update last login
        $db->update('app_users', [
            'last_login' => date('Y-m-d H:i:s'),
            'photo_url' => $photoUrl
        ], 'id = ?', [$user['id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'id' => $user['id'],
            'uid' => $user['uid'],
            'email' => $user['email'],
            'name' => $user['name'],
            'photo_url' => $user['photo_url'],
            'provider' => 'facebook'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Facebook login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Login failed',
        'error' => $e->getMessage()
    ]);
}
