<?php
/**
 * FCM v1 API Helper (Modern Firebase Cloud Messaging)
 * Uses service account for authentication
 */

class FCMv1Helper {
    private $projectId;
    private $serviceAccountJson;
    private $fcmUrl;
    
    public function __construct($projectId = 'brackoddmedia-56b89') {
        $this->projectId = $projectId;
        $this->fcmUrl = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        
        // Load service account from config folder
        $serviceAccountPath = dirname(__FILE__) . '/../config/brackoddmedia-56b89-firebase-adminsdk-k7hcs-e9da41db1c.json';
        if (file_exists($serviceAccountPath)) {
            $this->serviceAccountJson = json_decode(file_get_contents($serviceAccountPath), true);
        } else {
            error_log('FCM: Service account JSON not found at: ' . $serviceAccountPath);
        }
    }
    
    /**
     * Get OAuth 2.0 access token using service account
     */
    private function getAccessToken() {
        if (!$this->serviceAccountJson) {
            error_log('FCM: Service account JSON not found');
            return false;
        }
        
        require_once __DIR__ . '/../vendor/autoload.php';
        
        try {
            $client = new Google_Client();
            $client->setAuthConfig($this->serviceAccountJson);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $token = $client->fetchAccessTokenWithAssertion();
            
            return $token['access_token'] ?? false;
        } catch (Exception $e) {
            error_log('FCM: Failed to get access token - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification to topic with custom features
     * 
     * @param string $topic Topic name (e.g., 'all', 'category_1')
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data payload
     * @param array $options Additional options:
     *   - 'channel_id': Android notification channel (case_study, live_news, stories, breaking)
     *   - 'priority': Notification priority (default, high)
     *   - 'image': Large image URL
     *   - 'badge': Custom badge/label text
     *   - 'actions': Array of action buttons [['action'=>'share', 'title'=>'Share'], ...]
     */
    public function sendToTopic($topic, $title, $body, $data = [], $options = []) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }
        
        // Determine notification channel based on type
        $channelId = $options['channel_id'] ?? $data['notification_type'] ?? 'default';
        $priority = $options['priority'] ?? 'high';
        $badge = $options['badge'] ?? null;
        
        // Build Android-specific configuration
        $androidConfig = [
            'priority' => $priority,
            'notification' => [
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'channel_id' => $this->getChannelId($channelId),
                'tag' => $channelId, // Groups notifications by type
                'color' => $this->getChannelColor($channelId),
            ]
        ];
        
        // Add badge/label if provided
        if ($badge) {
            $androidConfig['notification']['ticker'] = $badge;
            $data['badge'] = $badge;
        }
        
        // Add image if provided
        if (!empty($options['image'])) {
            $androidConfig['notification']['image'] = $options['image'];
        }
        
        // Add action buttons if provided
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
     * Get notification channel ID for different content types
     */
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
    
    /**
     * Get notification color for different content types
     */
    private function getChannelColor($type) {
        $colors = [
            'case_study' => '#10B981',      // Green
            'case_update' => '#10B981',     // Green
            'live_news' => '#EF4444',       // Red
            'breaking' => '#DC2626',        // Dark Red
            'story' => '#8B5CF6',           // Purple
            'article' => '#3B82F6',         // Blue
            'default' => '#6B7280'          // Gray
        ];
        
        return $colors[$type] ?? $colors['default'];
    }
    
    /**
     * Send Case Study notification with action buttons
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
    
    /**
     * Send Live News notification with action buttons
     */
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
    
    /**
     * Send Story notification with action buttons
     */
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
    
    /**
     * Send Breaking News notification (highest priority)
     */
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
                ['action' => 'save', 'title' => 'Save'},
                ['action' => 'share', 'title' => 'Share']
            ]
        ];
        
        return $this->sendToTopic('all', '🚨 Breaking News', $title, $data, $options);
    }
    
    /**
     * Send notification to specific device token
     */
    public function sendToToken($token, $title, $body, $data = []) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }
        
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body
                ],
                'data' => $data,
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                    ]
                ]
            ]
        ];
        
        return $this->sendRequest($accessToken, $message);
    }
    
    /**
     * Send HTTP request to FCM v1 API
     */
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
