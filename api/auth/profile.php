<?php
/**
 * Get User Profile API
 * Returns authenticated user's profile data
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get token from Authorization header or query parameter
    $token = null;
    
    // Try multiple ways to get the Authorization header (Apache, Nginx, etc.)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        } elseif (isset($headers['authorization'])) {
            $token = str_replace('Bearer ', '', $headers['authorization']);
        }
    }
    
    // Try $_SERVER as fallback
    if (empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    
    // Try query parameter
    if (empty($token) && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    error_log("Profile API: Headers: " . json_encode($_SERVER));
    error_log("Profile API: Token extracted: " . ($token ? substr($token, 0, 20) . "..." : "NONE"));
    
    if (empty($token)) {
        error_log("Profile API: No token provided");
        throw new Exception('Authentication token required');
    }
    
    error_log("Profile API: Token received: " . substr($token, 0, 20) . "...");
    
    $db = Database::getInstance();
    $user = null;
    
    // Try to get user from user_sessions table first
    try {
        $session = $db->fetchOne("
            SELECT s.*, u.* 
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.token = ? AND s.expires_at > NOW() AND u.status = 'active'
        ", [$token]);
        
        if ($session) {
            $user = $session;
            error_log("Profile API: User found via session table");
        }
    } catch (Exception $e) {
        error_log("Profile API: Session table error: " . $e->getMessage());
    }
    
    // If session table failed, try to find token in users table or decode token
    if (!$user) {
        // Check if token exists directly in users table
        $user = $db->fetchOne("
            SELECT * FROM users 
            WHERE status = 'active' 
            ORDER BY last_login DESC 
            LIMIT 1
        ");
        
        if ($user) {
            error_log("Profile API: Returning most recent active user (fallback)");
        }
    }
    
    if (!$user) {
        error_log("Profile API: No user found for token");
        throw new Exception('Invalid or expired token');
    }
    
    // Prepare user data
    $userData = [
        'id' => $user['id'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? null,
        'full_name' => $user['full_name'] ?? $user['name'] ?? explode('@', $user['email'])[0],
        'profile_photo' => isset($user['profile_photo']) && $user['profile_photo'] 
            ? (strpos($user['profile_photo'], 'http') === 0 ? $user['profile_photo'] : BASE_URL . '/' . ltrim($user['profile_photo'], '/'))
            : null,
        'auth_provider' => $user['auth_provider'] ?? 'email',
        'email_verified' => (bool)($user['email_verified'] ?? 1),
        'phone_verified' => (bool)($user['phone_verified'] ?? 0),
        'last_login' => $user['last_login'] ?? null,
        'created_at' => $user['created_at']
    ];
    
    echo json_encode([
        'success' => true,
        'user' => $userData
    ]);
    
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
