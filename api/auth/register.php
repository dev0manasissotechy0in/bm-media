<?php
/**
 * Unified Register API for Web & Mobile App
 * Creates new user account with email/phone + password
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/EmailService.php';

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
    $phone = trim($input['phone'] ?? '');
    $password = trim($input['password'] ?? '');
    $full_name = trim($input['full_name'] ?? '');
    
    // Validation
    if (empty($email) && empty($phone)) {
        throw new Exception('Email or phone is required');
    }
    
    if (empty($password)) {
        throw new Exception('Password is required');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    $db = Database::getInstance();
    
    // Check if user already exists
    if (!empty($email)) {
        $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) {
            throw new Exception('Email already registered');
        }
    }
    
    if (!empty($phone)) {
        $existing = $db->fetchOne("SELECT id FROM users WHERE phone = ?", [$phone]);
        if ($existing) {
            throw new Exception('Phone number already registered');
        }
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Determine auth provider
    $authProvider = !empty($email) ? 'email' : 'phone';
    
    // Insert new user
    $db->query("
        INSERT INTO users (email, phone, password, full_name, auth_provider, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ", [
        $email ?: null,
        $phone ?: null,
        $hashedPassword,
        $full_name,
        $authProvider
    ]);
    
    $userId = $db->getConnection()->lastInsertId();
    
    // Generate email verification token if email provided
    if (!empty($email)) {
        $verificationToken = bin2hex(random_bytes(32));
        
        // Store verification token
        $db->query("
            INSERT INTO email_verification_tokens (user_id, token, expires_at, created_at)
            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())
        ", [$userId, $verificationToken]);
        
        // Send verification email
        $emailService = new EmailService();
        $verificationLink = BASE_URL . '/verify-email.php?token=' . $verificationToken;
        
        $emailBody = file_get_contents(__DIR__ . '/../../email-templates/verify-email.html');
        $emailBody = str_replace('{{USER_NAME}}', htmlspecialchars($full_name), $emailBody);
        $emailBody = str_replace('{{VERIFICATION_LINK}}', $verificationLink, $emailBody);
        
        $emailService->sendEmail('auth', $email, 'Verify Your Email Address', $emailBody, $full_name);
    }
    
    // Generate session token
    $token = bin2hex(random_bytes(32));
    
    // Store session token
    $db->query("
        INSERT INTO user_sessions (user_id, token, expires_at, created_at) 
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
    ", [$userId, $token]);
    
    // Get user data
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    
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
        'message' => 'Registration successful',
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
