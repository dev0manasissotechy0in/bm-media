<?php
/**
 * Verify OTP and Login/Register User
 * This endpoint verifies the OTP and logs in or registers the user
 */

// Disable HTML error display for API responses
ini_set('display_errors', 0);
error_reporting(0); // Suppress all errors for clean JSON output

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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $otp = trim($input['otp'] ?? '');
    $user_type = trim($input['user_type'] ?? 'user');
    $name = trim($input['name'] ?? '');
    $fcm_token = isset($input['fcm_token']) && $input['fcm_token'] ? trim($input['fcm_token']) : null;
    
    error_log("Verify OTP - Email: $email, OTP: $otp, User Type: $user_type");
    
    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    if (empty($otp) || strlen($otp) !== 6) {
        throw new Exception('Invalid OTP code');
    }
    
    if (!in_array($user_type, ['user', 'author', 'admin'])) {
        throw new Exception('Invalid user type');
    }
    
    $db = Database::getInstance();
    
    // Debug: Check what OTPs exist for this email
    $all_otps = $db->fetchAll(
        "SELECT id, otp_code, purpose, user_type, is_used, verified, expires_at, created_at 
         FROM otp_codes WHERE email = ? ORDER BY created_at DESC LIMIT 5",
        [$email]
    );
    error_log("All OTPs for $email: " . json_encode($all_otps));
    
    // Verify OTP
    $otp_record = $db->fetchOne(
        "SELECT * FROM otp_codes 
         WHERE email = ? AND otp_code = ? AND purpose = 'login' AND user_type = ? 
         AND is_used = 0 AND verified = 0 AND expires_at > NOW()
         ORDER BY created_at DESC LIMIT 1",
        [$email, $otp, $user_type]
    );
    
    error_log("OTP Record Found: " . ($otp_record ? 'YES' : 'NO'));
    
    if (!$otp_record) {
        error_log("OTP verification failed - OTP: $otp, Email: $email, User Type: $user_type");
        throw new Exception('Invalid or expired OTP');
    }
    
    // Mark OTP as used and verified
    $db->query(
        "UPDATE otp_codes SET is_used = 1, verified = 1 WHERE id = ?",
        [$otp_record['id']]
    );
    
    // Determine which table to use based on user type
    $table = $user_type === 'admin' ? 'admin_users' : ($user_type === 'author' ? 'reporters' : 'users');
    
    // Check if user exists
    $user = $db->fetchOne(
        "SELECT * FROM {$table} WHERE email = ?",
        [$email]
    );
    
    if (!$user) {
        // Auto-register new user (only for 'user' type)
        if ($user_type === 'user') {
            // Insert new user
            $user_id = $db->insert('users', [
                'email' => $email,
                'full_name' => $name ?: explode('@', $email)[0],
                'status' => 'active',
                'email_verified' => 1,
                'auth_provider' => 'email',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Fetch newly created user
            $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        } else {
            throw new Exception('User not found. Please contact administrator.');
        }
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        throw new Exception('Your account is not active. Please contact support.');
    }
    
    // Update FCM token if provided
    if ($fcm_token) {
        $db->query(
            "UPDATE {$table} SET fcm_token = ?, updated_at = NOW() WHERE id = ?",
            [$fcm_token, $user['id']]
        );
    }
    
    // Update last login
    $db->query(
        "UPDATE {$table} SET last_login = NOW() WHERE id = ?",
        [$user['id']]
    );
    
    // Create session token
    $token = bin2hex(random_bytes(32)); // Generate secure token
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days')); // Token valid for 30 days
    
    // Try to create/update session in user_sessions table
    try {
        // Check if table exists
        $tableExists = $db->fetchOne("SHOW TABLES LIKE 'user_sessions'");
        
        if ($tableExists) {
            // Delete any existing sessions for this user
            $db->query("DELETE FROM user_sessions WHERE user_id = ?", [$user['id']]);
            
            // Insert new session
            $db->insert('user_sessions', [
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expires_at,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    } catch (Exception $sessionError) {
        // If session table fails, just log it and continue with token
        error_log("Session creation warning: " . $sessionError->getMessage());
    }
    
    // Prepare user data for response
    $user_data = [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'] ?? $user['name'] ?? explode('@', $user['email'])[0],
        'email' => $user['email'],
        'phone' => $user['phone'] ?? null,
        'profile_photo' => isset($user['profile_photo']) && $user['profile_photo'] 
            ? (strpos($user['profile_photo'], 'http') === 0 ? $user['profile_photo'] : BASE_URL . '/' . ltrim($user['profile_photo'], '/'))
            : null,
        'user_type' => $user_type,
        'status' => $user['status'],
        'email_verified' => (bool)($user['email_verified'] ?? 1),
        'auth_provider' => $user['auth_provider'] ?? 'email'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user_data,
        'token' => $token
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
