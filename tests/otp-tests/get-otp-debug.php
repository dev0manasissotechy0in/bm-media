<?php
/**
 * Get Latest OTP for Email (Development/Testing Only)
 * This bypasses email and retrieves OTP directly from database
 * USE ONLY FOR TESTING - REMOVE IN PRODUCTION
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $email = trim($_GET['email'] ?? '');
    
    if (empty($email)) {
        throw new Exception('Email parameter is required');
    }
    
    $db = Database::getInstance();
    
    // Get the latest valid OTP for this email
    $otp = $db->fetchOne(
        "SELECT otp_code, expires_at, created_at 
         FROM otp_codes 
         WHERE email = ? 
         AND expires_at > NOW() 
         AND is_used = 0
         ORDER BY created_at DESC 
         LIMIT 1",
        [$email]
    );
    
    if (!$otp) {
        throw new Exception('No valid OTP found for this email. Please request a new OTP.');
    }
    
    echo json_encode([
        'success' => true,
        'email' => $email,
        'otp' => $otp['otp_code'],
        'expires_at' => $otp['expires_at'],
        'created_at' => $otp['created_at'],
        'warning' => '⚠️ This endpoint should ONLY be used for testing. Remove in production!'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
