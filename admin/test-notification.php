<?php
/**
 * Debug & Test FCM Notifications
 */

require_once 'auth_check.php';
require_once '../includes/Settings.php';

$page_title = 'Test Notifications';
$db = Database::getInstance();

$result = null;
$error = null;
$debug_info = [];

// Check Firebase configuration (check database first, then config)
$fcm_key = Settings::get('fcm_server_key', FIREBASE_SERVER_KEY);
$firebase_configured = !empty($fcm_key);
$debug_info['firebase_key_configured'] = $firebase_configured;
$debug_info['firebase_key_length'] = $firebase_configured ? strlen($fcm_key) : 0;
$debug_info['fcm_key_source'] = Settings::get('fcm_server_key') ? 'database' : 'config';
$debug_info['push_notifications_enabled'] = Settings::get('enable_push_notifications', '0') === '1';

// Get total users with FCM tokens
try {
    $token_stats = $db->fetchOne("SELECT 
        COUNT(*) as total_users,
        COUNT(fcm_token) as users_with_tokens,
        COUNT(CASE WHEN fcm_token IS NOT NULL AND fcm_token != '' THEN 1 END) as active_tokens
    FROM users");
    
    $debug_info['total_users'] = $token_stats['total_users'];
    $debug_info['users_with_tokens'] = $token_stats['users_with_tokens'];
    $debug_info['active_tokens'] = $token_stats['active_tokens'];
} catch (Exception $e) {
    // fcm_token column doesn't exist yet
    $total_users = $db->fetchOne("SELECT COUNT(*) as total_users FROM users");
    $debug_info['total_users'] = $total_users['total_users'];
    $debug_info['users_with_tokens'] = 0;
    $debug_info['active_tokens'] = 0;
    $debug_info['fcm_token_column_missing'] = true;
}

// Get recent articles
$recent_articles = $db->fetchAll("SELECT id, title, slug, status, created_at FROM articles ORDER BY created_at DESC LIMIT 5");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_type = $_POST['notification_type'] ?? 'test';
    
    require_once INCLUDES_PATH . '/FCMHelper.php';
    $fcm = new FCMHelper();
    
    try {
        if ($notification_type === 'test') {
            // Send test notification to all users
            $title = trim($_POST['test_title'] ?? 'Test Notification');
            $body = trim($_POST['test_body'] ?? 'This is a test notification from admin panel');
            $image = trim($_POST['test_image'] ?? '');
            
            $data = [
                'notification_type' => 'custom',
                'type' => 'test',
                'title' => $title,
                'body' => $body,
            ];
            
            if (!empty($image)) {
                $data['image_url'] = $image;
            }
            
            $response = $fcm->sendToTopic('all', $title, $body, $data);
            
            if ($response) {
                $result = [
                    'success' => true,
                    'message' => 'Test notification sent successfully to "all" topic',
                    'data' => $data,
                    'response' => json_decode($response, true)
                ];
            } else {
                $error = 'Failed to send notification. Check Firebase server key.';
            }
            
        } elseif ($notification_type === 'article') {
            // Send article notification
            $article_id = (int)$_POST['article_id'];
            $article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$article_id]);
            
            if ($article) {
                $category = $db->fetchOne("SELECT name FROM categories WHERE id = ?", [$article['category_id']]);
                $categoryName = $category['name'] ?? '';
                
                if ($article['is_breaking']) {
                    $response = $fcm->sendBreakingNewsNotification($article_id, $article['title'], $article['slug']);
                    $type = 'Breaking News';
                } else {
                    $response = $fcm->sendArticleNotification($article_id, $article['title'], $article['slug'], $categoryName);
                    $type = 'Article';
                }
                
                if ($response) {
                    $result = [
                        'success' => true,
                        'message' => "$type notification sent for: {$article['title']}",
                        'article' => $article,
                        'response' => json_decode($response, true)
                    ];
                } else {
                    $error = 'Failed to send article notification';
                }
            } else {
                $error = 'Article not found';
            }
            
        } elseif ($notification_type === 'specific_user') {
            // Send to specific user
            $user_id = (int)$_POST['user_id'];
            $user = $db->fetchOne("SELECT fcm_token, full_name FROM users WHERE id = ?", [$user_id]);
            
            if ($user && !empty($user['fcm_token'])) {
                $title = trim($_POST['user_title'] ?? 'Hello ' . $user['full_name']);
                $body = trim($_POST['user_body'] ?? 'This is a test message for you');
                
                $data = [
                    'notification_type' => 'custom',
                    'type' => 'direct_message'
                ];
                
                $response = $fcm->sendToTokens([$user['fcm_token']], $title, $body, $data);
                
                if ($response) {
                    $result = [
                        'success' => true,
                        'message' => "Notification sent to {$user['full_name']}",
                        'user' => $user,
                        'response' => json_decode($response, true)
                    ];
                } else {
                    $error = 'Failed to send notification to user';
                }
            } else {
                $error = 'User not found or no FCM token';
            }
        }
        
    } catch (Exception $e) {
        $error = 'Exception: ' . $e->getMessage();
    }
}

