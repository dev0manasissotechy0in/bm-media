<?php
/**
 * Simple OTP Test - No Email Required
 * This bypasses email sending for pure database testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['step' => 'init', 'errors' => []];

try {
    // Step 1: Load config
    $response['step'] = 'loading_config';
    require_once __DIR__ . '/../../config/config.php';
    $response['config_loaded'] = true;
    $response['db_info'] = [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER
    ];
    
    // Step 2: Load Database class
    $response['step'] = 'loading_database_class';
    require_once __DIR__ . '/../../includes/Database.php';
    $response['database_class_loaded'] = true;
    
    // Step 3: Connect to database
    $response['step'] = 'connecting_to_database';
    $db = Database::getInstance();
    $response['database_connected'] = true;
    
    // Step 4: Parse input
    $response['step'] = 'parsing_input';
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    $response['email'] = $email;
    
    // Step 5: Generate OTP
    $response['step'] = 'generating_otp';
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $response['otp_generated'] = true;
    
    // Step 6: Calculate expiry
    $response['step'] = 'calculating_expiry';
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $response['expires_at'] = $expires_at;
    
    // Step 7: Check if OTP table exists
    $response['step'] = 'checking_table';
    try {
        $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'otp_codes'");
        if (!$tableCheck) {
            throw new Exception('otp_codes table does not exist! Run fix_auth_system.sql migration.');
        }
        $response['table_exists'] = true;
    } catch (Exception $e) {
        $response['table_error'] = $e->getMessage();
        throw new Exception('Table check failed: ' . $e->getMessage());
    }
    
    // Step 8: Check for existing OTP
    $response['step'] = 'checking_existing_otp';
    $existing = $db->fetchOne(
        "SELECT id FROM otp_codes WHERE email = ? AND purpose = 'login' AND expires_at > NOW()",
        [$email]
    );
    $response['existing_otp'] = $existing ? 'found' : 'none';
    
    // Step 9: Insert or update OTP
    $response['step'] = 'saving_otp';
    if ($existing) {
        $db->query(
            "UPDATE otp_codes SET otp_code = ?, expires_at = ?, created_at = NOW(), is_used = 0 WHERE id = ?",
            [$otp, $expires_at, $existing['id']]
        );
        $response['operation'] = 'updated';
    } else {
        $db->insert('otp_codes', [
            'email' => $email,
            'otp_code' => $otp,
            'purpose' => 'login',
            'user_type' => 'user',
            'expires_at' => $expires_at
        ]);
        $response['operation'] = 'inserted';
    }
    
    $response['step'] = 'complete';
    $response['success'] = true;
    $response['message'] = 'OTP generated successfully (No email sent - TEST MODE)';
    $response['otp'] = $otp;
    $response['note'] = 'Use this OTP to test verify endpoint';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT);
