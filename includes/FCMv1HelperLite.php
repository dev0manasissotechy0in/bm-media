<?php
/**
 * FCM v1 API Helper - Lightweight Version (No Composer Dependencies)
 * Uses JWT for authentication instead of Google API Client
 */

class FCMv1HelperLite {
    private $projectId;
    private $serviceAccountPath;
    private $fcmUrl;
    private $accessToken = null;
    private $tokenExpiry = 0;
    
    public function __construct($projectId = 'brackoddmedia-56b89') {
        $this->projectId = $projectId;
        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $this->serviceAccountPath = dirname(__FILE__) . '/../config/brackoddmedia-56b89-firebase-adminsdk-k7hcs-e9da41db1c.json';
    }
    
    /**
     * Get OAuth 2.0 access token using JWT (No Google Client Library needed)
     */
    private function getAccessToken() {
        // Return cached token if still valid
        if ($this->accessToken && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }
        
        if (!file_exists($this->serviceAccountPath)) {
            error_log('FCM: Service account JSON not found at: ' . $this->serviceAccountPath);
            return false;
        }
        
        $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);
        
        // Create JWT
        $now = time();
        $expiry = $now + 3600; // 1 hour
        
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        $payload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $expiry
        ];
        
        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));
        
        $signature = '';
        $dataToSign = "$headerEncoded.$payloadEncoded";
        
        openssl_sign(
            $dataToSign,
            $signature,
            $serviceAccount['private_key'],
            OPENSSL_ALGO_SHA256
        );
        
        $signatureEncoded = $this->base64UrlEncode($signature);
        $jwt = "$headerEncoded.$payloadEncoded.$signatureEncoded";
        
        // Exchange JWT for access token
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $tokenData = json_decode($response, true);
            $this->accessToken = $tokenData['access_token'];
            $this->tokenExpiry = time() + ($tokenData['expires_in'] - 60); // Refresh 1 min early
            return $this->accessToken;
        } else {
            error_log('FCM: Failed to get access token - HTTP ' . $httpCode . ' - ' . $response);
            return false;
        }
    }
    
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Send notification to topic with custom features
     */
    public function sendToTopic($topic, $title, $body, $data = [], $options = []) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }
        
        $channelId = $options['channel_id'] ?? $data['notification_type'] ?? 'default';
        $priority = $options['priority'] ?? 'high';
        $badge = $options['badge'] ?? null;
        
        $androidConfig = [
            'priority' => $priority,
            'notification' => [
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'channel_id' => $this->getChannelId($channelId),
                'tag' => $channelId,
                'color' => $this->getChannelColor($channelId),
            ]
        ];
        
        if ($badge) {
            $androidConfig['notification']['ticker'] = $badge;
            $data['badge'] = $badge;
        }
        
        if (!empty($options['image'])) {
            $androidConfig['notification']['image'] = $options['image'];
        }
        
        if (!empty($options['actions'])) {
            $data['actions'] = json_encode($options['actions']);
        }
        
        $message = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data,
                'android' => $androidConfig
            ]
        ];
        
        return $this->sendRequest($accessToken, $message);
    }
    
    /**
     * Send notification to specific device token
     */
    public function sendToToken($token, $title, $body, $data = [], $options = []) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }
        
        $channelId = $options['channel_id'] ?? $data['notification_type'] ?? 'default';
        
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data,
                'android' => [
                    'priority' => $options['priority'] ?? 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id' => $this->getChannelId($channelId),
                        'color' => $this->getChannelColor($channelId),
                    ]
                ]
            ]
        ];
        
        return $this->sendRequest($accessToken, $message);
    }
    
    private function getChannelId($type) {
        $channels = [
            'case_study' => 'case_study_channel',
            'case_update' => 'case_study_channel',
            'live_news' => 'live_news_channel',
            'breaking' => 'breaking_news_channel',
            'story' => 'stories_channel',
            'article' => 'articles_channel',
            'default' => 'default_channel'
        ];
        return $channels[$type] ?? $channels['default'];
    }
    
    private function getChannelColor($type) {
        $colors = [
            'case_study' => '#10B981',
            'case_update' => '#10B981',
            'live_news' => '#EF4444',
            'breaking' => '#DC2626',
            'story' => '#8B5CF6',
            'article' => '#3B82F6',
            'default' => '#6B7280'
        ];
        return $colors[$type] ?? $colors['default'];
    }
    
    /**
     * Convenient methods for different notification types
     */
    public function sendCaseStudyNotification($caseId, $title, $slug, $isUpdate = false) {
        $badge = $isUpdate ? '📝 Case Study Update' : '📋 New Case Study';
        $notificationTitle = $isUpdate ? 'Case Study Updated' : 'New Case Study';
        
        $data = [
            'type' => 'case_study',
            'notification_type' => $isUpdate ? 'case_update' : 'case_study',
            'case_id' => (string)$caseId,
            'case_slug' => $slug,
            'route' => '/case/' . $slug
        ];
        
        $options = [
            'channel_id' => 'case_study',
            'badge' => $badge,
            'priority' => 'high',
            'actions' => [
                ['action' => 'open', 'title' => 'View Case'],
                ['action' => 'share', 'title' => 'Share']
            ]
        ];
        
        return $this->sendToTopic('all', $notificationTitle, $title, $data, $options);
    }
    
    public function sendLiveNewsNotification($articleId, $title, $slug, $imageUrl = null) {
        $data = [
            'type' => 'live_news',
            'notification_type' => 'live_news',
            'article_id' => (string)$articleId,
            'article_slug' => $slug,
            'route' => '/article/' . $slug
        ];
        
        $options = [
            'channel_id' => 'live_news',
            'badge' => '🔴 Live News',
            'priority' => 'high',
            'image' => $imageUrl,
            'actions' => [
                ['action' => 'open', 'title' => 'Read Now'],
                ['action' => 'save', 'title' => 'Save'],
                ['action' => 'share', 'title' => 'Share']
            ]
        ];
        
        return $this->sendToTopic('all', '🔴 Live News', $title, $data, $options);
    }
    
    public function sendStoryNotification($storyId, $title, $imageUrl = null) {
        $data = [
            'type' => 'story',
            'notification_type' => 'story',
            'story_id' => (string)$storyId,
            'route' => '/stories'
        ];
        
        $options = [
            'channel_id' => 'story',
            'badge' => '📱 New Latest Story',
            'priority' => 'default',
            'image' => $imageUrl,
            'actions' => [
                ['action' => 'view', 'title' => 'View Story'],
                ['action' => 'share', 'title' => 'Share']
            ]
        ];
        
        return $this->sendToTopic('all', 'New Story Available', $title, $data, $options);
    }
    
    public function sendBreakingNewsNotification($articleId, $title, $slug, $imageUrl = null) {
        $data = [
            'type' => 'breaking_news',
            'notification_type' => 'breaking',
            'article_id' => (string)$articleId,
            'article_slug' => $slug,
            'route' => '/article/' . $slug
        ];
        
        $options = [
            'channel_id' => 'breaking',
            'badge' => '🚨 Breaking News',
            'priority' => 'high',
            'image' => $imageUrl,
            'actions' => [
                ['action' => 'open', 'title' => 'Read Now'],
                ['action' => 'save', 'title' => 'Save'],
                ['action' => 'share', 'title' => 'Share']
            ]
        ];
        
        return $this->sendToTopic('all', '🚨 Breaking News', $title, $data, $options);
    }
    
    private function sendRequest($accessToken, $message) {
        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            error_log('FCM v1 Error: ' . $error);
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        error_log('FCM v1 Response (HTTP ' . $httpCode . '): ' . $result);
        
        if ($httpCode === 200) {
            return $result;
        } else {
            error_log('FCM v1: Failed - HTTP ' . $httpCode);
            return false;
        }
    }
}
