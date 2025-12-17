<?php
/**
 * Saved Articles
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

// Handle unsave action
if (isset($_GET['unsave'])) {
    $article_id = (int)$_GET['unsave'];
    $db->delete('user_saved_articles', 'user_id = ? AND article_id = ?', [$user_id, $article_id]);
    
    // Also decrement saves count
    $db->query("UPDATE articles SET saves_count = GREATEST(0, saves_count - 1) WHERE id = ?", [$article_id]);
    
    $_SESSION['success'] = 'Article removed from saved list';
    header('Location: saved-articles.php');
    exit;
}

// Pagination
$page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page_num - 1) * $per_page;

// Get saved articles
$saved_articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name, c.slug as category_slug,
     sa.saved_at,
     CASE 
         WHEN a.author_type = 'admin' THEN ad.full_name
         WHEN a.author_type = 'reporter' THEN r.full_name
         ELSE 'Unknown'
     END as author_name
     FROM user_saved_articles sa
     JOIN articles a ON sa.article_id = a.id
     LEFT JOIN categories c ON a.category_id = c.id
     LEFT JOIN admin_users ad ON a.author_id = ad.id AND a.author_type = 'admin'
     LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
     WHERE sa.user_id = ? AND a.status = 'published'
     ORDER BY sa.saved_at DESC
     LIMIT $per_page OFFSET $offset",
    [$user_id]
);

// Get total count
$total = $db->fetchOne("SELECT COUNT(*) as count FROM user_saved_articles WHERE user_id = ?", [$user_id]);
$total_pages = ceil($total['count'] / $per_page);

$page_title = 'Saved Articles';
include '../includes/header.php';
?>

<main class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">My Account</h6>
                    <div class="list-group list-group-flush">
                        <a href="<?= BASE_URL ?>/user/dashboard.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="<?= BASE_URL ?>/user/saved-articles.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-bookmark"></i> Saved Articles
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="bi bi-bookmark-fill"></i> Saved Articles</h2>
                <span class="badge bg-primary" style="font-size: 1rem;"><?= $total['count'] ?> Total</span>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (empty($saved_articles)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-bookmark" style="font-size: 5rem; color: #ddd;"></i>
                        <h4 class="mt-3">No Saved Articles</h4>
                        <p class="text-muted">Articles you save will appear here for easy access later.</p>
                        <a href="<?= BASE_URL ?>" class="btn btn-primary">
                            <i class="bi bi-house"></i> Browse Articles
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($saved_articles as $article): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($article['thumbnail'])): ?>
                            <img src="<?= BASE_URL ?>/uploads/articles/<?= htmlspecialchars($article['thumbnail']) ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;"
                                 onerror="this.style.display='none';">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <div class="mb-2">
                                    <a href="<?= BASE_URL ?>/category/<?= $article['category_slug'] ?>" 
                                       class="badge bg-primary text-decoration-none">
                                        <?= htmlspecialchars($article['category_name']) ?>
                                    </a>
                                </div>
                                
                                <h5 class="card-title">
                                    <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </a>
                                </h5>
                                
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars(substr(strip_tags($article['content']), 0, 100)) ?>...
                                </p>
                            </div>
                            
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Saved <?= timeAgo($article['saved_at']) ?>
                                    </small>
                                    <button onclick="unsaveArticle(<?= $article['id'] ?>, '<?= htmlspecialchars(addslashes($article['title'])) ?>')" 
                                            class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page_num > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page_num - 1 ?>">Previous</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page_num ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page_num < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page_num + 1 ?>">Next</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function unsaveArticle(articleId, title) {
    if (confirm('Remove "' + title + '" from saved articles?')) {
        window.location.href = 'saved-articles.php?unsave=' + articleId;
    }
}
</script>

<?php include '../includes/footer.php'; ?>