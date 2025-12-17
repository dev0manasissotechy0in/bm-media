<?php
/**
 * Reset Password API
 * Verifies OTP and resets user password
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = trim($input['email'] ?? '');
    $otp = trim($input['otp'] ?? '');
    $new_password = trim($input['new_password'] ?? '');
    
    // Validate inputs
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    if (empty($otp) || strlen($otp) !== 6) {
        throw new Exception('Invalid OTP code');
    }
    
    if (empty($new_password) || strlen($new_password) < 6) {
        throw new Exception('Password must be at least 6 characters');
    }
    
    $db = Database::getInstance();
    
    // Verify OTP
    $otp_record = $db->fetchOne(
        "SELECT * FROM otp_codes 
         WHERE email = ? AND otp_code = ? AND purpose = 'password_reset' 
         AND is_used = 0 AND verified = 0 AND expires_at > NOW()
         ORDER BY created_at DESC LIMIT 1",
        [$email, $otp]
    );
    
    if (!$otp_record) {
        throw new Exception('Invalid or expired OTP');
    }
    
    // Check if user exists
    $user = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    
    // Update password
    $db->query(
        "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
        [$hashed_password, $user['id']]
    );
    
    // Mark OTP as used
    $db->query(
        "UPDATE otp_codes SET is_used = 1, verified = 1 WHERE id = ?",
        [$otp_record['id']]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Password reset successfully. You can now login with your new password.'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
