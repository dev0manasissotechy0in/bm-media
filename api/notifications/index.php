<?php
/**
 * Notification API Endpoints
 * Handles notification operations for mobile app and web
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../includes/NotificationManager.php';

$db = new Database();
$notificationManager = new NotificationManager($db);

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'register-device':
            // Register device token for push notifications
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $device_token = $data['device_token'] ?? '';
                $platform = $data['platform'] ?? 'android';
                $user_id = $data['user_id'] ?? null;
                $device_info = $data['device_info'] ?? null;
                
                if (empty($device_token)) {
                    throw new Exception('Device token is required');
                }
                
                // Check if token exists
                $existing = $db->fetchOne(
                    "SELECT id FROM device_tokens WHERE device_token = ?",
                    [$device_token]
                );
                
                if ($existing) {
                    // Update existing
                    $db->update('device_tokens', $existing['id'], [
                        'user_id' => $user_id,
                        'platform' => $platform,
                        'device_info' => $device_info,
                        'is_active' => 1,
                        'last_used' => date('Y-m-d H:i:s')
                    ]);
                    $token_id = $existing['id'];
                } else {
                    // Insert new
                    $token_id = $db->insert('device_tokens', [
                        'user_id' => $user_id,
                        'device_token' => $device_token,
                        'platform' => $platform,
                        'device_info' => $device_info,
                        'is_active' => 1,
                        'last_used' => date('Y-m-d H:i:s')
                    ]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Device registered successfully',
                    'token_id' => $token_id
                ]);
            }
            break;
            
        case 'unregister-device':
            // Unregister device token
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $device_token = $data['device_token'] ?? '';
                
                if (empty($device_token)) {
                    throw new Exception('Device token is required');
                }
                
                $db->query(
                    "UPDATE device_tokens SET is_active = 0 WHERE device_token = ?",
                    [$device_token]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Device unregistered successfully'
                ]);
            }
            break;
            
        case 'get-notifications':
            // Get user notifications
            if ($method === 'GET') {
                $user_id = $_GET['user_id'] ?? null;
                $device_token = $_GET['device_token'] ?? null;
                $limit = $_GET['limit'] ?? 50;
                $offset = $_GET['offset'] ?? 0;
                
                $notifications = $notificationManager->getUserNotifications(
                    $user_id,
                    $device_token,
                    (int)$limit,
                    (int)$offset
                );
                
                $unread_count = $notificationManager->getUnreadCount($user_id, $device_token);
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications,
                    'unread_count' => $unread_count,
                    'total' => count($notifications)
                ]);
            }
            break;
            
        case 'mark-read':
            // Mark notification as read
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $notification_id = $data['notification_id'] ?? 0;
                $device_token = $data['device_token'] ?? '';
                
                if (!$notification_id) {
                    throw new Exception('Notification ID is required');
                }
                
                $notificationManager->trackView($notification_id, $device_token);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read'
                ]);
            }
            break;
            
        case 'mark-all-read':
            // Mark all notifications as read
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $user_id = $data['user_id'] ?? null;
                $device_token = $data['device_token'] ?? null;
                
                if ($device_token) {
                    $db->query(
                        "UPDATE user_notifications SET is_read = 1, read_at = NOW() WHERE device_token = ? AND is_read = 0",
                        [$device_token]
                    );
                } elseif ($user_id) {
                    $db->query(
                        "UPDATE user_notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0",
                        [$user_id]
                    );
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'All notifications marked as read'
                ]);
            }
            break;
            
        case 'track-click':
            // Track notification click
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $notification_id = $data['notification_id'] ?? 0;
                $user_id = $data['user_id'] ?? null;
                $device_token = $data['device_token'] ?? null;
                
                if (!$notification_id) {
                    throw new Exception('Notification ID is required');
                }
                
                $notificationManager->trackClick($notification_id, $user_id, $device_token);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Click tracked successfully'
                ]);
            }
            break;
            
        case 'get-preferences':
            // Get user notification preferences
            if ($method === 'GET') {
                $user_id = $_GET['user_id'] ?? null;
                $platform = $_GET['platform'] ?? 'android';
                
                $prefs = $db->fetchOne(
                    "SELECT * FROM user_notification_preferences WHERE user_id = ? AND platform = ?",
                    [$user_id, $platform]
                );
                
                if (!$prefs) {
                    // Return default preferences
                    $prefs = [
                        'news_enabled' => true,
                        'breaking_enabled' => true,
                        'case_study_enabled' => true,
                        'case_study_update_enabled' => true,
                        'general_enabled' => true
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'preferences' => $prefs
                ]);
            }
            break;
            
        case 'update-preferences':
            // Update user notification preferences
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $user_id = $data['user_id'] ?? null;
                $platform = $data['platform'] ?? 'android';
                $preferences = $data['preferences'] ?? [];
                
                // Check if preferences exist
                $existing = $db->fetchOne(
                    "SELECT id FROM user_notification_preferences WHERE user_id = ? AND platform = ?",
                    [$user_id, $platform]
                );
                
                $update_data = [
                    'news_enabled' => $preferences['news_enabled'] ?? 1,
                    'breaking_enabled' => $preferences['breaking_enabled'] ?? 1,
                    'case_study_enabled' => $preferences['case_study_enabled'] ?? 1,
                    'case_study_update_enabled' => $preferences['case_study_update_enabled'] ?? 1,
                    'general_enabled' => $preferences['general_enabled'] ?? 1
                ];
                
                if ($existing) {
                    $db->update('user_notification_preferences', $existing['id'], $update_data);
                } else {
                    $update_data['user_id'] = $user_id;
                    $update_data['platform'] = $platform;
                    $db->insert('user_notification_preferences', $update_data);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Preferences updated successfully'
                ]);
            }
            break;
            
        case 'subscribe-web-push':
            // Subscribe to web push notifications
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $user_id = $data['user_id'] ?? null;
                $endpoint = $data['endpoint'] ?? '';
                $p256dh_key = $data['keys']['p256dh'] ?? '';
                $auth_token = $data['keys']['auth'] ?? '';
                $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
                
                if (empty($endpoint) || empty($p256dh_key) || empty($auth_token)) {
                    throw new Exception('Invalid subscription data');
                }
                
                // Check if subscription exists
                $existing = $db->fetchOne(
                    "SELECT id FROM web_push_subscriptions WHERE endpoint = ?",
                    [$endpoint]
                );
                
                if ($existing) {
                    $db->update('web_push_subscriptions', $existing['id'], [
                        'user_id' => $user_id,
                        'is_active' => 1
                    ]);
                    $sub_id = $existing['id'];
                } else {
                    $sub_id = $db->insert('web_push_subscriptions', [
                        'user_id' => $user_id,
                        'endpoint' => $endpoint,
                        'p256dh_key' => $p256dh_key,
                        'auth_token' => $auth_token,
                        'user_agent' => $user_agent,
                        'ip_address' => $ip_address,
                        'is_active' => 1
                    ]);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Subscribed to web push notifications',
                    'subscription_id' => $sub_id
                ]);
            }
            break;
            
        case 'unsubscribe-web-push':
            // Unsubscribe from web push notifications
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                $endpoint = $data['endpoint'] ?? '';
                
                if (empty($endpoint)) {
                    throw new Exception('Endpoint is required');
                }
                
                $db->query(
                    "UPDATE web_push_subscriptions SET is_active = 0 WHERE endpoint = ?",
                    [$endpoint]
                );
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Unsubscribed from web push notifications'
                ]);
            }
            break;
            
        case 'test-notification':
            // Send a test notification (for testing purposes)
            if ($method === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $result = $notificationManager->sendNotification([
                    'title' => $data['title'] ?? 'Test Notification',
                    'message' => $data['message'] ?? 'This is a test notification',
                    'type' => $data['type'] ?? 'general',
                    'tag' => $data['tag'] ?? 'test',
                    'target' => $data['target'] ?? 'all'
                ]);
                
                echo json_encode($result);
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
