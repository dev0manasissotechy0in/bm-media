<?php
/**
 * Notification Manager
 * Handles sending notifications to web and mobile platforms
 * Supports FCM for mobile and Web Push API for web
 */

class NotificationManager {
    private $db;
    private $fcm_server_key;
    private $vapid_public_key;
    private $vapid_private_key;
    
    public function __construct($database) {
        $this->db = $database;
        
        // FCM Server Key (Get from Firebase Console)
        $this->fcm_server_key = 'YOUR_FCM_SERVER_KEY_HERE';
        
        // VAPID keys for Web Push (Generate using web-push library)
        $this->vapid_public_key = 'YOUR_VAPID_PUBLIC_KEY';
        $this->vapid_private_key = 'YOUR_VAPID_PRIVATE_KEY';
    }
    
    /**
     * Send notification when new article is uploaded
     */
    public function sendNewsNotification($article_id, $title, $category = 'General') {
        $message = "New article: " . substr($title, 0, 100);
        $image_url = $this->getArticleImage($article_id);
        $action_url = "/article.php?id=" . $article_id;
        
        return $this->sendNotification([
            'title' => '📰 ' . $category . ' News',
            'message' => $message,
            'type' => 'news',
            'tag' => 'news_' . $category,
            'target' => 'all',
            'reference_type' => 'article',
            'reference_id' => $article_id,
            'image_url' => $image_url,
            'action_url' => $action_url
        ]);
    }
    
    /**
     * Send breaking news notification for mobile stories (APP ONLY)
     */
    public function sendBreakingNewsNotification($story_id, $title) {
        $image_url = $this->getMobileStoryImage($story_id);
        $action_url = "/mobile-story.php?id=" . $story_id;
        
        return $this->sendNotification([
            'title' => '🔥 Breaking News',
            'message' => $title,
            'type' => 'breaking',
            'tag' => 'breaking_story',
            'target' => 'app',  // Mobile only
            'reference_type' => 'mobile_story',
            'reference_id' => $story_id,
            'image_url' => $image_url,
            'action_url' => $action_url
        ]);
    }
    
    /**
     * Send notification when new case study is uploaded
     */
    public function sendCaseStudyNotification($case_id, $title) {
        $image_url = $this->getCaseStudyImage($case_id);
        $action_url = "/case.php?id=" . $case_id;
        
        return $this->sendNotification([
            'title' => '📋 New Case Study Uploaded',
            'message' => $title,
            'type' => 'case_study',
            'tag' => 'case_study_new',
            'target' => 'all',
            'reference_type' => 'case',
            'reference_id' => $case_id,
            'image_url' => $image_url,
            'action_url' => $action_url
        ]);
    }
    
    /**
     * Send notification when case study is updated
     */
    public function sendCaseStudyUpdateNotification($case_id, $case_title) {
        $image_url = $this->getCaseStudyImage($case_id);
        $action_url = "/case.php?id=" . $case_id;
        
        return $this->sendNotification([
            'title' => '🔄 ' . $case_title . ' - Updated',
            'message' => 'The case study has been updated with new information',
            'type' => 'case_study_update',
            'tag' => 'case_study_update',
            'target' => 'all',
            'reference_type' => 'case',
            'reference_id' => $case_id,
            'image_url' => $image_url,
            'action_url' => $action_url
        ]);
    }
    
