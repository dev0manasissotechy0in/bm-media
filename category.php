<?php
require_once 'config/config.php';

// Get category slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL);
}

$db = Database::getInstance();

// Initialize ads manager
require_once 'includes/AdsManager.php';
AdsManager::init($db);

// Get category details
$category = $db->fetchOne("SELECT * FROM categories WHERE slug = ? AND status = 'active'", [$slug]);

if (!$category) {
    redirect(BASE_URL . '/404.php');
}

// Get subcategories for this category (for dynamic header)
$subcategories = $db->fetchAll(
    "SELECT id, name, slug, icon FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY order_id ASC, name ASC",
    [$category['id']]
);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total articles
$total = $db->count('articles', 'category_id = ? AND status = ?', [$category['id'], 'published']);
$total_pages = ceil($total / $per_page);

// Get articles
$articles = $db->fetchAll("
    SELECT a.*, c.name as category_name, c.slug as category_slug
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.category_id = ? AND a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT ? OFFSET ?
", [$category['id'], $per_page, $offset]);

// Page meta
$page_title = $category['seo_title'] ?: $category['name'] . ' News';
$page_description = $category['seo_description'] ?: 'Latest ' . $category['name'] . ' news and updates';
$page_keywords = $category['seo_keywords'] ?: $category['name'] . ', news';

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Category Ad -->
    <?php echo AdsManager::showCustomAd('category', 'top'); ?>

    <!-- Category Header -->
    <!-- <div class="category-header mb-4">
        <h1 class="display-4"><?= htmlspecialchars($category['name']) ?></h1>
        <?php if (!empty($category['description'])): ?>
        <p class="lead"><?= htmlspecialchars($category['description']) ?></p>
        <?php endif; ?>
        
        <p class="text-muted"><?= $total ?> articles found</p>
    </div> -->

    <!-- Articles Grid -->
    <?php if (!empty($articles)): ?>
    <div class="row g-4">
        <?php foreach ($articles as $article): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="position-relative">
                    <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>">
                        <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                             class="card-img-top" 
                             alt="<?= htmlspecialchars($article['title']) ?>"
                             onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    </a>
                    <?php if ($article['content_type'] === 'video'): ?>
                    <div class="video-play-overlay">
                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="play-button">
                            <i class="bi bi-play-circle-fill"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($article['is_breaking']): ?>
                    <span class="badge bg-danger mb-2">BREAKING</span>
                    <?php endif; ?>
                    <?php if ($article['is_live']): ?>
                    <span class="badge bg-danger mb-2 pulse">LIVE</span>
                    <?php endif; ?>
                    
                    <h5 class="card-title">
                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                            <?= htmlspecialchars(truncateText($article['title'], 80)) ?>
                        </a>
                    </h5>
                    
                    <p class="card-text text-muted">
                        <?= htmlspecialchars(truncateText($article['description'], 120)) ?>
                    </p>
                    
                    <div class="article-meta">
                        <small class="text-muted">
                            <i class="bi bi-calendar"></i> <?= timeAgo($article['published_at'], $article['created_at']) ?>
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-eye"></i> <?= formatNumber($article['views_count']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <?= generatePagination($page, $total_pages, BASE_URL . '/category/' . $slug) ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="alert alert-info">
        No articles found in this category.
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
