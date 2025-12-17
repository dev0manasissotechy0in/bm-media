<?php
/**
 * NOTIFICATION HELPER CLASS
 * Creates notifications for case thread updates
 */

class NotificationHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Send notification when new article is linked to case
     */
    public function notifyNewArticle($caseId, $articleId, $articleTitle) {
        try {
            // Get case details
            $caseStmt = $this->pdo->prepare("SELECT title, slug FROM case_threads WHERE id = ?");
            $caseStmt->execute([$caseId]);
            $case = $caseStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$case) {
                return false;
            }
            
            // Get followers who want article notifications
            $followersStmt = $this->pdo->prepare("
                SELECT user_id 
                FROM case_follows 
                WHERE case_id = ? AND notify_new_articles = 1
            ");
            $followersStmt->execute([$caseId]);
            $followers = $followersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($followers)) {
                return true; // No followers to notify
            }
            
            // Create notifications for each follower
            $insertStmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, case_id, notification_type, title, message, action_url, entity_type, entity_id) 
                VALUES (?, ?, 'new_article', ?, ?, ?, 'article', ?)
            ");
            
            $title = "New Article in " . $case['title'];
            $message = "New article published: " . $articleTitle;
            $actionUrl = "/article/" . $articleId;
            
            foreach ($followers as $userId) {
                $insertStmt->execute([$userId, $caseId, $title, $message, $actionUrl, $articleId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating article notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification when timeline event is added
     */
    public function notifyTimelineEvent($caseId, $eventId, $eventTitle, $eventType) {
        try {
            // Get case details
            $caseStmt = $this->pdo->prepare("SELECT title, slug FROM case_threads WHERE id = ?");
            $caseStmt->execute([$caseId]);
            $case = $caseStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$case) {
                return false;
            }
            
            // Get followers who want timeline notifications
            $followersStmt = $this->pdo->prepare("
                SELECT user_id 
                FROM case_follows 
                WHERE case_id = ? AND notify_timeline_events = 1
            ");
            $followersStmt->execute([$caseId]);
            $followers = $followersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($followers)) {
                return true;
            }
            
            // Create notifications
            $insertStmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, case_id, notification_type, title, message, action_url, entity_type, entity_id) 
                VALUES (?, ?, 'timeline_event', ?, ?, ?, 'event', ?)
            ");
            
            $title = "Timeline Update: " . $case['title'];
            $message = "New " . str_replace('_', ' ', $eventType) . " event: " . $eventTitle;
            $actionUrl = "/case/" . $case['slug'];
            
            foreach ($followers as $userId) {
                $insertStmt->execute([$userId, $caseId, $title, $message, $actionUrl, $eventId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating timeline notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification when document is added
     */
    public function notifyDocument($caseId, $documentId, $documentTitle, $documentType) {
        try {
            // Get case details
            $caseStmt = $this->pdo->prepare("SELECT title, slug FROM case_threads WHERE id = ?");
            $caseStmt->execute([$caseId]);
            $case = $caseStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$case) {
                return false;
            }
            
            // Get followers who want document notifications
            $followersStmt = $this->pdo->prepare("
                SELECT user_id 
                FROM case_follows 
                WHERE case_id = ? AND notify_documents = 1
            ");
            $followersStmt->execute([$caseId]);
            $followers = $followersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($followers)) {
                return true;
            }
            
            // Create notifications
            $insertStmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, case_id, notification_type, title, message, action_url, entity_type, entity_id) 
                VALUES (?, ?, 'document_added', ?, ?, ?, 'document', ?)
            ");
            
            $title = "New Document: " . $case['title'];
            $message = "New " . str_replace('_', ' ', $documentType) . " added: " . $documentTitle;
            $actionUrl = "/case/" . $case['slug'];
            
            foreach ($followers as $userId) {
                $insertStmt->execute([$userId, $caseId, $title, $message, $actionUrl, $documentId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating document notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for verdict
     */
    public function notifyVerdict($caseId, $verdictTitle, $verdictMessage) {
        try {
            // Get case details
            $caseStmt = $this->pdo->prepare("SELECT title, slug FROM case_threads WHERE id = ?");
            $caseStmt->execute([$caseId]);
            $case = $caseStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$case) {
                return false;
            }
            
            // Get followers who want verdict notifications
            $followersStmt = $this->pdo->prepare("
                SELECT user_id 
                FROM case_follows 
                WHERE case_id = ? AND notify_verdicts = 1
            ");
            $followersStmt->execute([$caseId]);
            $followers = $followersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($followers)) {
                return true;
            }
            
            // Create notifications
            $insertStmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, case_id, notification_type, title, message, action_url, entity_type, entity_id) 
                VALUES (?, ?, 'verdict', ?, ?, ?, 'case', ?)
            ");
            
            $title = "Verdict: " . $case['title'];
            $message = $verdictMessage;
            $actionUrl = "/case/" . $case['slug'];
            
            foreach ($followers as $userId) {
                $insertStmt->execute([$userId, $caseId, $title, $message, $actionUrl, $caseId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating verdict notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification for case status change
     */
    public function notifyCaseUpdate($caseId, $updateType, $updateMessage) {
        try {
            // Get case details
            $caseStmt = $this->pdo->prepare("SELECT title, slug FROM case_threads WHERE id = ?");
            $caseStmt->execute([$caseId]);
            $case = $caseStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$case) {
                return false;
            }
            
            // Get all followers (send to everyone regardless of preferences)
            $followersStmt = $this->pdo->prepare("
                SELECT user_id 
                FROM case_follows 
                WHERE case_id = ?
            ");
            $followersStmt->execute([$caseId]);
            $followers = $followersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($followers)) {
                return true;
            }
            
            // Create notifications
            $insertStmt = $this->pdo->prepare("
                INSERT INTO notifications 
                (user_id, case_id, notification_type, title, message, action_url, entity_type, entity_id) 
                VALUES (?, ?, ?, ?, ?, ?, 'case', ?)
            ");
            
            $title = "Case Update: " . $case['title'];
            $actionUrl = "/case/" . $case['slug'];
            
            foreach ($followers as $userId) {
                $insertStmt->execute([$userId, $caseId, $updateType, $title, $updateMessage, $actionUrl, $caseId]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating case update notification: " . $e->getMessage());
            return false;
        }
    }
}