// Get users with tokens for testing
$test_users = $db->fetchAll("SELECT id, full_name, email, fcm_token, created_at 
    FROM users 
    WHERE fcm_token IS NOT NULL AND fcm_token != '' 
    ORDER BY created_at DESC 
    LIMIT 10");

include 'includes/header.php';
?>

<style>
.debug-card {
    border-left: 4px solid #0d6efd;
}
.debug-card.success {
    border-left-color: #198754;
}
.debug-card.error {
    border-left-color: #dc3545;
}
.debug-card.warning {
    border-left-color: #ffc107;
}
.stat-box {
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}
.code-block {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 15px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    overflow-x: auto;
}
</style>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-bell-fill"></i> Test FCM Notifications
            </h1>
            <a href="notifications.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Notifications
            </a>
        </div>

        <!-- Configuration Status -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-box <?= $firebase_configured ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                    <h5>Firebase Config</h5>
                    <h2><?= $firebase_configured ? 'Active' : 'Missing' ?></h2>
                    <small><?= $debug_info['firebase_key_length'] ?> chars</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-box bg-primary text-white">
                    <h5>Total Users</h5>
                    <h2><?= $debug_info['total_users'] ?></h2>
                    <small>Registered</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-box bg-info text-white">
                    <h5>With FCM Tokens</h5>
                    <h2><?= $debug_info['users_with_tokens'] ?></h2>
                    <small>Can receive notifications</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-box <?= $debug_info['active_tokens'] > 0 ? 'bg-success' : 'bg-warning' ?> text-white">
                    <h5>Active Tokens</h5>
                    <h2><?= $debug_info['active_tokens'] ?></h2>
                    <small>Ready to notify</small>
                </div>
            </div>
        </div>

        <!-- Result Messages -->
        <?php if ($result): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5><i class="bi bi-check-circle-fill"></i> Success!</h5>
            <p class="mb-2"><?= htmlspecialchars($result['message']) ?></p>
            
            <?php if (isset($result['response'])): ?>
            <details class="mt-3">
                <summary style="cursor: pointer;">View Full Response</summary>
                <div class="code-block mt-2">
                    <?= htmlspecialchars(json_encode($result['response'], JSON_PRETTY_PRINT)) ?>
                </div>
            </details>
            <?php endif; ?>
            
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="bi bi-exclamation-triangle-fill"></i> Error!</h5>
            <p class="mb-0"><?= htmlspecialchars($error) ?></p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!$firebase_configured): ?>
        <div class="alert alert-warning">
            <h5><i class="bi bi-exclamation-triangle"></i> Firebase Not Configured</h5>
            <p>Firebase Server Key is not set. Please add it to your <code>config/config.php</code>:</p>
            <div class="code-block">
                define('FIREBASE_SERVER_KEY', 'your-firebase-server-key-here');
            </div>
            <small class="d-block mt-2">
                Get your server key from: 
                <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a> 
                → Project Settings → Cloud Messaging → Server Key
            </small>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Test Notification -->
            <div class="col-md-6 mb-4">
                <div class="card debug-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-broadcast"></i> Send Test Notification</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="notification_type" value="test">
                            
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" name="test_title" class="form-control" 
                                       value="Test Notification" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="test_body" class="form-control" rows="3" required>This is a test notification. If you see this, notifications are working!</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Image URL (Optional)</label>
                                <input type="url" name="test_image" class="form-control" 
                                       placeholder="https://example.com/image.jpg">
                            </div>
                            
                            <div class="alert alert-info mb-3">
                                <small>
                                    <i class="bi bi-info-circle"></i> 
                                    This will send to all users subscribed to "all" topic.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100" <?= !$firebase_configured ? 'disabled' : '' ?>>
                                <i class="bi bi-send"></i> Send Test Notification
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Article Notification -->
            <div class="col-md-6 mb-4">
                <div class="card debug-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-newspaper"></i> Send Article Notification</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="notification_type" value="article">
                            
                            <div class="mb-3">
                                <label class="form-label">Select Article</label>
                                <select name="article_id" class="form-select" required>
                                    <option value="">-- Choose Article --</option>
                                    <?php foreach ($recent_articles as $article): ?>
                                    <option value="<?= $article['id'] ?>">
                                        <?= htmlspecialchars($article['title']) ?> 
                                        (<?= $article['status'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="alert alert-info mb-3">
                                <small>
                                    <i class="bi bi-info-circle"></i> 
                                    Will send with article details and deep link.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100" <?= !$firebase_configured ? 'disabled' : '' ?>>
                                <i class="bi bi-send"></i> Send Article Notification
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Send to Specific User -->
            <div class="col-md-12 mb-4">
                <div class="card debug-card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="bi bi-person-fill"></i> Send to Specific User</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="notification_type" value="specific_user">
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Select User</label>
                                    <select name="user_id" class="form-select" required>
                                        <option value="">-- Choose User --</option>
                                        <?php foreach ($test_users as $user): ?>
                                        <option value="<?= $user['id'] ?>">
                                            <?= htmlspecialchars($user['full_name']) ?> 
                                            (<?= htmlspecialchars($user['email']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Only users with FCM tokens shown</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="user_title" class="form-control" 
                                           value="Personal Test Message" required>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Message</label>
                                    <input type="text" name="user_body" class="form-control" 
                                           value="This is a direct notification to you!" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-warning w-100" <?= !$firebase_configured ? 'disabled' : '' ?>>
                                <i class="bi bi-send"></i> Send to Selected User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Information -->
        <div class="card debug-card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bug"></i> Debug Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Configuration</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Firebase Configured:</span>
                                <span class="badge bg-<?= $firebase_configured ? 'success' : 'danger' ?>">
                                    <?= $firebase_configured ? 'Yes' : 'No' ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Server Key Length:</span>
                                <span><?= $debug_info['firebase_key_length'] ?> characters</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>FCM Helper:</span>
                                <span class="badge bg-<?= class_exists('FCMHelper') ? 'success' : 'danger' ?>">
                                    <?= class_exists('FCMHelper') ? 'Loaded' : 'Missing' ?>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>User Statistics</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Total Users:</span>
                                <strong><?= $debug_info['total_users'] ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Users with Tokens:</span>
                                <strong><?= $debug_info['users_with_tokens'] ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Active Tokens:</span>
                                <strong><?= $debug_info['active_tokens'] ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Users with FCM Tokens -->
        <?php if (!empty($test_users)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Users with FCM Tokens</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>FCM Token</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($test_users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <code style="font-size: 11px;">
                                        <?= substr($user['fcm_token'], 0, 30) ?>...
                                    </code>
                                </td>
                                <td><?= timeAgo($user['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Instructions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-question-circle"></i> How to Test</h5>
            </div>
            <div class="card-body">
                <ol>
                    <li><strong>Check Configuration:</strong> Ensure Firebase Server Key is set in config.php</li>
                    <li><strong>Verify Users:</strong> Make sure users have FCM tokens (they should log in to the app first)</li>
                    <li><strong>Send Test Notification:</strong> Use the "Send Test Notification" form above</li>
                    <li><strong>Check App:</strong> Open your Flutter app and check if notification appears</li>
                    <li><strong>Verify in App:</strong> 
                        <ul>
                            <li>Check notification bar (system notification)</li>
                            <li>Check in-app notifications screen</li>
                            <li>Look for console logs in Flutter debug</li>
                        </ul>
                    </li>
                </ol>
                
                <hr>
                
                <h6>Troubleshooting:</h6>
                <ul>
                    <li>If users have 0 tokens: Users need to open the app at least once</li>
                    <li>If notification fails: Check Firebase server key is valid</li>
                    <li>If app doesn't receive: Check app's Firebase configuration</li>
                    <li>Check Flutter console for error messages</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
