<?php
/**
 * Notifications Management
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';
require_once '../includes/FCMHelper.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$page_title = 'Push Notifications';

$errors = [];
$success = '';

// Handle notification sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $articleId = trim($_POST['article_id'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($body)) {
        $errors[] = 'Message body is required';
    }
    
    if (empty($errors)) {
        if (empty(FIREBASE_SERVER_KEY)) {
            $errors[] = 'Firebase Server Key is not configured in config.php';
        } else {
            $fcm = new FCMHelper();
            
            $data = [
                'type' => 'custom',
                'notification_type' => 'custom',
            ];
            
            if (!empty($articleId)) {
                $article = $db->fetchOne("SELECT slug FROM articles WHERE id = ?", [$articleId]);
                if ($article) {
                    $data['article_id'] = $articleId;
                    $data['type'] = 'article';
                    $data['notification_type'] = 'post';
                    $data['route'] = '/article/' . $article['slug'];
                }
            }
            
            if (!empty($imageUrl)) {
                $data['image_url'] = $imageUrl;
            }
            
            $result = $fcm->sendToTopic('all', $title, $body, $data);
            
            if ($result) {
                $success = 'Notification sent successfully to all app users!';
            } else {
                $errors[] = 'Failed to send notification. Check error logs for details.';
            }
        }
    }
}

// Get recent articles for dropdown
$recentArticles = $db->fetchAll("SELECT id, title FROM articles WHERE status = 'published' ORDER BY published_at DESC LIMIT 50");

include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Push Notifications</h1>
    
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <?php if (empty(FIREBASE_SERVER_KEY)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Configuration Required:</strong> Firebase Server Key is not set in config.php. 
        Please add <code>define('FIREBASE_SERVER_KEY', 'your-key-here');</code>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Send Custom Notification -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-paper-plane me-1"></i> Send Custom Notification
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Notification Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required 
                                   placeholder="e.g., Breaking News Alert" 
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                            <small class="text-muted">This will appear as the notification heading</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea name="body" class="form-control" rows="4" required 
                                      placeholder="Enter your notification message..."><?= htmlspecialchars($_POST['body'] ?? '') ?></textarea>
                            <small class="text-muted">The main notification content</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Link to Article (Optional)</label>
                            <select name="article_id" class="form-select">
                                <option value="">-- Select Article (Optional) --</option>
                                <?php foreach ($recentArticles as $article): ?>
                                <option value="<?= $article['id'] ?>"><?= htmlspecialchars($article['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">If selected, tapping notification will open this article</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Image URL (Optional)</label>
                            <input type="url" name="image_url" class="form-control" 
                                   placeholder="https://example.com/image.jpg"
                                   value="<?= htmlspecialchars($_POST['image_url'] ?? '') ?>">
                            <small class="text-muted">Notification thumbnail (must be a complete URL)</small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="send_notification" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send to All Users
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="this.form.reset()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i> How Notifications Work
                </div>
                <div class="card-body">
                    <ul>
                        <li><strong>New Article Published:</strong> When you publish an article, a notification is automatically sent</li>
                        <li><strong>Breaking News:</strong> If article is marked as "Breaking News", special alert is sent</li>
                        <li><strong>Category-Specific:</strong> Users subscribed to categories receive targeted notifications</li>
                    </ul>
                    
                    <h5 class="mt-4">Custom Notifications:</h5>
                    <ul>
                        <li>Use the form above to send instant notifications to all app users</li>
                        <li>Perfect for announcements, events, or important updates</li>
                        <li>Can link to any article or stand alone</li>
                        <li>Notifications appear in the app's notification section with Save & Share buttons</li>
                    </ul>
                    
                    <h5 class="mt-4">Implementation Steps:</h5>
                    <ol>
                        <li>Set up service worker for web push</li>
                        <li>Configure Firebase Cloud Messaging (optional)</li>
                        <li>Create notification subscription UI</li>
                        <li>Build admin interface for sending notifications</li>
                        <li>Add analytics tracking</li>
                    </ol>
                    
                    <h5 class="mt-4">Database Schema:</h5>
                    <pre class="bg-light p-3"><code>CREATE TABLE notification_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    endpoint TEXT,
    p256dh TEXT,
    auth TEXT,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    message TEXT,
    icon VARCHAR(255),
    url VARCHAR(255),
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    status ENUM('draft', 'scheduled', 'sent') DEFAULT 'draft',
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);</code></pre>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i> Stats
                </div>
                <div class="card-body text-center">
                    <h2 class="text-primary">0</h2>
                    <p class="text-muted">Total Subscribers</p>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <h5>0</h5>
                            <small>Sent Today</small>
                        </div>
                        <div class="col-6">
                            <h5>0%</h5>
                            <small>Click Rate</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exclamation-triangle me-1"></i> Important Notes
                </div>
                <div class="card-body">
                    <ul class="small">
                        <li>Requires HTTPS for web push</li>
                        <li>Users must grant permission</li>
                        <li>Don't send too frequently</li>
                        <li>Provide clear opt-out option</li>
                        <li>Test notifications thoroughly</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-code me-1"></i> Technical Setup
                </div>
                <div class="card-body">
                    <p class="small text-muted">Required:</p>
                    <ul class="small">
                        <li>Service Worker (sw.js)</li>
                        <li>Push subscription handler</li>
                        <li>VAPID keys generation</li>
                        <li>Firebase project (optional)</li>
                        <li>SSL certificate</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <a href="dashboard.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>