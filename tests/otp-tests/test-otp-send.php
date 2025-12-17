<?php
/**
 * Test OTP Sending - Direct Test
 * Use this to test if OTP can be sent successfully
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/EmailHelper.php';
require_once __DIR__ . '/../../includes/Settings.php';

$testEmail = $_GET['email'] ?? 'test@example.com';

try {
    $db = Database::getInstance();
    
    // Step 1: Check if otp_codes table exists
    $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'otp_codes'");
    if (!$tableCheck) {
        throw new Exception('❌ Table otp_codes does not exist. Run database/migration_advanced_features.sql');
    }
    
    // Step 2: Generate test OTP
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    echo json_encode([
        'step' => 'OTP Generated',
        'otp' => $otp,
        'email' => $testEmail,
        'expires_at' => $expires_at
    ]) . "\n\n";
    
    // Step 3: Insert into database
    $insert_id = $db->insert('otp_codes', [
        'email' => $testEmail,
        'otp_code' => $otp,
        'purpose' => 'login',
        'user_type' => 'user',
        'expires_at' => $expires_at
    ]);
    
    if (!$insert_id) {
        throw new Exception('❌ Failed to insert OTP into database');
    }
    
    echo json_encode([
        'step' => 'Database Insert',
        'status' => 'Success',
        'insert_id' => $insert_id
    ]) . "\n\n";
    
    // Step 4: Check SMTP configuration (from smtp-multi-config.php)
    $smtp_host = Settings::get('auth_smtp_host', '');
    $smtp_username = Settings::get('auth_smtp_username', '');
    $smtp_password = Settings::get('auth_smtp_password', '');
    
    echo json_encode([
        'step' => 'SMTP Configuration Check',
        'smtp_configured' => !empty($smtp_host) && !empty($smtp_username) && !empty($smtp_password),
        'host' => $smtp_host ?: 'Not configured',
        'username' => $smtp_username ?: 'Not configured',
        'password_set' => !empty($smtp_password)
    ]) . "\n\n";
    
    // Final summary - OTP is ready to use
    echo json_encode([
        'test_complete' => true,
        'success' => true,
        'otp_code' => $otp,
        'email' => $testEmail,
        'expires_at' => $expires_at,
        'insert_id' => $insert_id,
        'message' => '✅ OTP Generated and Saved to Database',
        'note' => 'Use this OTP to test verification in your app',
        'smtp_note' => empty($smtp_host) ? 'Configure SMTP in admin/smtp-multi-config.php to send emails' : 'SMTP is configured. Use register.php for actual email sending.'
    ]) . "\n\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
