<?php
/**
 * Subcategory Page
 */

require_once 'config/config.php';

// Get slugs from URL
$parent_slug = $_GET['parent_slug'] ?? '';
$slug = $_GET['slug'] ?? '';

if (empty($parent_slug) || empty($slug)) {
    redirect(BASE_URL . '/404.php');
}

$db = Database::getInstance();

// Get parent category
$parent = $db->fetchOne("SELECT * FROM categories WHERE slug = ? AND status = 'active'", [$parent_slug]);

if (!$parent) {
    redirect(BASE_URL . '/404.php');
}

// Get subcategory
$category = $db->fetchOne("SELECT * FROM categories WHERE slug = ? AND parent_id = ? AND status = 'active'", [$slug, $parent['id']]);

if (!$category) {
    redirect(BASE_URL . '/404.php');
}

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
$page_title = $category['seo_title'] ?: $parent['name'] . ' - ' . $category['name'] . ' News';
$page_description = $category['seo_description'] ?: 'Latest ' . $category['name'] . ' news under ' . $parent['name'];
$page_keywords = $category['seo_keywords'] ?: $parent['name'] . ', ' . $category['name'] . ', news';

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/category/<?= $parent['slug'] ?>"><?= htmlspecialchars($parent['name']) ?></a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($category['name']) ?></li>
        </ol>
    </nav>

    <!-- Category Header -->
    <div class="category-header mb-4">
        <div class="d-flex align-items-center">
            <?php if ($category['icon']): ?>
            <img src="<?= UPLOADS_URL ?>/categories/<?= $category['icon'] ?>" 
                 alt="<?= htmlspecialchars($category['name']) ?>" 
                 class="me-3" style="width: 50px; height: 50px;">
            <?php endif; ?>
            <div>
                <h1 class="display-4 mb-0"><?= htmlspecialchars($category['name']) ?></h1>
                <p class="text-muted mb-0"><?= $total ?> articles found in <?= htmlspecialchars($parent['name']) ?></p>
            </div>
        </div>
    </div>

    <!-- Articles Grid -->
    <?php if (!empty($articles)): ?>
    <div class="row g-4">
        <?php foreach ($articles as $article): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <?php if ($article['thumbnail']): ?>
                <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                     class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                <?php endif; ?>
                <div class="card-body">
                    <?php if ($article['is_live']): ?>
                    <span class="badge bg-danger mb-2">
                        <i class="bi bi-broadcast"></i> LIVE
                    </span>
                    <?php endif; ?>
                    
                    <h5 class="card-title">
                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                            <?= htmlspecialchars($article['title']) ?>
                        </a>
                    </h5>
                    
                    <?php if ($article['description']): ?>
                    <p class="card-text text-muted">
                        <?= substr(strip_tags($article['description']), 0, 100) ?>...
                    </p>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <small class="text-muted">
                        <i class="bi bi-clock"></i> <?= timeAgo($article['published_at'], $article['created_at']) ?>
                        <span class="float-end">
                            <i class="bi bi-eye"></i> <?= number_format($article['views_count']) ?>
                        </span>
                    </small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
    
    <?php else: ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> No articles found in this subcategory.
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