    /**
     * Core function to send notification
     */
    public function sendNotification($data) {
        try {
            // Insert notification record
            $notification_id = $this->db->insert('notifications', [
                'title' => $data['title'],
                'message' => $data['message'],
                'type' => $data['type'],
                'tag' => $data['tag'] ?? null,
                'target' => $data['target'] ?? 'all',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'created_by' => $_SESSION['admin_id'] ?? null
            ]);
            
            if (!$notification_id) {
                throw new Exception('Failed to create notification record');
            }
            
            $sent_count = 0;
            $delivered_count = 0;
            
            // Send to mobile app (FCM)
            if ($data['target'] === 'all' || $data['target'] === 'app') {
                $result = $this->sendToMobileApp($notification_id, $data);
                $sent_count += $result['sent'];
                $delivered_count += $result['delivered'];
            }
            
            // Send to web (Web Push)
            if ($data['target'] === 'all' || $data['target'] === 'web') {
                $result = $this->sendToWeb($notification_id, $data);
                $sent_count += $result['sent'];
                $delivered_count += $result['delivered'];
            }
            
            // Update notification stats
            $this->db->update('notifications', $notification_id, [
                'total_sent' => $sent_count,
                'total_delivered' => $delivered_count
            ]);
            
            // Update analytics
            $this->updateAnalytics($data['tag'], $data['type'], $sent_count);
            
            return [
                'success' => true,
                'notification_id' => $notification_id,
                'sent' => $sent_count,
                'delivered' => $delivered_count
            ];
            
        } catch (Exception $e) {
            error_log("Notification Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to mobile app via FCM
     */
    private function sendToMobileApp($notification_id, $data) {
        $sent = 0;
        $delivered = 0;
        
        // Get active device tokens
        $tokens = $this->db->fetchAll(
            "SELECT DISTINCT dt.device_token, dt.user_id, dt.platform 
             FROM device_tokens dt
             LEFT JOIN user_notification_preferences unp ON dt.user_id = unp.user_id AND dt.platform = unp.platform
             WHERE dt.is_active = 1 
             AND dt.platform IN ('android', 'ios')
             AND (unp.{$data['type']}_enabled IS NULL OR unp.{$data['type']}_enabled = 1)"
        );
        
        if (empty($tokens)) {
            return ['sent' => 0, 'delivered' => 0];
        }
        
        // Prepare FCM payload
        $fcm_data = [
            'notification' => [
                'title' => $data['title'],
                'body' => $data['message'],
                'image' => $data['image_url'] ?? '',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ],
            'data' => [
                'notification_id' => (string)$notification_id,
                'type' => $data['type'],
                'tag' => $data['tag'] ?? '',
                'action_url' => $data['action_url'] ?? '',
                'reference_type' => $data['reference_type'] ?? '',
                'reference_id' => (string)($data['reference_id'] ?? '')
            ],
            'priority' => 'high'
        ];
        
        // Send in batches of 500 (FCM limit is 1000)
        $token_chunks = array_chunk($tokens, 500);
        
        foreach ($token_chunks as $chunk) {
            $device_tokens = array_column($chunk, 'device_token');
            $fcm_data['registration_ids'] = $device_tokens;
            
            $response = $this->sendFCMRequest($fcm_data);
            
            if ($response['success']) {
                $sent += count($device_tokens);
                $delivered += $response['success_count'];
                
                // Track individual deliveries
                foreach ($chunk as $token_data) {
                    $this->db->insert('user_notifications', [
                        'notification_id' => $notification_id,
                        'user_id' => $token_data['user_id'],
                        'device_token' => $token_data['device_token'],
                        'platform' => $token_data['platform'],
                        'is_delivered' => 1,
                        'delivered_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        
        return ['sent' => $sent, 'delivered' => $delivered];
    }
    
    /**
     * Send notification to web via Web Push API
     */
    private function sendToWeb($notification_id, $data) {
        $sent = 0;
        $delivered = 0;
        
        // Get active web push subscriptions
        $subscriptions = $this->db->fetchAll(
            "SELECT wps.*, unp.{$data['type']}_enabled 
             FROM web_push_subscriptions wps
             LEFT JOIN user_notification_preferences unp ON wps.user_id = unp.user_id AND unp.platform = 'web'
             WHERE wps.is_active = 1 
             AND (unp.{$data['type']}_enabled IS NULL OR unp.{$data['type']}_enabled = 1)"
        );
        
        if (empty($subscriptions)) {
            return ['sent' => 0, 'delivered' => 0];
        }
        
        // Prepare notification payload
        $payload = json_encode([
            'title' => $data['title'],
            'body' => $data['message'],
            'icon' => '/assets/images/logo.png',
            'badge' => '/assets/images/badge.png',
            'image' => $data['image_url'] ?? '',
            'data' => [
                'notification_id' => $notification_id,
                'url' => $data['action_url'] ?? '/',
                'type' => $data['type'],
                'tag' => $data['tag'] ?? ''
            ],
            'tag' => $data['tag'] ?? 'default',
            'requireInteraction' => false
        ]);
        
        foreach ($subscriptions as $sub) {
            try {
                $result = $this->sendWebPush(
                    $sub['endpoint'],
                    $sub['p256dh_key'],
                    $sub['auth_token'],
                    $payload
                );
                
                if ($result) {
                    $sent++;
                    $delivered++;
                    
                    // Track delivery
                    $this->db->insert('user_notifications', [
                        'notification_id' => $notification_id,
                        'user_id' => $sub['user_id'],
                        'device_token' => $sub['endpoint'],
                        'platform' => 'web',
                        'is_delivered' => 1,
                        'delivered_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // Mark subscription as inactive if delivery fails
                    $this->db->update('web_push_subscriptions', $sub['id'], ['is_active' => 0]);
                }
            } catch (Exception $e) {
                error_log("Web Push Error: " . $e->getMessage());
            }
        }
        
        return ['sent' => $sent, 'delivered' => $delivered];
    }
    
    /**
     * Send FCM request
     */
    private function sendFCMRequest($data) {
        $url = 'https://fcm.googleapis.com/fcm/send';
        
        $headers = [
            'Authorization: key=' . $this->fcm_server_key,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpcode === 200) {
            $response = json_decode($result, true);
            return [
                'success' => true,
                'success_count' => $response['success'] ?? 0,
                'failure_count' => $response['failure'] ?? 0
            ];
        }
        
        return ['success' => false, 'success_count' => 0];
    }
    
    /**
     * Send Web Push notification
     */
    private function sendWebPush($endpoint, $p256dh, $auth, $payload) {
        // This is a simplified implementation
        // In production, use a library like web-push-php
        // https://github.com/web-push-libs/web-push-php
        
        // For now, return true to simulate success
        // TODO: Implement actual web push sending
        return true;
    }
    
    /**
     * Track notification click
     */
    public function trackClick($notification_id, $user_id = null, $device_token = null) {
        try {
            // Update user notification
            if ($device_token) {
                $this->db->query(
                    "UPDATE user_notifications 
                     SET is_clicked = 1, clicked_at = NOW() 
                     WHERE notification_id = ? AND device_token = ?",
                    [$notification_id, $device_token]
                );
            }
            
            // Update notification total clicks
            $this->db->query(
                "UPDATE notifications 
                 SET total_clicked = total_clicked + 1 
                 WHERE id = ?",
                [$notification_id]
            );
            
            // Update analytics
            $notification = $this->db->fetchOne("SELECT tag, type FROM notifications WHERE id = ?", [$notification_id]);
            if ($notification) {
                $this->updateAnalytics($notification['tag'], $notification['type'], 0, 1);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Track Click Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Track notification view
     */
    public function trackView($notification_id, $device_token = null) {
        try {
            // Update user notification
            if ($device_token) {
                $this->db->query(
                    "UPDATE user_notifications 
                     SET is_read = 1, read_at = NOW() 
                     WHERE notification_id = ? AND device_token = ? AND is_read = 0",
                    [$notification_id, $device_token]
                );
            }
            
            // Update notification total views
            $this->db->query(
                "UPDATE notifications 
                 SET total_viewed = total_viewed + 1 
                 WHERE id = ?",
                [$notification_id]
            );
            
            // Update analytics
            $notification = $this->db->fetchOne("SELECT tag, type FROM notifications WHERE id = ?", [$notification_id]);
            if ($notification) {
                $this->updateAnalytics($notification['tag'], $notification['type'], 0, 0, 1);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Track View Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update analytics
     */
    private function updateAnalytics($tag, $type, $sent = 0, $clicked = 0, $viewed = 0) {
        if (!$tag) return;
        
        $date = date('Y-m-d');
        
        // Check if record exists for today
        $existing = $this->db->fetchOne(
            "SELECT id, total_sent, total_clicked, total_viewed FROM notification_analytics WHERE tag = ? AND date = ?",
            [$tag, $date]
        );
        
        if ($existing) {
            $new_sent = $existing['total_sent'] + $sent;
            $new_clicked = $existing['total_clicked'] + $clicked;
            $new_viewed = $existing['total_viewed'] + $viewed;
            $click_rate = $new_sent > 0 ? ($new_clicked / $new_sent) * 100 : 0;
            
            $this->db->update('notification_analytics', $existing['id'], [
                'total_sent' => $new_sent,
                'total_clicked' => $new_clicked,
                'total_viewed' => $new_viewed,
                'click_rate' => round($click_rate, 2)
            ]);
        } else {
            $click_rate = $sent > 0 ? ($clicked / $sent) * 100 : 0;
            
            $this->db->insert('notification_analytics', [
                'tag' => $tag,
                'type' => $type,
                'total_sent' => $sent,
                'total_clicked' => $clicked,
                'total_viewed' => $viewed,
                'click_rate' => round($click_rate, 2),
                'date' => $date
            ]);
        }
    }
    
    /**
     * Helper functions to get images
     */
    private function getArticleImage($article_id) {
        $article = $this->db->fetchOne("SELECT featured_image FROM articles WHERE id = ?", [$article_id]);
        return $article ? ($article['featured_image'] ?? '') : '';
    }
    
    private function getMobileStoryImage($story_id) {
        $story = $this->db->fetchOne("SELECT media_path FROM mobile_stories WHERE id = ?", [$story_id]);
        return $story ? ($story['media_path'] ?? '') : '';
    }
    
    private function getCaseStudyImage($case_id) {
        $case = $this->db->fetchOne("SELECT featured_image FROM cases WHERE id = ?", [$case_id]);
        return $case ? ($case['featured_image'] ?? '') : '';
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id = null, $device_token = null, $limit = 50, $offset = 0) {
        if ($device_token) {
            $query = "SELECT n.*, un.is_read, un.is_clicked, un.read_at, un.clicked_at
                      FROM notifications n
                      INNER JOIN user_notifications un ON n.id = un.notification_id
                      WHERE un.device_token = ?
                      ORDER BY n.created_at DESC
                      LIMIT ? OFFSET ?";
            return $this->db->fetchAll($query, [$device_token, $limit, $offset]);
        } elseif ($user_id) {
            $query = "SELECT n.*, un.is_read, un.is_clicked, un.read_at, un.clicked_at
                      FROM notifications n
                      INNER JOIN user_notifications un ON n.id = un.notification_id
                      WHERE un.user_id = ?
                      ORDER BY n.created_at DESC
                      LIMIT ? OFFSET ?";
            return $this->db->fetchAll($query, [$user_id, $limit, $offset]);
        }
        
        return [];
    }
    
    /**
     * Get unread count
     */
    public function getUnreadCount($user_id = null, $device_token = null) {
        if ($device_token) {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM user_notifications WHERE device_token = ? AND is_read = 0",
                [$device_token]
            );
        } elseif ($user_id) {
            $result = $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM user_notifications WHERE user_id = ? AND is_read = 0",
                [$user_id]
            );
        } else {
            return 0;
        }
        
        return $result ? $result['count'] : 0;
    }
}
