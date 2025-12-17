<?php
/**
 * OTP System Diagnostic Tool
 * Check OTP configuration, database, and email settings
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Settings.php';
require_once __DIR__ . '/../../includes/Database.php';

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

try {
    $db = Database::getInstance();
    
    // 1. Check if otp_codes table exists
    $tableCheck = $db->fetchOne("SHOW TABLES LIKE 'otp_codes'");
    $diagnostics['checks']['otp_table_exists'] = !empty($tableCheck);
    
    if ($diagnostics['checks']['otp_table_exists']) {
        // Check table structure
        $columns = $db->fetchAll("SHOW COLUMNS FROM otp_codes");
        $diagnostics['otp_table_columns'] = array_column($columns, 'Field');
        
        // Count active OTP codes
        $activeCount = $db->fetchOne("SELECT COUNT(*) as count FROM otp_codes WHERE expires_at > NOW() AND is_used = 0");
        $diagnostics['active_otp_count'] = $activeCount['count'] ?? 0;
        
        // Get recent OTPs (last 5, hide actual codes)
        $recentOtps = $db->fetchAll(
            "SELECT id, email, purpose, user_type, expires_at, is_used, verified, created_at 
             FROM otp_codes 
             ORDER BY created_at DESC 
             LIMIT 5"
        );
        $diagnostics['recent_otps'] = $recentOtps;
    }
    
    // 2. Check OTP settings
    $diagnostics['settings'] = [
        'otp_enabled' => Settings::get('otp_enabled', '0'),
        'otp_expiry_minutes' => Settings::get('otp_expiry_minutes', '10'),
    ];
    
    // 3. Check SMTP configuration for auth emails (from smtp-multi-config.php)
    $smtp_settings = [
        'auth_smtp_enabled' => Settings::get('auth_smtp_enabled', '0'),
        'auth_smtp_host' => Settings::get('auth_smtp_host', ''),
        'auth_smtp_port' => Settings::get('auth_smtp_port', ''),
        'auth_smtp_username' => Settings::get('auth_smtp_username', ''),
        'auth_smtp_password' => !empty(Settings::get('auth_smtp_password', '')) ? '***SET***' : 'NOT SET',
        'auth_smtp_encryption' => Settings::get('auth_smtp_encryption', ''),
        'auth_smtp_from_email' => Settings::get('auth_smtp_from_email', ''),
        'auth_smtp_from_name' => Settings::get('auth_smtp_from_name', ''),
    ];
    $diagnostics['smtp_config'] = $smtp_settings;
    
    // Check if SMTP is configured
    $smtp_configured = !empty($smtp_settings['auth_smtp_host']) && 
                      !empty($smtp_settings['auth_smtp_username']) && 
                      !empty(Settings::get('auth_smtp_password', ''));
    $diagnostics['checks']['smtp_configured'] = $smtp_configured;
    
    // 4. Check if PHPMailer is available
    // Need to load autoload first
    if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
        require_once __DIR__ . '/../../vendor/autoload.php';
    }
    $diagnostics['checks']['phpmailer_available'] = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
    
    // 5. Check if EmailHelper exists
    $diagnostics['checks']['email_helper_exists'] = file_exists(__DIR__ . '/../../includes/EmailHelper.php');
    
    // 6. Overall status
    $allChecks = $diagnostics['checks'];
    $diagnostics['overall_status'] = all_true($allChecks) ? 'OK' : 'ISSUES FOUND';
    
    // 7. Recommendations
    $recommendations = [];
    if (!$allChecks['otp_table_exists']) {
        $recommendations[] = 'Run migration: database/migration_advanced_features.sql';
    }
    if (!$allChecks['smtp_configured']) {
        $recommendations[] = 'Configure Auth SMTP in admin/smtp-multi-config.php';
    }
    if (!$allChecks['phpmailer_available']) {
        $recommendations[] = 'Install PHPMailer: composer require phpmailer/phpmailer';
    }
    if ($diagnostics['settings']['otp_enabled'] !== '1') {
        $recommendations[] = 'Enable OTP in admin/settings.php under Security tab';
    }
    
    $diagnostics['recommendations'] = $recommendations;
    
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}

function all_true($array) {
    foreach ($array as $value) {
        if (!$value) return false;
    }
    return true;
}
