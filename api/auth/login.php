<?php
/**
 * Unified Login API for Web & Mobile App
 * Supports email/phone + password authentication
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $rawInput = file_get_contents('php://input');
    error_log("Login API - Raw Input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Login API - Decoded Input: " . json_encode($input));
    
    // Support both 'identifier' and 'email' for backward compatibility
    $identifier = trim($input['identifier'] ?? $input['email'] ?? ''); // Email or phone
    $password = trim($input['password'] ?? '');
    
    error_log("Login API - Identifier: $identifier, Password length: " . strlen($password));
    
    if (empty($identifier) || empty($password)) {
        throw new Exception('Email/Phone and password are required');
    }
    
    $db = Database::getInstance();
    
    // Determine if identifier is email or phone
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    error_log("Login API - Is Email: " . ($isEmail ? 'Yes' : 'No'));
    
    // Find user in users table
    if ($isEmail) {
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$identifier]);
    } else {
        $user = $db->fetchOne("SELECT * FROM users WHERE phone = ? AND status = 'active'", [$identifier]);
    }
    
    error_log("Login API - User found: " . ($user ? 'Yes (ID: ' . $user['id'] . ')' : 'No'));
    
    if (!$user) {
        error_log("Login API - ERROR: User not found for identifier: $identifier");
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error_code' => 'USER_NOT_FOUND',
            'message' => 'No account found with this email/phone. Please register first.'
        ]);
        exit();
    }
    
    // Check if password field exists and is not empty
    if (empty($user['password'])) {
        error_log("Login API - ERROR: User has no password set (may be social login only)");
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error_code' => 'NO_PASSWORD_SET',
            'message' => 'This account uses social login or email OTP. Please use the appropriate login method.'
        ]);
        exit();
    }
    
    error_log("Login API - Password hash from DB: " . substr($user['password'], 0, 20) . '...');
    
    // Verify password
    $passwordMatch = password_verify($password, $user['password']);
    error_log("Login API - Password verify result: " . ($passwordMatch ? 'MATCH' : 'NO MATCH'));
    
    if (!$passwordMatch) {
        error_log("Login API - ERROR: Password verification failed for user ID: " . $user['id']);
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error_code' => 'INVALID_PASSWORD',
            'message' => 'Invalid password. Please try again.'
        ]);
        exit();
    }
    
    // Check email verification
    if (!$user['email_verified']) {
        error_log("Login API - ERROR: Email not verified for user ID: " . $user['id']);
        throw new Exception('Please verify your email before logging in');
    }
    
    // Update last login
    $db->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Generate session token (simple token for demo - use JWT in production)
    $token = bin2hex(random_bytes(32));
    
    // Store session token in database (create sessions table if needed)
    $db->query("
        INSERT INTO user_sessions (user_id, token, expires_at, created_at) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
        ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at)
    ", [$user['id'], $token]);
    
    // Prepare user data for response
    $userData = [
        'id' => $user['id'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'full_name' => $user['full_name'],
        'profile_photo' => $user['profile_photo'] ? BASE_URL . '/' . ltrim($user['profile_photo'], '/') : null,
        'auth_provider' => $user['auth_provider'],
        'email_verified' => (bool)$user['email_verified'],
        'phone_verified' => (bool)$user['phone_verified']
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $userData
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
