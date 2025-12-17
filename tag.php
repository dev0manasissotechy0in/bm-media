<?php
require_once 'config/config.php';

// Get tag slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL);
}

$db = Database::getInstance();

// Get tag details
$tag = $db->fetchOne("SELECT * FROM tags WHERE slug = ?", [$slug]);

if (!$tag) {
    redirect(BASE_URL . '/404.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total articles
$total = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM article_tags at
    INNER JOIN articles a ON at.article_id = a.id
    WHERE at.tag_id = ? AND a.status = 'published'
", [$tag['id']])['total'];

$total_pages = ceil($total / $per_page);

// Get articles
$articles = $db->fetchAll("
    SELECT a.*, c.name as category_name, c.slug as category_slug
    FROM article_tags at
    INNER JOIN articles a ON at.article_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE at.tag_id = ? AND a.status = 'published'
    ORDER BY a.published_at DESC
    LIMIT ? OFFSET ?
", [$tag['id'], $per_page, $offset]);

// Page meta
$page_title = $tag['name'] . ' - Tagged Articles';
$page_description = 'Articles tagged with ' . $tag['name'];
$page_keywords = $tag['name'] . ', news';

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Tag Header -->
    <div class="tag-header mb-4">
        <h1 class="display-4">
            <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($tag['name']) ?>
        </h1>
        <p class="text-muted"><?= $total ?> articles tagged</p>
    </div>

    <!-- Articles Grid -->
    <?php if (!empty($articles)): ?>
    <div class="row g-4">
        <?php foreach ($articles as $article): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>">
                    <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($article['title']) ?>"
                         onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                </a>
                <div class="card-body">
                    <?php if ($article['category_name']): ?>
                    <div class="mb-2">
                        <a href="<?= BASE_URL ?>/category/<?= $article['category_slug'] ?>" 
                           class="badge bg-primary text-decoration-none">
                            <?= htmlspecialchars($article['category_name']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
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
        <?= generatePagination($page, $total_pages, BASE_URL . '/tag/' . $slug) ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="alert alert-info">
        No articles found with this tag.
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
