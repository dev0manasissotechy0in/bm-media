<?php
require_once 'config/config.php';

// Get article slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL);
}

$db = Database::getInstance();

// Initialize ads manager
require_once 'includes/AdsManager.php';
AdsManager::init($db);

// Get article details
$article = $db->fetchOne("
    SELECT a.*, 
           c.name as category_name, 
           c.slug as category_slug,
           CASE 
               WHEN a.author_type = 'admin' THEN ad.full_name
               WHEN a.author_type = 'reporter' THEN r.full_name
           END as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admin_users ad ON a.author_id = ad.id AND a.author_type = 'admin'
    LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
    WHERE a.slug = ? AND a.status = 'published'
", [$slug]);

if (!$article) {
    redirect(BASE_URL . '/404.php');
}

// Increment view count
incrementArticleViews($article['id']);

// Get article sections
$sections = $db->fetchAll("
    SELECT * FROM article_sections 
    WHERE article_id = ? 
    ORDER BY order_id ASC
", [$article['id']]);

// Get article tags
$tags = $db->fetchAll("
    SELECT t.* FROM tags t
    INNER JOIN article_tags at ON t.id = at.tag_id
    WHERE at.article_id = ?
", [$article['id']]);

// Get gallery images (if gallery article)
$gallery_images = [];
$gallery_metadata = [];
if ($article['content_type'] === 'gallery') {
    // Check if images are in the media_gallery_images column (JSON format)
    if (!empty($article['media_gallery_images'])) {
        $image_files = json_decode($article['media_gallery_images'], true);
        if (is_array($image_files)) {
            foreach ($image_files as $index => $filename) {
                $gallery_images[] = [
                    'id' => $index,
                    'article_id' => $article['id'],
                    'image_url' => $filename,
                    'image_alt' => $article['title'] . ' - Image ' . ($index + 1),
                    'caption' => '',
                    'order_id' => $index
                ];
            }
        }
    }
    
    // For detailed gallery, get metadata from database
    if (($article['gallery_type'] ?? 'simple') === 'detailed') {
        $gallery_metadata = $db->fetchAll("
            SELECT * FROM article_gallery_metadata 
            WHERE article_id = ? 
            ORDER BY order_id ASC
        ", [$article['id']]);
        
        // Map metadata to images
        $metadata_map = [];
        foreach ($gallery_metadata as $meta) {
            $metadata_map[$meta['image_filename']] = $meta;
        }
        
        // Enhance gallery_images with metadata
        foreach ($gallery_images as &$img) {
            if (isset($metadata_map[$img['image_url']])) {
                $img['title'] = $metadata_map[$img['image_url']]['title'];
                $img['description'] = $metadata_map[$img['image_url']]['description'];
            }
        }
        unset($img);
    }
    
    // Also check the article_gallery table (legacy support)
    if (empty($gallery_images)) {
        $gallery_images = $db->fetchAll("
            SELECT * FROM article_gallery 
            WHERE article_id = ? 
            ORDER BY order_id ASC
        ", [$article['id']]);
    }
}

// Get live updates (if live article)
$live_updates = [];
if ($article['is_live']) {
    $live_updates = $db->fetchAll("
        SELECT * FROM article_live_updates 
        WHERE article_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ", [$article['id']]);
}

// Get comments
$comments = $db->fetchAll("
    SELECT c.*, u.full_name as user_name, u.profile_photo
    FROM comments c
    INNER JOIN users u ON c.user_id = u.id
    WHERE c.article_id = ? AND c.status = 'approved' AND c.parent_id IS NULL
    ORDER BY c.created_at DESC
", [$article['id']]);

// Get related articles
$related_articles = getRelatedArticles($article['id'], $article['category_id'], 4);

// Check if user has liked/saved article
$user_liked = false;
$user_saved = false;
if (Security::isLoggedIn('user')) {
    $user_liked = hasUserLikedArticle($_SESSION['user_id'], $article['id']);
    $user_saved = hasUserSavedArticle($_SESSION['user_id'], $article['id']);
}

// Page meta
$page_title = $article['seo_title'] ?: $article['title'];
$page_description = $article['seo_description'] ?: getExcerpt($article['description'], 160);
$page_keywords = $article['seo_keywords'];
$page_image = UPLOADS_URL . '/articles/' . $article['thumbnail'];

include 'includes/header.php';
?>

<!-- Lightbox CSS for Gallery -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css">

<script>
<?php if ($article['is_live']): ?>
const PAGE_TYPE = 'live_article';
const ENABLE_LIVE_UPDATES = true;
<?php endif; ?>
</script>

<div class="container py-4">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Article Header -->
            <article class="article-detail">
                <div class="article-header">
                    <?php if ($article['is_live']): ?>
                    <span class="badge bg-danger mb-2 pulse">
                        <i class="bi bi-broadcast"></i> LIVE
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($article['is_breaking']): ?>
                    <span class="badge bg-warning text-dark mb-2">
                        <i class="bi bi-exclamation-triangle-fill"></i> BREAKING
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($article['category_name']): ?>
                    <div class="mb-2">
                        <a href="<?= BASE_URL ?>/category/<?= $article['category_slug'] ?>" class="badge bg-primary text-decoration-none">
                            <?= htmlspecialchars($article['category_name']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <h1 class="display-5 fw-bold"><?= htmlspecialchars($article['title']) ?></h1>
                    
                    <div class="article-meta">
                        <span><i class="bi bi-person-circle"></i> <?= htmlspecialchars($article['author_name'] ?? 'N/A') ?></span>
                        <span><i class="bi bi-calendar3"></i> <?= formatDate($article['published_at'] ?? '', 'd M, Y H:i', $article['created_at'] ?? '') ?></span>
                        <span><i class="bi bi-eye"></i> <?= formatNumber($article['views_count']) ?> views</span>
                        <span><i class="bi bi-heart"></i> <?= formatNumber($article['likes_count']) ?> likes</span>
                        <span><i class="bi bi-chat"></i> <?= formatNumber($article['comments_count']) ?> comments</span>
                    </div>
                </div>

                <!-- Featured Image / Video Player -->
                <?php if (!empty($article['thumbnail']) && $article['content_type'] !== 'reel'): ?>
                <div class="article-featured-image mb-4" id="mediaContainer">
                    <?php if ($article['content_type'] === 'video' && (!empty($article['media_video_url']) || !empty($article['media_video_file']))): ?>
                        <!-- Video Article with Play Button -->
                        <div class="position-relative">
                            <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                                 alt="<?= htmlspecialchars($article['thumbnail_alt'] ?: $article['title']) ?>" 
                                 class="img-fluid rounded"
                                 id="videoThumbnail"
                                 onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                            
                            <!-- Play Button Overlay -->
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <button onclick="playVideoArticle()" class="btn btn-danger btn-lg rounded-circle shadow-lg" 
                                        id="playButton" style="width: 80px; height: 80px;">
                                    <i class="bi bi-play-fill fs-1"></i>
                                </button>
                            </div>
                            
                            <!-- Video Player (Hidden Initially) -->
                            <div id="videoPlayer" style="display: none;">
                                <?php 
                                $video_url = $article['media_video_url'] ?: $article['media_video_file'];
                                $is_youtube = preg_match('/youtube\.com|youtu\.be/', $video_url);
                                
                                if ($is_youtube) {
                                    // Extract YouTube video ID
                                    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches);
                                    $video_id = $matches[1] ?? '';
                                    ?>
                                    <div class="ratio ratio-16x9">
                                        <iframe id="youtubePlayer"
                                                src="https://www.youtube.com/embed/<?= $video_id ?>?autoplay=1&rel=0" 
                                                frameborder="0" 
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                allowfullscreen>
                                        </iframe>
                                    </div>
                                <?php } else { ?>
                                    <div class="ratio ratio-16x9">
                                        <video controls autoplay playsinline id="htmlVideoPlayer">
                                            <source src="<?= strpos($video_url, 'http') === 0 ? $video_url : UPLOADS_URL . '/articles/' . basename($video_url) ?>" type="video/mp4">
                                            Your browser does not support the video tag.
                                        </video>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Regular Image Article -->
                        <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                             alt="<?= htmlspecialchars($article['thumbnail_alt'] ?: $article['title']) ?>" 
                             class="img-fluid rounded"
                             onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Reel Video Player -->
                <?php if ($article['content_type'] === 'reel' && (!empty($article['media_reel_url']) || !empty($article['media_reel_file']))): ?>
                <div class="reel-player-section mb-4">
                    <div class="card bg-dark text-white border-0 overflow-hidden">
                        <div class="position-relative">
                            <?php 
                            $reel_video = $article['media_reel_url'] ?: $article['media_reel_file'];
                            $is_youtube = preg_match('/youtube\.com|youtu\.be/', $reel_video);
                            
                            if ($is_youtube) {
                                // Extract YouTube video ID
                                preg_match('/(?:youtube\.com\/shorts\/|youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $reel_video, $matches);
                                $video_id = $matches[1] ?? '';
                                ?>
                                <div class="ratio ratio-9x16" style="max-width: 500px; margin: 0 auto; background: #000;">
                                    <iframe src="https://www.youtube.com/embed/<?= $video_id ?>?autoplay=1&mute=1&rel=0" 
                                            frameborder="0" 
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                            allowfullscreen>
                                    </iframe>
                                </div>
                            <?php } else { ?>
                                <div class="ratio ratio-9x16" style="max-width: 500px; margin: 0 auto; background: #000;">
                                    <video controls playsinline autoplay muted loop>
                                        <source src="<?= strpos($reel_video, 'http') === 0 ? $reel_video : UPLOADS_URL . '/articles/' . basename($reel_video) ?>" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            <?php } ?>
                            
                            <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-gradient-dark">
                                <a href="<?= BASE_URL ?>/reel/<?= $article['id'] ?>" 
                                   class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-film"></i> Watch in Reel Player
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Article Description -->
                <div class="article-description lead mb-4">
                    <?= nl2br(htmlspecialchars($article['description'])) ?>
                </div>

                <!-- Article Actions -->
                <div class="article-actions-wrapper mb-4">
                    <input type="hidden" id="article-id" value="<?= $article['id'] ?>">
                    
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <!-- Primary Actions -->
                            <div class="d-flex flex-wrap gap-2 mb-3 pb-3 border-bottom">
                                <button onclick="likeArticle(<?= $article['id'] ?>)" 
                                        class="btn <?= $user_liked ? 'btn-primary' : 'btn-outline-primary' ?> btn-lg flex-grow-1" 
                                        id="likeBtn">
                                    <i class="bi bi-heart<?= $user_liked ? '-fill' : '' ?>"></i> 
                                    <span id="likesCount"><?= formatNumber($article['likes_count']) ?></span> Likes
                                </button>
                                
                                <button onclick="saveArticle(<?= $article['id'] ?>)" 
                                        class="btn <?= $user_saved ? 'btn-warning' : 'btn-outline-warning' ?> btn-lg flex-grow-1" 
                                        id="saveBtn">
                                    <i class="bi bi-bookmark<?= $user_saved ? '-fill' : '' ?>"></i> 
                                    <?= $user_saved ? 'Saved' : 'Save' ?>
                                </button>
                                
                                <button onclick="toggleTTS()" 
                                        class="btn btn-outline-secondary btn-lg flex-grow-1" 
                                        id="ttsBtn">
                                    <i class="bi bi-volume-up-fill" id="ttsIcon"></i> 
                                    <span id="ttsText">Listen</span>
                                </button>
                            </div>
                            
                            <!-- Share Section -->
                            <div class="share-section">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0 text-muted">
                                        <i class="bi bi-share-fill me-2"></i>Share this article
                                    </h6>
                                    <?php if (ENABLE_ARTICLE_DOWNLOAD): ?>
                                    <button onclick="downloadArticle(<?= $article['id'] ?>)" 
                                            class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-download"></i> Download
                                    </button>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex flex-wrap gap-2">
                                    <button onclick="shareArticle('facebook', '<?= currentURL() ?>', '<?= addslashes($article['title']) ?>')" 
                                            class="btn btn-facebook flex-grow-1">
                                        <i class="bi bi-facebook"></i> Facebook
                                    </button>
                                    
                                    <button onclick="shareArticle('twitter', '<?= currentURL() ?>', '<?= addslashes($article['title']) ?>')" 
                                            class="btn btn-twitter flex-grow-1">
                                        <i class="bi bi-twitter"></i> Twitter
                                    </button>
                                    
                                    <button onclick="shareArticle('whatsapp', '<?= currentURL() ?>', '<?= addslashes($article['title']) ?>')" 
                                            class="btn btn-whatsapp flex-grow-1">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </button>
                                    
                                    <button onclick="copyArticleLink()" 
                                            class="btn btn-link-copy flex-grow-1" 
                                            id="copyLinkBtn">
                                        <i class="bi bi-link-45deg"></i> Copy Link
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                .article-actions-wrapper .card {
                    border-radius: 15px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
                }
                
                .article-actions-wrapper .btn {
                    border-radius: 10px;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    min-width: 120px;
                }
                
                .article-actions-wrapper .btn-lg {
                    padding: 12px 20px;
                }
                
                .article-actions-wrapper .btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                }
                
                .btn-facebook {
                    background: #1877f2;
                    color: white;
                    border: none;
                }
                
                .btn-facebook:hover {
                    background: #145dbf;
                    color: white;
                }
                
                .btn-twitter {
                    background: #1da1f2;
                    color: white;
                    border: none;
                }
                
                .btn-twitter:hover {
                    background: #1a8cd8;
                    color: white;
                }
                
                .btn-whatsapp {
                    background: #25d366;
                    color: white;
                    border: none;
                }
                
                .btn-whatsapp:hover {
                    background: #20ba5a;
                    color: white;
                }
                
                .btn-link-copy {
                    background: #6c757d;
                    color: white;
                    border: none;
                }
                
                .btn-link-copy:hover {
                    background: #5a6268;
                    color: white;
                }
                
                .btn-link-copy.copied {
                    background: #28a745;
                }
                
                .share-section h6 {
                    font-weight: 600;
                    font-size: 0.95rem;
                }
                
                @media (max-width: 768px) {
                    .article-actions-wrapper .btn {
                        min-width: auto;
                        font-size: 0.875rem;
                    }
                    
                    .article-actions-wrapper .btn-lg {
                        padding: 10px 15px;
                    }
                }
                </style>

                <script>
                function copyArticleLink() {
                    const url = '<?= currentURL() ?>';
                    const btn = document.getElementById('copyLinkBtn');
                    
                    navigator.clipboard.writeText(url).then(() => {
                        // Change button appearance
                        btn.classList.add('copied');
                        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                        
                        // Reset after 2 seconds
                        setTimeout(() => {
                            btn.classList.remove('copied');
                            btn.innerHTML = '<i class="bi bi-link-45deg"></i> Copy Link';
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy:', err);
                        alert('Failed to copy link');
                    });
                }
                </script>

                <!-- Live Updates Timeline -->
                <?php if ($article['is_live'] && !empty($live_updates)): ?>
                <div class="live-updates-section mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h3 class="mb-0">
                            <i class="bi bi-broadcast text-danger"></i> 
                            <span class="text-danger">LIVE</span> Updates Timeline
                        </h3>
                        <span class="badge bg-danger">
                            <i class="bi bi-circle-fill" style="font-size: 0.5rem; animation: blink 1.5s infinite;"></i>
                            <?= count($live_updates) ?> Updates
                        </span>
                    </div>
                    
                    <div id="liveUpdatesContainer" class="timeline">
                        <?php foreach ($live_updates as $index => $update): ?>
                        <div class="timeline-item" data-update-id="<?= $update['id'] ?>">
                            <?php if ($index === 0): ?>
                            <span class="timeline-badge">LATEST</span>
                            <?php endif; ?>
                            
                            <div class="timeline-marker"></div>
                            
                            <div class="timeline-content">
                                <span class="timeline-time">
                                    <i class="bi bi-clock"></i>
                                    <?= formatDate($update['created_at'], 'g:i A') ?>
                                    <small class="opacity-75 ms-1"><?= formatDate($update['created_at'], 'd M') ?></small>
                                </span>
                                
                                <div class="timeline-text">
                                    <?= nl2br(htmlspecialchars($update['update_text'])) ?>
                                </div>
                                
                                <?php 
                                $timeAgo = timeAgo($update['created_at']);
                                if ($timeAgo):
                                ?>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-calendar-event"></i> <?= $timeAgo ?>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-info-circle"></i>
                        <small>This is a live article. Updates will appear automatically as events unfold.</small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Article Content Sections -->
                <div class="article-content">
                    <?php if (!empty($article['content'])): ?>
                        <!-- Simple content from content column -->
                        <div class="article-body">
                            <?= Security::cleanHTML($article['content']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($sections as $section): ?>
                        <?php if ($section['section_type'] === 'subtitle'): ?>
                            <h3><?= htmlspecialchars($section['subtitle']) ?></h3>
                        
                        <?php elseif ($section['section_type'] === 'text_image'): ?>
                            <?php if ($section['subtitle']): ?>
                            <h4><?= htmlspecialchars($section['subtitle']) ?></h4>
                            <?php endif; ?>
                            <?php if ($section['content']): ?>
                            <p><?= nl2br(Security::cleanHTML($section['content'])) ?></p>
                            <?php endif; ?>
                            <?php if ($section['media_url']): ?>
                            <figure class="figure">
                                <img src="<?= UPLOADS_URL ?>/articles/<?= $section['media_url'] ?>" 
                                     alt="<?= htmlspecialchars($section['media_alt']) ?>" 
                                     class="figure-img img-fluid rounded">
                                <?php if ($section['media_alt']): ?>
                                <figcaption class="figure-caption"><?= htmlspecialchars($section['media_alt']) ?></figcaption>
                                <?php endif; ?>
                            </figure>
                            <?php endif; ?>
                        
                        <?php elseif ($section['section_type'] === 'text_video'): ?>
                            <?php if ($section['subtitle']): ?>
                            <h4><?= htmlspecialchars($section['subtitle']) ?></h4>
                            <?php endif; ?>
                            <?php if ($section['content']): ?>
                            <p><?= nl2br(Security::cleanHTML($section['content'])) ?></p>
                            <?php endif; ?>
                            <?php if ($section['media_url']): ?>
                            <div class="ratio ratio-16x9 mb-3">
                                <video controls>
                                    <source src="<?= UPLOADS_URL ?>/articles/<?= $section['media_url'] ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            </div>
                            <?php endif; ?>
                        
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- In-Article Ad -->
                <?php echo AdsManager::showArticleAd(); ?>
                <?php echo AdsManager::showCustomAd('article', 'middle'); ?>

                <!-- Gallery Section -->
                <?php if ($article['content_type'] === 'gallery' && !empty($gallery_images)): ?>
                    <?php if (($article['gallery_type'] ?? 'simple') === 'simple'): ?>
                        <!-- Simple Gallery: Thumbnail Slider -->
                        <div class="simple-gallery-slider mb-4">
                            <div id="galleryCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <?php foreach ($gallery_images as $index => $image): ?>
                                    <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="<?= $index ?>" 
                                            class="<?= $index === 0 ? 'active' : '' ?>" 
                                            aria-current="<?= $index === 0 ? 'true' : 'false' ?>" 
                                            aria-label="Slide <?= $index + 1 ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner">
                                    <?php foreach ($gallery_images as $index => $image): ?>
                                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="<?= UPLOADS_URL ?>/articles/<?= $image['image_url'] ?>" 
                                             class="d-block w-100" 
                                             alt="<?= htmlspecialchars($image['image_alt'] ?? $article['title']) ?>"
                                             style="max-height: 600px; object-fit: contain; background: #000;">
                                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-75 rounded p-2">
                                            <p class="mb-0">Photo <?= $index + 1 ?> of <?= count($gallery_images) ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Detailed Gallery: Photos with Title & Description -->
                        <div class="article-gallery mb-4">
                            <h3 class="border-bottom pb-2 mb-4">
                                <i class="bi bi-images"></i> Photo Gallery
                            </h3>
                            <?php foreach ($gallery_images as $image): ?>
                            <div class="gallery-item mb-4">
                                <div class="row">
                                    <div class="col-md-6">
                                        <a href="<?= UPLOADS_URL ?>/articles/<?= $image['image_url'] ?>" 
                                           data-lightbox="gallery" 
                                           data-title="<?= htmlspecialchars($image['title'] ?? '') ?>">
                                            <img src="<?= UPLOADS_URL ?>/articles/<?= $image['image_url'] ?>" 
                                                 alt="<?= htmlspecialchars($image['title'] ?? $image['image_alt']) ?>" 
                                                 class="img-fluid rounded shadow">
                                        </a>
                                    </div>
                                    <div class="col-md-6 d-flex flex-column justify-content-center">
                                        <?php if (!empty($image['title'])): ?>
                                        <h4 class="mb-3"><?= htmlspecialchars($image['title']) ?></h4>
                                        <?php endif; ?>
                                        <?php if (!empty($image['description'])): ?>
                                        <p class="text-muted"><?= nl2br(htmlspecialchars($image['description'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($image !== end($gallery_images)): ?>
                                <hr class="my-4">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                <div class="article-tags mb-4">
                    <strong>Tags:</strong>
                    <?php foreach ($tags as $tag): ?>
                    <a href="<?= BASE_URL ?>/tag/<?= $tag['slug'] ?>" class="badge bg-secondary text-decoration-none me-1">
                        <?= htmlspecialchars($tag['name']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <div class="comments-section mt-5">
                    <h3 class="border-bottom pb-2">Comments (<?= $article['comments_count'] ?>)</h3>
                    
                    <?php if (Security::isLoggedIn('user')): ?>
                    <form id="commentForm" method="POST" action="javascript:void(0);" class="mb-4">
                        <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                        <?= Security::getCSRFTokenField() ?>
                        <div class="mb-3">
                            <textarea name="comment_text" class="form-control" rows="3" placeholder="Write your comment..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Post Comment</button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info">
                        Please <a href="<?= BASE_URL ?>/login.php">login</a> to post a comment.
                    </div>
                    <?php endif; ?>
                    
                    <div id="commentsContainer">
                        <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="d-flex">
                                <div class="me-3">
                                    <?php if ($comment['profile_photo']): ?>
                                    <img src="<?= UPLOADS_URL ?>/users/<?= $comment['profile_photo'] ?>" alt="" class="rounded-circle" width="40" height="40">
                                    <?php else: ?>
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($comment['user_name'], 0, 1)) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="comment-author"><?= htmlspecialchars($comment['user_name']) ?></div>
                                    <div class="comment-time text-muted small"><?= timeAgo($comment['created_at']) ?></div>
                                    <div class="comment-text mt-2"><?= nl2br(htmlspecialchars($comment['comment_text'])) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Related Articles -->
            <?php if (!empty($related_articles)): ?>
            <div class="sidebar-widget">
                <h4>Related Articles</h4>
                <?php foreach ($related_articles as $related): ?>
                <div class="widget-article-item">
                    <img src="<?= UPLOADS_URL ?>/articles/<?= $related['thumbnail'] ?>" 
                         alt="<?= htmlspecialchars($related['title']) ?>" 
                         class="widget-article-img"
                         onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    <div>
                        <h6>
                            <a href="<?= BASE_URL ?>/article/<?= $related['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars(truncateText($related['title'], 70)) ?>
                            </a>
                        </h6>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted"><?= timeAgo($related['published_at']) ?></small>
                            <small class="text-muted">•</small>
                            <small class="text-muted">
                                <i class="bi bi-chat-dots"></i> <?= $related['comments_count'] ?? 0 ?>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Sidebar Ad -->
            <?php echo AdsManager::showCustomAd('sidebar', 'middle'); ?>

            <!-- Popular Articles -->
            <div class="sidebar-widget">
                <h4>Trending Now</h4>
                <?php
                $popular = $db->fetchAll("
                    SELECT * FROM articles 
                    WHERE status = 'published' 
                    ORDER BY views_count DESC 
                    LIMIT 5
                ");
                foreach ($popular as $pop):
                ?>
                <div class="widget-article-item">
                    <img src="<?= UPLOADS_URL ?>/articles/<?= $pop['thumbnail'] ?>" 
                         alt="<?= htmlspecialchars($pop['title']) ?>" 
                         class="widget-article-img"
                         onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                    <div>
                        <h6>
                            <a href="<?= BASE_URL ?>/article/<?= $pop['slug'] ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars(truncateText($pop['title'], 70)) ?>
                            </a>
                        </h6>
                        <small class="text-muted"><i class="bi bi-eye"></i> <?= formatNumber($pop['views_count']) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Floating Reel Player Button -->
<!-- <?php if ($article['content_type'] === 'reel' && (!empty($article['media_reel_url']) || !empty($article['media_reel_file']))): ?>
<a href="<?= BASE_URL ?>/reel/<?= $article['id'] ?>" 
   class="floating-reel-btn" 
   title="Watch Reel in Player">
    <i class="bi bi-film"></i>
    <span class="btn-text">Watch Reel</span>
</a> -->

<style>
.floating-reel-btn {
    position: fixed;
    right: 30px;
    bottom: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
    z-index: 1000;
    overflow: hidden;
}

.floating-reel-btn:hover {
    width: 160px;
    border-radius: 30px;
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
    color: white;
}

.floating-reel-btn .btn-text {
    display: none;
    margin-left: 8px;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
}

.floating-reel-btn:hover .btn-text {
    display: inline;
}

.floating-reel-btn:hover i {
    font-size: 24px;
}

@media (max-width: 768px) {
    .floating-reel-btn {
        right: 20px;
        bottom: 70px;
        width: 56px;
        height: 56px;
        font-size: 26px;
    }
    
    .floating-reel-btn:hover {
        width: 56px;
        border-radius: 50%;
    }
    
    .floating-reel-btn:hover .btn-text {
        display: none;
    }
    
    .floating-reel-btn:hover i {
        font-size: 26px;
    }
}

/* Pulse animation */
@keyframes pulse-ring {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(1.3);
        opacity: 0;
    }
}

.floating-reel-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    animation: pulse-ring 2s infinite;
    z-index: -1;
}

.floating-reel-btn:hover::before {
    animation: none;
}
</style>
<?php endif; ?>

<!-- Lightbox JS for Gallery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
<script>
// Configure lightbox
if (typeof lightbox !== 'undefined') {
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': 'Image %1 of %2'
    });
}

// Play video article - replaces thumbnail with video player
function playVideoArticle() {
    const thumbnail = document.getElementById('videoThumbnail');
    const playButton = document.getElementById('playButton');
    const videoPlayer = document.getElementById('videoPlayer');
    
    if (thumbnail && playButton && videoPlayer) {
        // Hide thumbnail and play button
        thumbnail.style.display = 'none';
        playButton.style.display = 'none';
        
        // Show and play video
        videoPlayer.style.display = 'block';
        
        // If it's an HTML5 video, play it
        const htmlVideo = document.getElementById('htmlVideoPlayer');
        if (htmlVideo) {
            htmlVideo.play();
        }
    }
}

<?php if ($article['is_live']): ?>
// Auto-refresh live updates
let lastUpdateId = <?= !empty($live_updates) ? $live_updates[0]['id'] : 0 ?>;
const articleId = <?= $article['id'] ?>;

function fetchLiveUpdates() {
    fetch('<?= BASE_URL ?>/api/articles/live-updates.php?article_id=' + articleId + '&last_id=' + lastUpdateId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.updates && data.updates.length > 0) {
                // Prepend new updates to the timeline
                const container = document.getElementById('liveUpdatesContainer');
                data.updates.reverse().forEach(update => {
                    const updateHtml = `
                        <div class="timeline-item" data-update-id="${update.id}">
                            <span class="timeline-badge">LATEST</span>
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <span class="timeline-time">
                                    <i class="bi bi-clock"></i>
                                    ${formatTime(update.created_at)}
                                    <small class="opacity-75 ms-1">${formatDate(update.created_at)}</small>
                                </span>
                                <div class="timeline-text">
                                    ${escapeHtml(update.update_text).replace(/\n/g, '<br>')}
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-calendar-event"></i> Just now
                                </small>
                            </div>
                        </div>
                    `;
                    
                    // Remove LATEST badge from previous first item
                    const badges = container.querySelectorAll('.timeline-badge');
                    badges.forEach(badge => badge.remove());
                    
                    // Insert new update at the beginning
                    container.insertAdjacentHTML('afterbegin', updateHtml);
                    
                    lastUpdateId = Math.max(lastUpdateId, update.id);
                });
                
                // Update badge count
                const badge = document.querySelector('.live-updates-section .badge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent.match(/\d+/)[0]);
                    badge.innerHTML = `<i class="bi bi-circle-fill" style="font-size: 0.5rem; animation: blink 1.5s infinite;"></i> ${currentCount + data.updates.length} Updates`;
                }
                
                // Show notification
                showNotification('New live update available!');
            }
        })
        .catch(error => console.error('Error fetching live updates:', error));
}

function formatTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
}

function formatDate(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showNotification(message) {
    // Create a simple toast notification
    const toast = document.createElement('div');
    toast.className = 'alert alert-info position-fixed top-0 end-0 m-3';
    toast.style.zIndex = '9999';
    toast.innerHTML = `<i class="bi bi-info-circle"></i> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Poll for updates every 30 seconds
setInterval(fetchLiveUpdates, 30000);

// Also check immediately after 5 seconds
setTimeout(fetchLiveUpdates, 5000);
<?php endif; ?>

// Text-to-Speech functionality
let ttsUtterance = null;
let isSpeaking = false;

function toggleTTS() {
    if (!('speechSynthesis' in window)) {
        alert('Text-to-Speech is not supported in your browser');
        return;
    }
    
    if (isSpeaking) {
        // Stop speaking
        window.speechSynthesis.cancel();
        isSpeaking = false;
        updateTTSButton(false);
    } else {
        // Start speaking
        speakArticle();
    }
}

function speakArticle() {
    // Get article content
    const title = document.querySelector('h1')?.textContent || '';
    const description = document.querySelector('.article-description')?.textContent || '';
    const content = document.querySelector('.article-content')?.textContent || '';
    
    // Combine all text
    const fullText = `${title}. ${description}. ${content}`;
    
    // Create speech synthesis utterance
    ttsUtterance = new SpeechSynthesisUtterance(fullText);
    
    // Configure voice settings for gentle voice
    ttsUtterance.rate = 0.9; // Slightly slower for clarity
    ttsUtterance.pitch = 1.0; // Normal pitch
    ttsUtterance.volume = 1.0; // Full volume
    
    // Try to use a gentle/female voice if available
    const voices = window.speechSynthesis.getVoices();
    const gentleVoice = voices.find(voice => 
        voice.name.includes('Female') || 
        voice.name.includes('Google UK English Female') ||
        voice.name.includes('Microsoft Zira') ||
        voice.lang.startsWith('en')
    );
    
    if (gentleVoice) {
        ttsUtterance.voice = gentleVoice;
    }
    
    // Event listeners
    ttsUtterance.onstart = function() {
        isSpeaking = true;
        updateTTSButton(true);
    };
    
    ttsUtterance.onend = function() {
        isSpeaking = false;
        updateTTSButton(false);
    };
    
    ttsUtterance.onerror = function(event) {
        console.error('TTS Error:', event);
        isSpeaking = false;
        updateTTSButton(false);
    };
    
    // Start speaking
    window.speechSynthesis.speak(ttsUtterance);
}

function updateTTSButton(speaking) {
    const btn = document.getElementById('ttsBtn');
    const icon = document.getElementById('ttsIcon');
    const text = document.getElementById('ttsText');
    
    if (speaking) {
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-secondary');
        icon.className = 'bi bi-stop-circle-fill';
        text.textContent = 'Stop';
    } else {
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-outline-secondary');
        icon.className = 'bi bi-volume-up-fill';
        text.textContent = 'Listen';
    }
}

// Load voices when they become available
if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = function() {
        // Voices are loaded
    };
}
</script>

<!-- Twitter Embed Widget -->
<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>

<!-- Instagram Embed Widget -->
<script async src="https://www.instagram.com/embed.js"></script>

<!-- Process Social Media Embeds -->
<script>
// Function to process Instagram embeds
function processInstagramEmbeds() {
    if (window.instgrm) {
        window.instgrm.Embeds.process();
    }
}

// Function to process Twitter embeds
function processTwitterEmbeds() {
    if (window.twttr) {
        window.twttr.widgets.load();
    }
}

// Wait for widgets to load then process embeds
setTimeout(() => {
    processInstagramEmbeds();
    processTwitterEmbeds();
}, 1000);

// Also process when scripts are loaded
if (window.instgrm) {
    window.instgrm.Embeds.process();
}

if (window.twttr) {
    window.twttr.ready((twttr) => {
        twttr.widgets.load();
    });
}
</script>

<?php include 'includes/footer.php'; ?>
