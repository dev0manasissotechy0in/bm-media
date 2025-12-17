<?php
/**
 * Send Test Push Notification to Mobile App
 * Admin API for testing FCM notifications
 */

require_once '../../config/config.php';
require_once '../../includes/Settings.php';

header('Content-Type: application/json');

// Check authentication (session already started in config.php)
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['author_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if push notifications are enabled
$push_enabled = Settings::get('enable_push_notifications', '0') === '1';
if (!$push_enabled) {
    echo json_encode([
        'success' => false,
        'message' => 'Push notifications are disabled. Please enable them in Settings > Notifications.'
    ]);
    exit;
}

// Get FCM server key
$fcm_key = Settings::get('fcm_server_key', '');
if (empty($fcm_key)) {
    echo json_encode([
        'success' => false,
        'message' => 'FCM Server Key not configured. Please add it in Settings > Notifications.'
    ]);
    exit;
}

// Load FCM Helper (Lite version - no composer needed)
try {
    require_once INCLUDES_PATH . '/FCMv1HelperLite.php';
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load FCM library: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $title = $data['title'] ?? 'Test Notification 📱';
    $body = $data['body'] ?? 'This is a test push notification from your admin panel!';
    $image = $data['image'] ?? '';
    $topic = $data['topic'] ?? 'all'; // Default to 'all' topic
    
    // Prepare notification data
    $notificationData = [
        'notification_type' => 'test',
        'type' => 'custom',
        'title' => $title,
        'body' => $body,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($image)) {
        $notificationData['image_url'] = $image;
    }
    
    // Initialize FCM v1 and send
    $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
    $response = $fcm->sendToTopic($topic, $title, $body, $notificationData);
    
    if ($response) {
        $responseData = json_decode($response, true);
        
        // Log the notification send
        $db = Database::getInstance();
        $db->insert('notification_logs', [
            'type' => 'test',
            'title' => $title,
            'body' => $body,
            'topic' => $topic,
            'sent_by' => $_SESSION['admin_id'] ?? $_SESSION['author_id'],
            'fcm_response' => $response,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Test notification sent successfully!',
            'details' => [
                'title' => $title,
                'body' => $body,
                'topic' => $topic,
                'sent_at' => date('Y-m-d H:i:s'),
                'fcm_response' => $responseData
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send notification. Please check your FCM Server Key.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Test Notification Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error sending notification: ' . $e->getMessage()
    ]);
}
