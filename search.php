<?php
require_once 'config/config.php';

// Get search query
$q = trim($_GET['q'] ?? '');

$db = Database::getInstance();
$articles = [];
$total = 0;

if (!empty($q)) {
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 12;
    $offset = ($page - 1) * $per_page;
    
    // Sanitize search query
    $search_term = Security::sanitize($q);
    
    // Get total results
    $total = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM articles 
        WHERE status = 'published' 
        AND (title LIKE ? OR description LIKE ? OR content LIKE ?)
    ", ["%$search_term%", "%$search_term%", "%$search_term%"])['total'];
    
    $total_pages = ceil($total / $per_page);
    
    // Get search results
    $articles = $db->fetchAll("
        SELECT a.*, c.name as category_name, c.slug as category_slug
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.status = 'published' 
        AND (a.title LIKE ? OR a.description LIKE ? OR a.content LIKE ?)
        ORDER BY 
            CASE 
                WHEN a.title LIKE ? THEN 1
                WHEN a.description LIKE ? THEN 2
                ELSE 3
            END,
            a.published_at DESC
        LIMIT ? OFFSET ?
    ", [
        "%$search_term%", "%$search_term%", "%$search_term%",
        "%$search_term%", "%$search_term%",
        $per_page, $offset
    ]);
}

// Page meta
$page_title = !empty($q) ? 'Search Results for: ' . htmlspecialchars($q) : 'Search';
$page_description = 'Search results';

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Search Header -->
    <div class="search-header mb-4">
        <h1 class="display-5">Search</h1>
        
        <!-- Search Form -->
        <form method="GET" action="<?= BASE_URL ?>/search" class="mb-3">
            <div class="input-group input-group-lg">
                <input type="text" 
                       name="q" 
                       class="form-control" 
                       placeholder="Search articles..." 
                       value="<?= htmlspecialchars($q) ?>"
                       required>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>
        
        <?php if (!empty($q)): ?>
        <p class="text-muted">
            Found <strong><?= formatNumber($total) ?></strong> results for "<strong><?= htmlspecialchars($q) ?></strong>"
        </p>
        <?php endif; ?>
    </div>

    <!-- Search Results -->
    <?php if (!empty($articles)): ?>
    <div class="row g-4">
        <?php foreach ($articles as $article): ?>
        <div class="col-12">
            <div class="card">
                <div class="row g-0">
                    <div class="col-md-3">
                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>">
                            <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                                 class="img-fluid h-100 w-100" 
                                 style="object-fit: cover;"
                                 alt="<?= htmlspecialchars($article['title']) ?>"
                                 onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        </a>
                    </div>
                    <div class="col-md-9">
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
                            
                            <h4 class="card-title">
                                <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h4>
                            
                            <p class="card-text text-muted">
                                <?= htmlspecialchars(truncateText($article['description'], 200)) ?>
                            </p>
                            
                            <div class="article-meta">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> <?= timeAgo($article['published_at'], $article['created_at']) ?>
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-eye"></i> <?= formatNumber($article['views_count']) ?> views
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-heart"></i> <?= formatNumber($article['likes_count']) ?> likes
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
        <?= generatePagination($page, $total_pages, BASE_URL . '/search?q=' . urlencode($q)) ?>
    </nav>
    <?php endif; ?>

    <?php elseif (!empty($q)): ?>
    <div class="alert alert-info">
        <h5><i class="bi bi-info-circle"></i> No results found</h5>
        <p class="mb-0">Try using different keywords or check your spelling.</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
