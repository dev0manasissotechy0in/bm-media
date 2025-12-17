<?php
/**
 * API: Get App Configuration including social login settings
 * Returns settings controlled by admin panel
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Settings.php';

try {
    Settings::load();
    
    $response = [
        'success' => true,
        'data' => [
            'social_login' => [
                'google_enabled' => Settings::isGoogleLoginEnabled(),
                'facebook_enabled' => Settings::isFacebookLoginEnabled(),
                'google_client_id' => Settings::get('google_client_id', ''),
            ],
            'features' => [
                'otp_enabled' => Settings::isOtpEnabled(),
                'comments_enabled' => Settings::get('enable_comments', '1') === '1',
                'user_registration_enabled' => Settings::get('enable_user_registration', '1') === '1',
            ],
            'app_info' => [
                'site_name' => Settings::get('site_name', 'News App'),
                'site_tagline' => Settings::get('site_tagline', ''),
            ]
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load app configuration',
        'error' => $e->getMessage()
    ]);
}
