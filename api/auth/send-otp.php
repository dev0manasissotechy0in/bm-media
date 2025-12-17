<?php
/**
 * Send OTP to Email for Login/Registration
 * This endpoint sends a 6-digit OTP to the user's email address
 */

require_once __DIR__ . '/../api_init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/EmailHelper.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error_response('Method not allowed', 405);
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $name = trim($input['name'] ?? '');
    $user_type = trim($input['user_type'] ?? 'user'); // 'user', 'author', or 'admin'
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Validate user type
    if (!in_array($user_type, ['user', 'author', 'admin'])) {
        throw new Exception('Invalid user type');
    }
    
    $db = Database::getInstance();
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Get OTP expiry time from settings (default 10 minutes)
    $expiry_minutes = 10;
    $expiry_setting = $db->fetchOne(
        "SELECT setting_value FROM settings WHERE setting_key = 'otp_expiry_minutes'"
    );
    if ($expiry_setting) {
        $expiry_minutes = intval($expiry_setting['setting_value']);
    }
    
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));
    
    // Check if OTP already exists for this email and purpose
    $existing_otp = $db->fetchOne(
        "SELECT id FROM otp_codes WHERE email = ? AND purpose = 'login' AND user_type = ? AND expires_at > NOW()",
        [$email, $user_type]
    );
    
    if ($existing_otp) {
        // Update existing OTP
        $db->query(
            "UPDATE otp_codes SET otp_code = ?, expires_at = ?, created_at = NOW(), is_used = 0, verified = 0 
             WHERE id = ?",
            [$otp, $expires_at, $existing_otp['id']]
        );
    } else {
        // Insert new OTP
        $db->insert('otp_codes', [
            'email' => $email,
            'otp_code' => $otp,
            'purpose' => 'login',
            'user_type' => $user_type,
            'expires_at' => $expires_at
        ]);
    }
    
    // Send OTP email
    $emailSent = false;
    $emailError = null;
    
    try {
        $emailHelper = new EmailHelper('auth');
        $emailSent = $emailHelper->sendOTP($email, $otp, $name, $user_type);
    } catch (Exception $e) {
        $emailError = $e->getMessage();
        error_log("Email sending failed: " . $emailError);
        
        // If email fails, return error
        throw new Exception('Failed to send OTP email. Please check your email configuration.');
    }
    
    // Prepare response
    $response = [
        'message' => 'OTP sent successfully to your email',
        'expires_in_minutes' => $expiry_minutes
    ];
    
    // In debug mode, return the OTP (only for development)
    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $response['debug'] = [
            'otp' => $otp,
            'expires_at' => $expires_at
        ];
    }
    
    send_success_response($response);
    
} catch (Exception $e) {
    send_error_response($e->getMessage(), 400);
}
