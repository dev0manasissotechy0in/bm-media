<?php
/**
 * Get App Branding API
 * Returns branding settings for Flutter app (app name, logo URLs, fonts)
 */

require_once __DIR__ . '/api_init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Settings.php';

try {
    // Fetch branding settings
    $branding = [
        'app_name' => Settings::get('app_name', 'Brackodd Media'),
        'app_tagline' => Settings::get('app_tagline', 'Your trusted news source'),
        'logo_text' => Settings::get('logo_text', ''),
        'footer_text' => Settings::get('footer_text', ''),
        
        // Logo paths (relative to app assets)
        'logo_light' => 'assets/images/logo.png',
        'logo_dark' => 'assets/images/logoDark.png',
        
        // Font configuration
        'font_family' => 'Westack',
        'font_path' => 'assets/fonts/westack',
        
        // App version info
        'app_version' => Settings::get('app_version', '1.0.0'),
        'min_app_version' => Settings::get('min_app_version', '1.0.0'),
        'force_update' => Settings::get('force_update', '0') === '1',
        
        // Feature flags
        'show_splash' => Settings::get('show_splash', '1') === '1',
        'enable_dark_mode' => Settings::get('enable_dark_mode', '1') === '1',
        
        // Splash screen configuration
        'splash_duration' => (int)Settings::get('splash_duration', '3'),
        'splash_logo_url' => Settings::get('splash_logo_url', 'assets/images/logo.png'),
        'splash_background_color' => Settings::get('splash_background_color', '#FFFFFF'),
        'splash_text' => Settings::get('splash_text', ''),
        
        // Timestamp for caching
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    send_success_response($branding);
    
} catch (Exception $e) {
    send_error_response('Failed to fetch branding settings: ' . $e->getMessage(), 500);
}
