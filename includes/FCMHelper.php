<?php
/**
 * FCM (Firebase Cloud Messaging) Helper
 * Sends push notifications to Flutter app users
 */

class FCMHelper {
    private $serverKey;
    private $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    
    public function __construct($serverKey = null) {
        if ($serverKey) {
            $this->serverKey = $serverKey;
        } else {
            // Try to get from database settings first
            require_once __DIR__ . '/Settings.php';
            $this->serverKey = Settings::get('fcm_server_key', FIREBASE_SERVER_KEY);
        }
    }
    
    /**
     * Send notification to specific device tokens
     */
    public function sendToTokens($tokens, $title, $body, $data = []) {
        if (empty($this->serverKey)) {
            error_log('FCM: Firebase server key not configured');
            return false;
        }
        
        if (empty($tokens)) {
            return false;
        }
        
        // Ensure tokens is an array
        if (!is_array($tokens)) {
            $tokens = [$tokens];
        }
        
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => '1',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];
        
        $payload = [
            'registration_ids' => $tokens,
            'notification' => $notification,
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]),
            'priority' => 'high'
        ];
        
        return $this->sendRequest($payload);
    }
    
    /**
     * Send notification to topic (all subscribers)
     */
    public function sendToTopic($topic, $title, $body, $data = []) {
        if (empty($this->serverKey)) {
            error_log('FCM: Firebase server key not configured');
            return false;
        }
        
        $notification = [
            'title' => $title,
            'body' => $body,
            'sound' => 'default',
            'badge' => '1',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];
        
        $payload = [
            'to' => '/topics/' . $topic,
            'notification' => $notification,
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ]),
            'priority' => 'high'
        ];
        
        return $this->sendRequest($payload);
    }
    
    /**
     * Send notification when new article is published
     */
    public function sendArticleNotification($articleId, $articleTitle, $articleSlug, $categoryName = null) {
        $title = 'New Article Published';
        $body = $articleTitle;
        
        if ($categoryName) {
            $body = $categoryName . ': ' . $articleTitle;
        }
        
        $data = [
            'type' => 'article',
            'article_id' => (string)$articleId,
            'article_slug' => $articleSlug,
            'route' => '/article/' . $articleSlug
        ];
        
        // Send to all app users subscribed to notifications
        return $this->sendToTopic('all', $title, $body, $data);
    }
    
    /**
     * Send notification when breaking news is published
     */
    public function sendBreakingNewsNotification($articleId, $articleTitle, $articleSlug) {
        $title = '🚨 Breaking News';
        $body = $articleTitle;
        
        $data = [
            'type' => 'breaking_news',
            'article_id' => (string)$articleId,
            'article_slug' => $articleSlug,
            'route' => '/article/' . $articleSlug
        ];
        
        return $this->sendToTopic('all', $title, $body, $data);
    }
    
    /**
     * Send notification for category-specific articles
     */
    public function sendCategoryNotification($categoryId, $categoryName, $articleId, $articleTitle, $articleSlug) {
        $title = 'New in ' . $categoryName;
        $body = $articleTitle;
        
        $data = [
            'type' => 'article',
            'article_id' => (string)$articleId,
            'article_slug' => $articleSlug,
            'category_id' => (string)$categoryId,
            'route' => '/article/' . $articleSlug
        ];
        
        // Send to category-specific topic
        $topicName = 'category_' . $categoryId;
        return $this->sendToTopic($topicName, $title, $body, $data);
    }
    
    /**
     * Send notification when new podcast is published
     */
    public function sendPodcastNotification($podcastId, $podcastTitle, $podcastSlug) {
        $title = '🎧 New Podcast Available';
        $body = $podcastTitle;
        
        $data = [
            'type' => 'podcast',
            'podcast_id' => (string)$podcastId,
            'podcast_slug' => $podcastSlug,
            'route' => '/podcast/' . $podcastSlug
        ];
        
        return $this->sendToTopic('all', $title, $body, $data);
    }
    
    /**
     * Send HTTP request to FCM
     */
    private function sendRequest($payload) {
        $headers = [
            'Authorization: key=' . $this->serverKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            error_log('FCM Error: ' . $error);
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Log for debugging
        error_log('FCM Response (HTTP ' . $httpCode . '): ' . $result);
        
        // Return the full response string (not just true/false)
        // This allows calling code to inspect the response
        if ($httpCode === 200) {
            return $result;
        } else {
            error_log('FCM: Failed to send notification - HTTP ' . $httpCode);
            return false;
        }
    }
    
    /**
     * Get all active user FCM tokens from database
     */
    public static function getAllUserTokens($db) {
        try {
            $tokens = $db->fetchAll("
                SELECT DISTINCT fcm_token 
                FROM users 
                WHERE fcm_token IS NOT NULL 
                AND fcm_token != '' 
                AND status = 'active'
            ");
            
            return array_column($tokens, 'fcm_token');
        } catch (Exception $e) {
            error_log('Error fetching FCM tokens: ' . $e->getMessage());
            return [];
        }
    }
}
