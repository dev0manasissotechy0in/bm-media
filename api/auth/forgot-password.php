<?php
/**
 * Forgot Password API
 * Sends OTP to user's email for password reset
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
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
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    $db = Database::getInstance();
    
    // Check if user exists
    $user = $db->fetchOne("SELECT id, email, full_name FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        // Don't reveal that user doesn't exist for security
        error_log("Forgot Password - User not found: $email");
        echo json_encode([
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset code.'
        ]);
        exit();
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Get OTP expiry time (default 10 minutes)
    $expiry_minutes = 10;
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_minutes} minutes"));
    
    // Check if OTP already exists
    $existing_otp = $db->fetchOne(
        "SELECT id FROM otp_codes WHERE email = ? AND purpose = 'password_reset' AND expires_at > NOW()",
        [$email]
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
            'purpose' => 'password_reset',
            'user_type' => 'user',
            'expires_at' => $expires_at
        ]);
    }
    
    // Send OTP email
    $emailSent = false;
    $emailError = null;
    
    try {
        $emailHelper = new EmailHelper('auth');
        $emailSent = $emailHelper->sendOTP($email, $otp, $user['full_name'], 'user', 'password_reset');
        
        if (!$emailSent) {
            throw new Exception('Failed to send password reset email');
        }
    } catch (Exception $e) {
        $emailError = $e->getMessage();
        error_log("Failed to send password reset email: " . $emailError);
        
        // Still return success to avoid revealing if user exists
        echo json_encode([
            'success' => true,
            'message' => 'If an account exists with this email, you will receive a password reset code.'
        ]);
        exit();
    }
    
    $response = [
        'success' => true,
        'message' => 'Password reset code sent to your email',
        'expires_in_minutes' => $expiry_minutes
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
