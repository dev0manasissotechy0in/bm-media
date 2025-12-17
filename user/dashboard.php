<?php
/**
 * User Dashboard
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get user details
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

if (!$user) {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// Get user statistics
$saved_count = $db->fetchOne("SELECT COUNT(*) as count FROM user_saved_articles WHERE user_id = ?", [$user_id]);
$comments_count = $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE user_id = ?", [$user_id]);
$likes_count = $db->fetchOne("SELECT COUNT(*) as count FROM user_article_likes WHERE user_id = ?", [$user_id]);

// Get recently saved articles
$saved_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.thumbnail, 
     c.name as category_name, c.slug as category_slug,
     sa.saved_at
     FROM user_saved_articles sa
     JOIN articles a ON sa.article_id = a.id
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE sa.user_id = ? AND a.status = 'published'
     ORDER BY sa.saved_at DESC
     LIMIT 5",
    [$user_id]
);

// Get recent comments
$recent_comments = $db->fetchAll(
    "SELECT c.id, c.comment_text, c.created_at, 
     a.title as article_title, a.slug as article_slug
     FROM comments c
     JOIN articles a ON c.article_id = a.id
     WHERE c.user_id = ? AND c.status = 'approved'
     ORDER BY c.created_at DESC
     LIMIT 5",
    [$user_id]
);

// Get reading history (liked articles)
$liked_articles = $db->fetchAll(
    "SELECT a.id, a.title, a.slug, a.thumbnail, 
     c.name as category_name, c.slug as category_slug,
     al.liked_at
     FROM user_article_likes al
     JOIN articles a ON al.article_id = a.id
     LEFT JOIN categories c ON a.category_id = c.id
     WHERE al.user_id = ? AND a.status = 'published'
     ORDER BY al.liked_at DESC
     LIMIT 5",
    [$user_id]
);

$page_title = 'My Dashboard';
include '../includes/header.php';
?>

<main class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <?php if ($user['profile_photo']): ?>
                        <img src="<?= BASE_URL ?>/uploads/users/<?= htmlspecialchars($user['profile_photo']) ?>" 
                             class="rounded-circle mb-3" width="100" height="100" style="object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 100px; height: 100px; font-size: 2rem;">
                            <?= strtoupper(substr($user['full_name'] ?? $user['email'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    
                    <h5 class="mb-1"><?= htmlspecialchars($user['full_name'] ?? 'User') ?></h5>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <a href="<?= BASE_URL ?>/user/profile.php" class="btn btn-primary btn-sm w-100 mb-2">
                        <i class="bi bi-pencil"></i> Edit Profile
                    </a>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title mb-3">Quick Links</h6>
                    <div class="list-group list-group-flush">
                        <a href="<?= BASE_URL ?>/user/dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>/user/saved-articles.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bookmark"></i> Saved Articles
                            <span class="badge bg-primary float-end"><?= $saved_count['count'] ?></span>
                        </a>
                        <a href="<?= BASE_URL ?>/user/profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person"></i> Profile Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <h2 class="mb-4">Welcome back, <?= htmlspecialchars($user['full_name'] ?? 'User') ?>!</h2>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-primary">
                        <div class="card-body text-center">
                            <i class="bi bi-bookmark-fill text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-2 mb-0"><?= $saved_count['count'] ?></h3>
                            <p class="text-muted mb-0">Saved Articles</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-success">
                        <div class="card-body text-center">
                            <i class="bi bi-chat-dots-fill text-success" style="font-size: 2rem;"></i>
                            <h3 class="mt-2 mb-0"><?= $comments_count['count'] ?></h3>
                            <p class="text-muted mb-0">Comments Posted</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm border-danger">
                        <div class="card-body text-center">
                            <i class="bi bi-heart-fill text-danger" style="font-size: 2rem;"></i>
                            <h3 class="mt-2 mb-0"><?= $likes_count['count'] ?></h3>
                            <p class="text-muted mb-0">Articles Liked</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recently Saved Articles -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-bookmark"></i> Recently Saved</h5>
                        <a href="<?= BASE_URL ?>/user/saved-articles.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($saved_articles)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i><br>
                            No saved articles yet. Start saving articles to read later!
                        </p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($saved_articles as $article): ?>
                            <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($article['title']) ?></h6>
                                    <small class="text-muted"><?= timeAgo($article['saved_at']) ?></small>
                                </div>
                                <small class="text-muted">
                                    <span class="badge bg-primary"><?= htmlspecialchars($article['category_name']) ?></span>
                                </small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Comments -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-chat-dots"></i> Recent Comments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_comments)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-chat" style="font-size: 3rem;"></i><br>
                            No comments yet. Share your thoughts on articles!
                        </p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_comments as $comment): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between mb-2">
                                    <h6 class="mb-1">
                                        <a href="<?= BASE_URL ?>/article/<?= $comment['article_slug'] ?>#comment-<?= $comment['id'] ?>">
                                            <?= htmlspecialchars($comment['article_title']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted"><?= timeAgo($comment['created_at']) ?></small>
                                </div>
                                <p class="mb-0 text-muted"><?= htmlspecialchars(substr($comment['comment_text'], 0, 150)) ?>...</p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Liked Articles -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-heart"></i> Recently Liked</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($liked_articles)): ?>
                        <p class="text-muted text-center py-4">
                            <i class="bi bi-heart" style="font-size: 3rem;"></i><br>
                            No liked articles yet. Like articles you enjoy!
                        </p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($liked_articles as $article): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <?php if (!empty($article['thumbnail'])): ?>
                                    <img src="<?= BASE_URL ?>/uploads/articles/<?= htmlspecialchars($article['thumbnail']) ?>" 
                                         class="card-img-top" style="height: 150px; object-fit: cover;">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($article['title']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <span class="badge bg-primary"><?= htmlspecialchars($article['category_name']) ?></span>
                                            • <?= timeAgo($article['liked_at']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>