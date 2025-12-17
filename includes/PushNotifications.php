<?php
/**
 * Push Notifications Helper Functions
 * Server-side push notification management
 */

require_once __DIR__ . '/../vendor/autoload.php'; // Assuming web-push-php library

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

/**
 * Send push notification to subscribers
 */
function sendPushNotification($notification_data, $target = 'all') {
    $db = Database::getInstance();
    
    // Get VAPID keys from config
    $vapid_public_key = defined('VAPID_PUBLIC_KEY') ? VAPID_PUBLIC_KEY : '';
    $vapid_private_key = defined('VAPID_PRIVATE_KEY') ? VAPID_PRIVATE_KEY : '';
    
    if (empty($vapid_public_key) || empty($vapid_private_key)) {
        return ['success' => false, 'error' => 'VAPID keys not configured'];
    }
    
    $auth = [
        'VAPID' => [
            'subject' => SITE_URL,
            'publicKey' => $vapid_public_key,
            'privateKey' => $vapid_private_key,
        ]
    ];
    
    $webPush = new WebPush($auth);
    
    // Get active subscriptions
    $subscriptions = $db->fetchAll("
        SELECT * FROM push_subscriptions 
        WHERE status = 'active'
    ");
    
    if (empty($subscriptions)) {
        return ['success' => false, 'error' => 'No active subscriptions'];
    }
    
    $sent_count = 0;
    $failed_count = 0;
    
    foreach ($subscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh_key'],
            'authToken' => $sub['auth_key'],
        ]);
        
        try {
            $webPush->sendOneNotification(
                $subscription,
                json_encode($notification_data)
            );
            $sent_count++;
            
            // Track notification send
            $db->execute("
                INSERT INTO push_notification_logs 
                (subscription_id, notification_data, status, sent_at) 
                VALUES (?, ?, 'sent', NOW())
            ", [$sub['id'], json_encode($notification_data)]);
            
        } catch (Exception $e) {
            $failed_count++;
            
            // Log failure
            $db->execute("
                INSERT INTO push_notification_logs 
                (subscription_id, notification_data, status, error_message, sent_at) 
                VALUES (?, ?, 'failed', ?, NOW())
            ", [$sub['id'], json_encode($notification_data), $e->getMessage()]);
        }
    }
    
    return [
        'success' => true,
        'sent' => $sent_count,
        'failed' => $failed_count,
        'total' => count($subscriptions)
    ];
}

/**
 * Send notification for new article
 */
function notifyNewArticle($article_id) {
    $db = Database::getInstance();
    
    $article = $db->fetchOne("
        SELECT a.*, c.name as category_name 
        FROM articles a 
        LEFT JOIN categories c ON a.category_id = c.id 
        WHERE a.id = ?
    ", [$article_id]);
    
    if (!$article) {
        return ['success' => false, 'error' => 'Article not found'];
    }
    
    $notification = [
        'title' => $article['title'],
        'body' => substr(strip_tags($article['description']), 0, 100) . '...',
        'icon' => UPLOADS_URL . '/articles/' . $article['thumbnail'],
        'badge' => ASSETS_URL . '/images/badge-72.png',
        'url' => BASE_URL . '/article/' . $article['slug'],
        'tag' => 'article-' . $article_id,
        'requireInteraction' => false,
        'data' => [
            'id' => $article_id,
            'type' => 'article',
            'category' => $article['category_name']
        ]
    ];
    
    if ($article['is_breaking']) {
        $notification['requireInteraction'] = true;
        $notification['title'] = '🔴 BREAKING: ' . $notification['title'];
    }
    
    return sendPushNotification($notification);
}

/**
 * Send notification for live updates
 */
function notifyLiveUpdate($title, $message, $url = null) {
    $notification = [
        'title' => '🔴 LIVE: ' . $title,
        'body' => $message,
        'icon' => ASSETS_URL . '/images/icon-192.png',
        'badge' => ASSETS_URL . '/images/badge-72.png',
        'url' => $url ?? BASE_URL,
        'tag' => 'live-update-' . time(),
        'requireInteraction' => true,
        'renotify' => true
    ];
    
    return sendPushNotification($notification);
}

/**
 * Generate VAPID keys (run once and save to config)
 */
function generateVAPIDKeys() {
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
    
    return [
        'publicKey' => $keys['publicKey'],
        'privateKey' => $keys['privateKey']
    ];
}
