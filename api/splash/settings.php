<?php
/**
 * SPLASH SCREEN SETTINGS API
 * Returns splash screen configuration for mobile app
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Get platform from query parameter
    $platform = isset($_GET['platform']) ? $_GET['platform'] : 'all';
    $isFirstTime = isset($_GET['first_time']) && $_GET['first_time'] === 'true';
    
    // Fetch splash screen settings
    $stmt = $pdo->prepare("SELECT * FROM splash_screen_settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        echo json_encode([
            'success' => false,
            'error' => 'SETTINGS_NOT_FOUND',
            'message' => 'Splash screen settings not configured'
        ]);
        exit;
    }
    
    // Determine which splash to show
    $showDynamic = false;
    $dynamicReason = '';
    
    if ($settings['is_dynamic_enabled']) {
        // Check platform targeting
        $targetPlatforms = $settings['target_platforms'];
        $platformMatch = ($targetPlatforms === 'all' || $targetPlatforms === $platform);
        
        // Check new users only flag
        $userTargetMatch = !$settings['target_new_users_only'] || $isFirstTime;
        
        // Check schedule
        $scheduleMatch = true;
        if ($settings['is_scheduled']) {
            $now = time();
            $start = $settings['schedule_start_date'] ? strtotime($settings['schedule_start_date']) : 0;
            $end = $settings['schedule_end_date'] ? strtotime($settings['schedule_end_date']) : PHP_INT_MAX;
            $scheduleMatch = ($now >= $start && $now <= $end);
            
            if (!$scheduleMatch) {
                $dynamicReason = 'Outside scheduled period';
            }
        }
        
        if (!$platformMatch) {
            $dynamicReason = 'Platform not targeted';
        } elseif (!$userTargetMatch) {
            $dynamicReason = 'Not a new user';
        }
        
        $showDynamic = $platformMatch && $userTargetMatch && $scheduleMatch;
        
        // Increment impression count if showing dynamic
        if ($showDynamic) {
            $updateStmt = $pdo->prepare("UPDATE splash_screen_settings SET impression_count = impression_count + 1 WHERE id = 1");
            $updateStmt->execute();
        }
    } else {
        $dynamicReason = 'Dynamic splash disabled';
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'show_dynamic' => $showDynamic,
        'dynamic_reason' => $dynamicReason,
        'default_splash' => [
            'logo' => $settings['default_logo'],
            'background_color' => $settings['default_background_color'],
            'text_color' => $settings['default_text_color'],
            'tagline' => $settings['default_tagline'],
            'animation_type' => $settings['default_animation_type']
        ]
    ];
    
    if ($showDynamic) {
        $response['dynamic_splash'] = [
            'image' => $settings['dynamic_image'],
            'background_color' => $settings['dynamic_background_color'],
            'text_color' => $settings['dynamic_text_color'],
            'title' => $settings['dynamic_title'],
            'subtitle' => $settings['dynamic_subtitle'],
            'button_text' => $settings['dynamic_button_text'],
            'button_action' => $settings['dynamic_button_action'],
            'display_duration' => (int)$settings['dynamic_display_duration']
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database error in splash screen API: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to fetch splash screen settings'
    ]);
} catch (Exception $e) {
    error_log("Error in splash screen API: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'SERVER_ERROR',
        'message' => 'An error occurred'
    ]);
}
