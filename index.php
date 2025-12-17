<?php
require_once 'config/config.php';

// Page meta information
$page_title = 'Home';

// Get database instance
$db = Database::getInstance();

// Initialize ads manager
require_once 'includes/AdsManager.php';
AdsManager::init($db);

// Fetch live news (if any)
$live_news = $db->fetchAll("SELECT * FROM articles WHERE is_live = 1 AND status = 'published' ORDER BY updated_at DESC LIMIT 3");

// Fetch featured news
$featured_news = $db->fetchAll("SELECT a.*, c.name as category_name, c.slug as category_slug 
                                 FROM articles a 
                                 LEFT JOIN categories c ON a.category_id = c.id 
                                 WHERE a.is_featured = 1 AND a.status = 'published' 
                                 ORDER BY a.published_at DESC LIMIT 5");

// Fetch top news
$top_news = $db->fetchAll("SELECT a.*, c.name as category_name, c.slug as category_slug 
                           FROM articles a 
                           LEFT JOIN categories c ON a.category_id = c.id 
                           WHERE a.is_top_news = 1 AND a.status = 'published' 
                           ORDER BY a.published_at DESC LIMIT 10");

// Fetch gallery articles
$gallery_articles = $db->fetchAll("SELECT * FROM articles WHERE content_type = 'gallery' AND status = 'published' ORDER BY published_at DESC LIMIT 6");

// Fetch video articles
$video_articles = $db->fetchAll("SELECT * FROM articles WHERE content_type = 'video' AND status = 'published' ORDER BY published_at DESC LIMIT 6");

// Fetch ALL reels (both standalone reels and article reels combined)
$all_reels = [];

// Get standalone reels from reels table
$standalone_reels_raw = $db->fetchAll("
    SELECT 
        r.id,
        r.title,
        r.description,
        r.video_url,
        r.video_file,
        r.thumbnail,
        r.views_count as views,
        r.likes_count as likes,
        r.created_at,
        r.article_id,
        c.name as category_name,
        c.slug as category_slug,
        'standalone' as reel_type
    FROM reels r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE r.status = 'active'
    ORDER BY r.created_at DESC
    LIMIT 8
");

// Generate slugs for standalone reels
$standalone_reels = array_map(function($reel) {
    $reel['slug'] = generateSlug($reel['title']);
    return $reel;
}, $standalone_reels_raw);

// Get article-based reels
$reel_articles = $db->fetchAll("
    SELECT 
        a.id,
        a.title,
        a.description,
        a.media_reel_url as video_url,
        a.media_reel_file as video_file,
        a.thumbnail,
        a.views_count as views,
        a.likes_count as likes,
        a.published_at as created_at,
        a.id as article_id,
        a.slug,
        c.name as category_name,
        c.slug as category_slug,
        'article' as reel_type
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.content_type = 'reel' 
        AND a.status = 'published'
        AND (a.media_reel_url IS NOT NULL OR a.media_reel_file IS NOT NULL)
    ORDER BY a.published_at DESC
    LIMIT 8
");

// Merge both types of reels
$all_reels = array_merge($standalone_reels, $reel_articles);

// Sort by created_at descending
usort($all_reels, function($a, $b) {
    $time_a = (!empty($a['created_at']) && $a['created_at'] !== null) ? strtotime($a['created_at']) : 0;
    $time_b = (!empty($b['created_at']) && $b['created_at'] !== null) ? strtotime($b['created_at']) : 0;
    return $time_b - $time_a;
});

// Limit to 8 most recent
$all_reels = array_slice($all_reels, 0, 8);

// Fetch active stories
$stories = $db->fetchAll("SELECT * FROM stories WHERE status = 'active' AND expires_at > NOW() ORDER BY created_at DESC LIMIT 10");

// Fetch category-wise articles
$categories = getTopCategories();
$category_articles = [];
foreach ($categories as $category) {
    $articles = $db->fetchAll("SELECT * FROM articles WHERE category_id = ? AND status = 'published' ORDER BY published_at DESC LIMIT 5", [$category['id']]);
    if (!empty($articles)) {
        $category_articles[$category['id']] = [
            'category' => $category,
            'articles' => $articles
        ];
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container">
    
    <!-- Live News Section -->
    <?php if (!empty($live_news)): ?>
    <section class="live-news-section mb-5">
        <div class="section-header d-flex align-items-center mb-4">
            <span class="badge bg-danger me-2 pulse">
                <i class="bi bi-broadcast"></i> LIVE
            </span>
            <h2 class="mb-0">Live News</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($live_news as $news): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card live-news-card h-100 shadow-sm border-0 overflow-hidden">
                    <div class="position-relative overflow-hidden" style="height: 250px;">
                        <img src="<?= UPLOADS_URL ?>/articles/<?= $news['thumbnail'] ?>" class="card-img-top h-100 object-fit-cover" alt="<?= htmlspecialchars($news['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        <span class="badge bg-danger position-absolute top-0 end-0 m-3 fs-6">
                            <i class="bi bi-circle-fill me-1"></i> LIVE
                        </span>
                        <div class="position-absolute bottom-0 start-0 end-0 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,0.7));"></div>
                    </div>
                    <div class="card-body pt-4">
                        <h5 class="card-title mb-2">
                            <a href="<?= BASE_URL ?>/article/<?= $news['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars(truncateText($news['title'], 65)) ?>
                            </a>
                        </h5>
                        <p class="card-text text-muted small mb-3">
                            <i class="bi bi-clock"></i> <?= timeAgo($news['updated_at'], $news['created_at']) ?>
                        </p>
                        <a href="<?= BASE_URL ?>/article/<?= $news['slug'] ?>" class="btn btn-sm btn-danger stretched-link">
                            <i class="bi bi-arrow-right"></i> Read More
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Banner Ad (Top) -->
    <?php echo AdsManager::showBannerAd(); ?>
    <?php echo AdsManager::showCustomAd('header', 'top'); ?>

    <!-- Stories Section -->
    <?php if (!empty($stories)): ?>
    <section class="stories-section mb-5">
        <div class="section-header mb-3">
            <h2><i class="bi bi-images"></i> Stories</h2>
        </div>
        <div class="stories-container d-flex gap-3 overflow-auto pb-3">
            <?php foreach ($stories as $story): ?>
            <div class="story-item">
                <a href="<?= BASE_URL ?>/story-view.php?id=<?= $story['id'] ?>" class="text-decoration-none">
                    <div class="story-circle">
                        <img src="<?= UPLOADS_URL ?>/stories/<?= $story['media_url'] ?>" alt="<?= htmlspecialchars($story['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    </div>
                    <p class="text-center small mt-2 mb-0"><?= truncateText($story['title'], 20) ?></p>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured News Section -->
    <?php if (!empty($featured_news)): ?>
    <section class="featured-section mb-5">
        <div class="section-header mb-4">
            <h2><i class="bi bi-star-fill text-warning"></i> Featured News</h2>
        </div>
        <div class="row g-4">
            <!-- Main Featured -->
            <?php $main_featured = $featured_news[0]; ?>
            <div class="col-lg-8">
                <div class="card featured-main h-100 shadow-lg border-0 overflow-hidden">
                    <div class="position-relative" style="height: 400px;">
                        <img src="<?= UPLOADS_URL ?>/articles/<?= $main_featured['thumbnail'] ?>" class="w-100 h-100 object-fit-cover" alt="<?= htmlspecialchars($main_featured['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        <div class="position-absolute top-0 start-0 end-0 bottom-0 d-flex flex-column justify-content-end" style="background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.4) 50%, transparent 100%); padding: 40px 30px;">
                            <?php if ($main_featured['category_name']): ?>
                            <span class="badge bg-primary mb-3 w-fit" style="width: fit-content;">
                                <i class="bi bi-tag"></i> <?= htmlspecialchars($main_featured['category_name']) ?>
                            </span>
                            <?php endif; ?>
                            <h2 class="text-white mb-3 fw-bold" style="line-height: 1.4;">
                                <a href="<?= BASE_URL ?>/article/<?= $main_featured['slug'] ?>" class="text-white text-decoration-none">
                                    <?= htmlspecialchars($main_featured['title']) ?>
                                </a>
                            </h2>
                            <p class="text-white mb-3 opacity-90" style="font-size: 0.95rem; line-height: 1.6;">
                                <?= getExcerpt($main_featured['description'], 120) ?>
                            </p>
                            <div class="d-flex gap-3 align-items-center">
                                <small class="text-white-50">
                                    <i class="bi bi-clock"></i> <?= timeAgo($main_featured['published_at'], $main_featured['created_at']) ?>
                                </small>
                                <small class="text-white-50">
                                    <i class="bi bi-eye"></i> <?= formatNumber($main_featured['views_count']) ?> views
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Side Featured -->
            <div class="col-lg-4">
                <div class="d-flex flex-column gap-3">
                    <?php for ($i = 1; $i < count($featured_news) && $i <= 3; $i++): 
                        $featured = $featured_news[$i];
                    ?>
                    <div class="card featured-side h-100 shadow-sm border-0 overflow-hidden">
                        <div class="row g-0 h-100">
                            <div class="col-5">
                                <img src="<?= UPLOADS_URL ?>/articles/<?= $featured['thumbnail'] ?>" class="img-fluid h-100 object-fit-cover" alt="<?= htmlspecialchars($featured['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                            </div>
                            <div class="col-7">
                                <div class="card-body d-flex flex-column justify-content-between h-100 p-3">
                                    <?php if ($featured['category_name']): ?>
                                    <span class="badge bg-warning text-dark mb-2 w-fit" style="width: fit-content; font-size: 0.75rem;">
                                        <?= htmlspecialchars($featured['category_name']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="card-title mb-2">
                                            <a href="<?= BASE_URL ?>/article/<?= $featured['slug'] ?>" class="text-decoration-none text-dark" style="font-size: 0.95rem; font-weight: 600; line-height: 1.3;">
                                                <?= htmlspecialchars(truncateText($featured['title'], 55)) ?>
                                            </a>
                                        </h6>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= timeAgo($featured['published_at'], $featured['created_at']) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Banner Ad (After Featured) -->
    <?php echo AdsManager::showBannerAd(); ?>
    <?php echo AdsManager::showCustomAd('header', 'middle'); ?>

    <!-- Top News Section -->
    <?php if (!empty($top_news)): ?>
    <section class="top-news-section mb-5">
        <div class="section-header mb-3">
            <h2><i class="bi bi-newspaper"></i> Top News</h2>
        </div>
        <div class="row">
            <?php foreach ($top_news as $news): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card news-card h-100">
                    <img src="<?= UPLOADS_URL ?>/articles/<?= $news['thumbnail'] ?>" class="card-img-top" alt="<?= htmlspecialchars($news['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    <div class="card-body">
                        <?php if ($news['category_name']): ?>
                        <span class="badge bg-secondary mb-2"><?= htmlspecialchars($news['category_name']) ?></span>
                        <?php endif; ?>
                        <h6 class="card-title">
                            <a href="<?= BASE_URL ?>/article/<?= $news['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars(truncateText($news['title'], 70)) ?>
                            </a>
                        </h6>
                        <p class="card-text small text-muted">
                            <i class="bi bi-clock"></i> <?= timeAgo($news['published_at'], $news['created_at']) ?>
                            <i class="bi bi-eye ms-2"></i> <?= formatNumber($news['views_count']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Article Ad (In-Content) -->
    <?php echo AdsManager::showArticleAd(); ?>
    <?php echo AdsManager::showCustomAd('article', 'top'); ?>

    <!-- Gallery Section -->
    <?php if (!empty($gallery_articles)): ?>
    <section class="gallery-section mb-5">
        <div class="section-header mb-3">
            <h2><i class="bi bi-images"></i> Photo Gallery</h2>
        </div>
        <div class="row">
            <?php foreach ($gallery_articles as $article): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card gallery-card h-100">
                    <div class="position-relative">
                        <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        <span class="badge bg-dark position-absolute bottom-0 end-0 m-2">
                            <i class="bi bi-images"></i> Gallery
                        </span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h6>
                        <p class="card-text small text-muted">
                            <i class="bi bi-clock"></i> <?= timeAgo($article['published_at'], $article['created_at']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Video Section -->
    <?php if (!empty($video_articles)): ?>
    <section class="video-section mb-5">
        <div class="section-header mb-3">
            <h2><i class="bi bi-play-circle"></i> Videos</h2>
        </div>
        <div class="row">
            <?php foreach ($video_articles as $article): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card video-card h-100">
                    <div class="position-relative">
                        <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        <div class="video-play-overlay">
                            <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="play-button">
                                <i class="bi bi-play-circle-fill"></i>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h6>
                        <p class="card-text small text-muted">
                            <i class="bi bi-clock"></i> <?= timeAgo($article['published_at'], $article['created_at']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Banner Ad (Before Reels) -->
    <?php echo AdsManager::showSidebarAd(); ?>

    <!-- Reels Section -->
    <?php if (!empty($all_reels)): ?>
    <section class="reels-section mb-5">
        <div class="section-header mb-3 d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-camera-reels"></i> Reels</h2>
            <a href="<?= BASE_URL ?>/reels" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="row">
            <?php foreach ($all_reels as $reel): ?>
            <div class="col-lg-3 col-md-4 col-6 mb-3">
                <div class="card reel-card">
                    <div class="position-relative">
                        <?php 
                        // Determine thumbnail URL
                        $thumbnail_url = ASSETS_URL . '/images/placeholder.jpg';
                        if (!empty($reel['thumbnail'])) {
                            if ($reel['reel_type'] == 'article') {
                                // Article thumbnails: just filename, stored in uploads/articles/
                                $thumbnail_url = UPLOADS_URL . '/articles/' . $reel['thumbnail'];
                            } elseif (strpos($reel['thumbnail'], 'uploads/') === 0) {
                                // Standalone reel with full path stored in DB
                                $thumbnail_url = (BASE_URL ? BASE_URL . '/' : '/') . $reel['thumbnail'];
                            } else {
                                // Standalone reel with just filename
                                $thumbnail_url = UPLOADS_URL . '/reels/' . $reel['thumbnail'];
                            }
                        }
                        
                        // Determine link URL - use clean URL format with slug
                        // Ensure we use numeric IDs only
                        $reel_id_param = ($reel['reel_type'] == 'article') ? (int)$reel['id'] : (int)$reel['id'];
                        
                        if ($reel['reel_type'] == 'article' && !empty($reel['slug'])) {
                            // Article-based reel with slug
                            $reel_url = BASE_URL . '/reel/' . $reel['slug'] . '/' . $reel_id_param;
                        } elseif ($reel['reel_type'] == 'standalone' && !empty($reel['slug'])) {
                            // Standalone reel with generated slug
                            $reel_url = BASE_URL . '/reel/' . $reel['slug'] . '/' . $reel_id_param;
                        } elseif ($reel['reel_type'] == 'article') {
                            // Article-based reel without slug (fallback)
                            $reel_url = BASE_URL . '/reel-player.php?article_id=' . $reel_id_param;
                        } else {
                            // Standalone reel fallback
                            $reel_url = BASE_URL . '/reel-player.php?id=' . $reel_id_param;
                        }
                        ?>
                        <img src="<?= $thumbnail_url ?>" class="card-img" alt="<?= htmlspecialchars($reel['title']) ?>" style="aspect-ratio: 9/16; object-fit: cover;" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                        <div class="reel-overlay">
                            <a href="<?= $reel_url ?>" class="text-white text-decoration-none">
                                <i class="bi bi-play-circle-fill fs-1"></i>
                                <p class="mt-2 px-2"><?= htmlspecialchars(truncateText($reel['title'], 50)) ?></p>
                                <?php if (!empty($reel['category_name'])): ?>
                                <span class="badge bg-primary position-absolute top-0 start-0 m-2"><?= htmlspecialchars($reel['category_name']) ?></span>
                                <?php endif; ?>
                                <?php if ($reel['reel_type'] == 'standalone'): ?>
                                <span class="badge bg-warning position-absolute top-0 end-0 m-2">Reel</span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Banner Ad (Before Categories) -->
    <?php echo AdsManager::showBannerAd(); ?>
    <?php echo AdsManager::showCustomAd('footer', 'top'); ?>

    <!-- Category-wise Articles -->
    <?php foreach ($category_articles as $cat_data): 
        $category = $cat_data['category'];
        $articles = $cat_data['articles'];
    ?>
    <section class="category-section mb-5">
        <div class="section-header mb-3 d-flex justify-content-between align-items-center">
            <h2>
                <?php if ($category['icon']): ?>
                <img src="<?= UPLOADS_URL ?>/categories/<?= $category['icon'] ?>" alt="" height="24" class="me-2">
                <?php endif; ?>
                <?= htmlspecialchars($category['name']) ?>
            </h2>
            <a href="<?= BASE_URL ?>/category/<?= $category['slug'] ?>" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="row">
            <?php foreach ($articles as $article): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card news-card h-100">
                    <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>" onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    <div class="card-body">
                        <h6 class="card-title">
                            <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars(truncateText($article['title'], 70)) ?>
                            </a>
                        </h6>
                        <p class="card-text small text-muted">
                            <i class="bi bi-clock"></i> <?= timeAgo($article['published_at'], $article['created_at']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

</div>

<?php include 'includes/footer.php'; ?>
