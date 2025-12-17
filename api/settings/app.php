<?php
/**
 * API - Get App Settings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../../config/config.php';
require_once '../../includes/Database.php';
require_once '../../includes/Settings.php';

$db = Database::getInstance();

try {
    $settings = $db->fetchOne("SELECT * FROM app_settings LIMIT 1");
    
    // Load general settings (social media, email, etc.)
    Settings::load();
    
    if ($settings) {
        echo json_encode([
            'success' => true,
            'settings' => [
                'post_details_layout' => $settings['post_details_layout'] ?? 'random',
                'category_tile_layout' => $settings['category_tile_layout'] ?? 'grid',
                'video_tab' => (bool)($settings['video_tab'] ?? true),
                'audio_tab' => (bool)($settings['audio_tab'] ?? true),
                'home_categories' => $settings['home_categories'],
                // Social media links from settings table
                'social' => [
                    'fb' => Settings::get('facebook_url', ''),
                    'twitter' => Settings::get('twitter_url', ''),
                    'instagram' => Settings::get('instagram_url', ''),
                    'youtube' => Settings::get('youtube_url', '')
                ],
                // Contact and legal info
                'support_email' => Settings::get('site_email', ''),
                'privacy_url' => BASE_URL . '/page.php?slug=privacy-policy',
                'terms_of_use_url' => BASE_URL . '/page.php?slug=terms-conditions',
                'website' => BASE_URL
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No settings found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
