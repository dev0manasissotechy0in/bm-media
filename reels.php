<?php
/**
 * Reels - Short-form video content
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Pagination
$page_num = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page_num - 1) * $per_page;

// Category filter
$category_filter = $_GET['category'] ?? '';
$where = ["r.status = 'active'"];
$params = [];

if (!empty($category_filter)) {
    $where[] = "r.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where);

// Get reels from both reels table and articles with reel content
$reels_raw = $db->fetchAll("
    SELECT 
        r.id,
        r.title,
        r.video_url,
        r.video_file,
        r.thumbnail,
        r.views_count,
        r.likes_count,
        r.created_at,
        r.article_id,
        c.name as category_name,
        c.slug as category_slug,
        'standalone' as reel_type
    FROM reels r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE $where_clause
    
    UNION
    
    SELECT 
        a.id,
        a.title,
        a.media_reel_url as video_url,
        a.media_reel_file as video_file,
        a.thumbnail,
        a.views_count,
        a.likes_count,
        a.published_at as created_at,
        a.id as article_id,
        c.name as category_name,
        c.slug as category_slug,
        'article' as source_type
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.content_type = 'reel' 
        AND a.status = 'published'
        AND (a.media_reel_url IS NOT NULL OR a.media_reel_file IS NOT NULL)
    
    ORDER BY created_at DESC
    LIMIT $per_page OFFSET $offset
", $params);

// Add slugs for standalone reels and article reels
$reels = array_map(function($reel) use ($db) {
    if ($reel['reel_type'] === 'article') {
        // Get article slug
        $article = $db->fetchOne("SELECT slug FROM articles WHERE id = ?", [$reel['id']]);
        $reel['slug'] = $article['slug'] ?? generateSlug($reel['title']);
    } else {
        // Generate slug for standalone reel
        $reel['slug'] = generateSlug($reel['title']);
    }
    return $reel;
}, $reels_raw);

// Get total count (reels + articles with reel content)
$total_reels = $db->fetchOne("SELECT COUNT(*) as count FROM reels r WHERE $where_clause", $params);
$total_articles = $db->fetchOne("
    SELECT COUNT(*) as count 
    FROM articles 
    WHERE content_type = 'reel' 
        AND status = 'published'
        AND (media_reel_url IS NOT NULL OR media_reel_file IS NOT NULL)
");
$total = ['count' => $total_reels['count'] + $total_articles['count']];
$total_pages = ceil($total['count'] / $per_page);

// Get categories with reels
$categories = $db->fetchAll("
    SELECT c.*, COUNT(r.id) as reel_count
    FROM categories c
    INNER JOIN reels r ON c.id = r.category_id AND r.status = 'active'
    WHERE c.status = 'active'
    GROUP BY c.id
    ORDER BY c.name ASC
");

$page_title = 'Reels - Short Videos';
$page_description = 'Watch trending short videos and news highlights';

include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row mb-4">
        <div class="col-lg-8">
            <h1 class="display-4"><i class="bi bi-play-circle-fill text-primary"></i> Reels</h1>
            <p class="lead text-muted">Short-form video content, news highlights, and trending moments</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <select class="form-select" id="categoryFilter" onchange="window.location.href='?category='+this.value">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?> (<?= $cat['reel_count'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <?php if (empty($reels)): ?>
    <div class="text-center py-5">
        <i class="bi bi-film text-muted" style="font-size: 5rem;"></i>
        <h3 class="mt-4">No Reels Available</h3>
        <p class="text-muted">Check back later for new video content</p>
    </div>
    <?php else: ?>
    
    <div class="row g-3">
        <?php foreach ($reels as $reel): ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2 reel-col">
            <a href="<?= BASE_URL ?>/reel/<?= $reel['slug'] ?>/<?= $reel['id'] ?>/" class="text-decoration-none">
                <div class="card shadow-sm reel-card">
                    <div class="reel-thumbnail">
                        <?php if (!empty($reel['thumbnail'])): ?>
                        <?php 
                        // Determine correct thumbnail path based on source
                        $thumbnail_path = $reel['thumbnail'];
                        if ($reel['reel_type'] === 'article') {
                            $thumbnail_url = UPLOADS_URL . '/articles/' . $thumbnail_path;
                        } elseif (strpos($thumbnail_path, 'uploads/') === 0) {
                            $thumbnail_url = (BASE_URL ? BASE_URL . '/' : '/') . $thumbnail_path;
                        } else {
                            $thumbnail_url = UPLOADS_URL . '/reels/' . $thumbnail_path;
                        }
                        ?>
                        <img src="<?= htmlspecialchars($thumbnail_url) ?>" 
                             class="card-img-top" alt="<?= htmlspecialchars($reel['title']) ?>"
                             onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        <?php else: ?>
                        <div class="bg-dark d-flex align-items-center justify-content-center h-100">
                            <i class="bi bi-play-circle text-white" style="font-size: 3rem; opacity: 0.5;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-gradient" 
                             style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                            <h6 class="text-white mb-1"><?= htmlspecialchars($reel['title']) ?></h6>
                            <?php if ($reel['category_name']): ?>
                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($reel['category_name']) ?></span>
                            <?php endif; ?>
                            <div class="text-white-50 small">
                                <i class="bi bi-eye"></i> <?= number_format($reel['views_count']) ?>
                                <i class="bi bi-heart ms-2"></i> <?= number_format($reel['likes_count']) ?>
                            </div>
                        </div>
                        
                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge bg-dark bg-opacity-75">
                                <i class="bi bi-play-circle"></i> Reel
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-5">
        <ul class="pagination justify-content-center">
            <?php if ($page_num > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page_num - 1 ?><?= $category_filter ? '&category='.$category_filter : '' ?>">Previous</a>
            </li>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page_num - 2); $i <= min($total_pages, $page_num + 2); $i++): ?>
            <li class="page-item <?= $i === $page_num ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $category_filter ? '&category='.$category_filter : '' ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <?php if ($page_num < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page_num + 1 ?><?= $category_filter ? '&category='.$category_filter : '' ?>">Next</a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</main>

<style>
/* Reel Cards Container */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -0.5rem;
}

/* Reel Column - 5 per row on desktop */
.reel-col {
    padding: 0 0.5rem;
    margin-bottom: 1rem;
}

@media (min-width: 1200px) {
    .reel-col {
        flex: 0 0 20%;
        max-width: 20%;
    }
}

@media (min-width: 992px) and (max-width: 1199.98px) {
    .reel-col {
        flex: 0 0 25%;
        max-width: 25%;
    }
}

@media (min-width: 768px) and (max-width: 991.98px) {
    .reel-col {
        flex: 0 0 33.333%;
        max-width: 33.333%;
    }
}

@media (min-width: 576px) and (max-width: 767.98px) {
    .reel-col {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 575.98px) {
    .reel-col {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

/* Reel Card Styling */
.reel-card {
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: none;
    height: 100%;
}

.reel-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.25) !important;
}

.reel-thumbnail {
    position: relative;
    width: 100%;
    padding-bottom: 177.78%; /* 9:16 aspect ratio */
    overflow: hidden;
    background: #000;
}

.reel-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.reel-card:hover .reel-thumbnail img {
    transform: scale(1.05);
}

a.text-decoration-none {
    display: block;
    height: 100%;
}
</style>

<?php include 'includes/footer.php'; ?>