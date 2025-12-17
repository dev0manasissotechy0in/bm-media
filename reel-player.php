<?php
/**
 * Instagram-Style Reel Player
 * Full-screen vertical video player with auto-play
 */

require_once 'config/config.php';

$db = Database::getInstance();

// Get reel ID from URL or get all reels
$reel_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$article_id = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;
$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

// If we have a slug and article_id, determine if it's an article or standalone reel
if ($slug && $article_id && !$reel_id) {
    // Check if this is an article-based reel
    $article_check = $db->fetchOne("SELECT id FROM articles WHERE slug = ? AND id = ? AND content_type = 'reel'", [$slug, $article_id]);
    
    if (!$article_check) {
        // Not an article, so it must be a standalone reel
        // The article_id in URL is actually the reel id
        $reel_id = $article_id;
        $article_id = null;
    }
}

// Build query to get reels
$where = ["(r.status = 'active' OR a.status = 'published')"];
$params = [];

// If specific reel ID, start from that reel
if ($reel_id) {
    // Get all reels starting from this one
    $current_reel = $db->fetchOne("SELECT created_at FROM reels WHERE id = ?", [$reel_id]);
    if ($current_reel) {
        $where[] = "r.created_at <= ?";
        $params[] = $current_reel['created_at'];
    }
}

// If coming from article, include that article's reel first
$reels = [];

if ($article_id) {
    // Get article with reel content
    $article_reel = $db->fetchOne("
        SELECT 
            a.id,
            a.title,
            a.description as content,
            a.media_reel_url as video_url,
            a.media_reel_file as video_file,
            a.thumbnail,
            a.views_count as views,
            a.likes_count as likes,
            a.slug,
            c.name as category_name,
            'article' as source_type
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.id = ? AND a.content_type = 'reel' AND a.status = 'published'
    ", [$article_id]);
    
    if ($article_reel) {
        $reels[] = $article_reel;
    }
}

// Get standalone reels and articles with reel content
$all_reels = $db->fetchAll("
    SELECT 
        r.id,
        r.title,
        r.description as content,
        r.video_url,
        r.video_file,
        r.thumbnail,
        r.views_count as views,
        r.likes_count as likes,
        r.article_id,
        c.name as category_name,
        'reel' as source_type,
        CASE WHEN r.article_id IS NOT NULL THEN a.slug ELSE NULL END as article_slug
    FROM reels r
    LEFT JOIN categories c ON r.category_id = c.id
    LEFT JOIN articles a ON r.article_id = a.id
    WHERE r.status = 'active'
    ORDER BY r.created_at DESC
    LIMIT 50
");

// Merge article reel with standalone reels (avoid duplicates)
foreach ($all_reels as $reel) {
    // Skip if this reel is from the same article we already added
    if ($article_id && $reel['article_id'] == $article_id) {
        continue;
    }
    $reels[] = $reel;
}

// Also get articles with reel content type
$article_reels = $db->fetchAll("
    SELECT 
        a.id,
        a.title,
        a.description as content,
        a.media_reel_url as video_url,
        a.media_reel_file as video_file,
        a.thumbnail,
        a.views_count as views,
        a.likes_count as likes,
        a.slug,
        c.name as category_name,
        'article' as source_type
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.content_type = 'reel' 
    AND a.status = 'published'
    AND a.id != ?
    ORDER BY a.created_at DESC
    LIMIT 30
", [$article_id ?? 0]);

$reels = array_merge($reels, $article_reels);

if (empty($reels)) {
    redirect(BASE_URL . '/reels.php');
}

// If specific reel_id, move it to the front
if ($reel_id) {
    foreach ($reels as $key => $reel) {
        if ($reel['source_type'] === 'reel' && $reel['id'] == $reel_id) {
            $found_reel = $reels[$key];
            unset($reels[$key]);
            array_unshift($reels, $found_reel);
            break;
        }
    }
}

$reels = array_values($reels); // Re-index array

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reels - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        
        .reels-container {
            width: 100vw;
            height: 100vh;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .reels-container::-webkit-scrollbar {
            display: none;
        }
        
        .reel {
            width: 100%;
            height: 100vh;
            position: relative;
            scroll-snap-align: start;
            scroll-snap-stop: always;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }
        
        .reel video {
            width: 100%;
            height: 100%;
            object-fit: contain;
            max-width: 500px;
        }
        
        .reel iframe {
            width: 100%;
            height: 100%;
            border: none;
            max-width: 500px;
        }
        
        /* Top Bar */
        .reel-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: 15px 20px;
            background: linear-gradient(180deg, rgba(0,0,0,0.8) 0%, transparent 100%);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .reel-logo {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }
        
        .close-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .close-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Info Overlay */
        .reel-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(0deg, rgba(0,0,0,0.8) 0%, transparent 100%);
            color: #fff;
            z-index: 50;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .reel-category {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 10px;
        }
        
        .reel-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .reel-description {
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: 15px;
            opacity: 0.9;
            max-height: 60px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        
        .reel-stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .reel-stat {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .read-article-btn {
            width: 100%;
            padding: 12px;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .read-article-btn:hover {
            background: #f0f0f0;
            color: #000;
        }
        
        /* Action Buttons (Right Side) */
        .reel-actions {
            position: absolute;
            right: 15px;
            bottom: 80px;
            display: flex;
            flex-direction: column;
            gap: 25px;
            z-index: 60;
        }
        
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .action-btn i {
            font-size: 28px;
            margin-bottom: 5px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }
        
        .action-btn span {
            font-size: 12px;
            font-weight: 600;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
        }
        
        .action-btn.active i {
            color: #ff3b5c;
        }
        
        /* Navigation Arrows */
        .nav-arrow {
            position: fixed;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            z-index: 200;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-arrow:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .nav-arrow.up {
            top: 50%;
            transform: translateY(-60px);
        }
        
        .nav-arrow.down {
            top: 50%;
            transform: translateY(10px);
        }
        
        /* Loading */
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 20px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .reel video,
            .reel iframe {
                max-width: 100%;
            }
            
            .reel-info {
                max-width: 100%;
                padding: 15px;
            }
            
            .reel-actions {
                bottom: 100px;
            }
        }
        
        /* Video controls overlay */
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 40;
            cursor: pointer;
        }
        
        .play-pause-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            border: none;
            color: #fff;
            font-size: 40px;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .video-overlay:hover .play-pause-btn,
        .video-overlay.paused .play-pause-btn {
            opacity: 1;
        }
        
        /* Mute/Unmute button */
        .mute-btn {
            position: absolute;
            top: 70px;
            right: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            border: 2px solid rgba(255,255,255,0.3);
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            z-index: 150;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mute-btn:hover {
            background: rgba(0,0,0,0.7);
            border-color: rgba(255,255,255,0.5);
        }
    </style>
</head>
<body>
    <!-- Top Header -->
    <div class="reel-header">
        <a href="<?= BASE_URL ?>" class="reel-logo">
            <i class="bi bi-camera-reels"></i> Reels
        </a>
        <button class="close-btn" onclick="window.history.back();">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <!-- Navigation Arrows -->
    <button class="nav-arrow up" id="scrollUp">
        <i class="bi bi-chevron-up"></i>
    </button>
    <button class="nav-arrow down" id="scrollDown">
        <i class="bi bi-chevron-down"></i>
    </button>
    
    <!-- Reels Container -->
    <div class="reels-container" id="reelsContainer">
        <?php foreach ($reels as $index => $reel): ?>
        <?php
            // Determine video source
            $video_url = null;
            $video_file = null;
            $is_youtube = false;
            $embed_url = null;
            
            if (!empty($reel['video_url'])) {
                $video_url = $reel['video_url'];
                // Check if YouTube/YouTube Shorts
                if (preg_match('/(?:youtube\.com\/shorts\/|youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                    $is_youtube = true;
                    $video_id = $matches[1];
                    $embed_url = "https://www.youtube.com/embed/{$video_id}?autoplay=1&mute=0&controls=1&loop=1&playlist={$video_id}&rel=0";
                } elseif (strpos($video_url, 'uploads/') === 0) {
                    // This is a local uploaded file path, treat it as video_file
                    $video_file = (BASE_URL ? BASE_URL . '/' : '/') . $video_url;
                }
            } elseif (!empty($reel['video_file'])) {
                // Detect correct upload path based on source type
                if ($reel['source_type'] === 'article') {
                    // For article-based reels
                    if (strpos($reel['video_file'], 'http') === 0) {
                        // Already a full URL
                        $video_file = $reel['video_file'];
                    } elseif (strpos($reel['video_file'], '/') === 0) {
                        // Absolute path from root
                        $video_file = (BASE_URL ?: '') . $reel['video_file'];
                    } else {
                        // Relative filename - article reels are stored in uploads/articles/
                        $video_file = UPLOADS_URL . '/articles/' . $reel['video_file'];
                    }
                } else {
                    // For standalone reels
                    if (strpos($reel['video_file'], 'http') === 0) {
                        // Already a full URL
                        $video_file = $reel['video_file'];
                    } elseif (strpos($reel['video_file'], 'uploads/') === 0) {
                        // Path starts with 'uploads/', add base URL if available
                        $video_file = (BASE_URL ? BASE_URL . '/' : '/') . $reel['video_file'];
                    } elseif (strpos($reel['video_file'], '/') === 0) {
                        // Absolute path from root
                        $video_file = (BASE_URL ?: '') . $reel['video_file'];
                    } 
                    // else {
                    //     // Just filename, assume it's in reels/videos folder
                    //     $video_file = UPLOADS_URL . '/reels/videos/' . $reel['video_file'];
                    // }
                }
            }
            
            $has_article = ($reel['source_type'] === 'article') || !empty($reel['article_id']);
            $article_link = $has_article ? BASE_URL . '/article/' . ($reel['slug'] ?? $reel['article_slug']) : null;
        ?>
        
        <div class="reel" data-index="<?= $index ?>" data-reel-id="<?= $reel['id'] ?>">
            <!-- Video -->
            <?php if ($is_youtube): ?>
                <iframe 
                    src="<?= $embed_url ?>" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen
                    class="reel-video">
                </iframe>
            <?php elseif ($video_file): ?>
                <video 
                    class="reel-video" 
                    loop 
                    playsinline
                    data-index="<?= $index ?>">
                    <source src="<?= $video_file ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="video-overlay" onclick="togglePlayPause(<?= $index ?>)">
                    <button class="play-pause-btn">
                        <i class="bi bi-pause-fill"></i>
                    </button>
                </div>
                <button class="mute-btn" onclick="toggleMute(<?= $index ?>); event.stopPropagation();">
                    <i class="bi bi-volume-up-fill"></i>
                </button>
            <?php elseif ($video_url): ?>
                <video 
                    class="reel-video" 
                    loop 
                    playsinline
                    data-index="<?= $index ?>">
                    <source src="<?= $video_url ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
                <div class="video-overlay" onclick="togglePlayPause(<?= $index ?>)">
                    <button class="play-pause-btn">
                        <i class="bi bi-pause-fill"></i>
                    </button>
                </div>
            <?php else: ?>
                <div class="loading">
                    <i class="bi bi-exclamation-circle"></i> Video not available
                </div>
            <?php endif; ?>
            
            <!-- Info Overlay -->
            <div class="reel-info">
                <?php if (!empty($reel['category_name'])): ?>
                <div class="reel-category">
                    <i class="bi bi-tag"></i> <?= htmlspecialchars($reel['category_name']) ?>
                </div>
                <?php endif; ?>
                
                <div class="reel-title">
                    <?= htmlspecialchars($reel['title']) ?>
                </div>
                
                <?php if (!empty($reel['content'])): ?>
                <div class="reel-description">
                    <?= htmlspecialchars(strip_tags($reel['content'])) ?>
                </div>
                <?php endif; ?>
                
                <div class="reel-stats">
                    <div class="reel-stat">
                        <i class="bi bi-eye"></i>
                        <span><?= formatNumber($reel['views'] ?? 0) ?></span>
                    </div>
                    <div class="reel-stat">
                        <i class="bi bi-heart"></i>
                        <span><?= formatNumber($reel['likes'] ?? 0) ?></span>
                    </div>
                </div>
                
                <?php if ($has_article && $article_link): ?>
                <a href="<?= $article_link ?>" class="read-article-btn">
                    <i class="bi bi-newspaper"></i> Read Full Article
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="reel-actions">
                <button class="action-btn like-btn" onclick="likeReel(<?= $reel['id'] ?>, '<?= $reel['source_type'] ?>')">
                    <i class="bi bi-heart"></i>
                    <span><?= formatNumber($reel['likes'] ?? 0) ?></span>
                </button>
                
                <button class="action-btn" onclick="shareReel(<?= $reel['id'] ?>, '<?= $reel['source_type'] ?>')">
                    <i class="bi bi-share"></i>
                    <span>Share</span>
                </button>
                
                <?php if ($has_article && $article_link): ?>
                <a href="<?= $article_link ?>" class="action-btn" style="text-decoration: none;">
                    <i class="bi bi-newspaper"></i>
                    <span>Article</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        const reelsContainer = document.getElementById('reelsContainer');
        const videos = document.querySelectorAll('.reel-video');
        let currentIndex = 0;
        let isScrolling = false;
        
        // Intersection Observer for auto-play
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target.querySelector('video');
                const iframe = entry.target.querySelector('iframe');
                
                if (entry.isIntersecting) {
                    // Pause all other videos and iframes first
                    document.querySelectorAll('.reel').forEach(reel => {
                        if (reel !== entry.target) {
                            const otherVideo = reel.querySelector('video');
                            const otherIframe = reel.querySelector('iframe');
                            
                            if (otherVideo) {
                                otherVideo.pause();
                                otherVideo.currentTime = 0;
                            }
                            if (otherIframe) {
                                // Stop YouTube video by reloading iframe
                                const src = otherIframe.src;
                                otherIframe.src = '';
                                otherIframe.src = src.replace('autoplay=1', 'autoplay=0');
                            }
                        }
                    });
                    
                    // Play current video or start iframe
                    if (video) {
                        video.currentTime = 0;
                        video.play().catch(e => console.log('Autoplay prevented:', e));
                        
                        // Update play button icon
                        const overlay = entry.target.querySelector('.video-overlay');
                        if (overlay) {
                            const playBtn = overlay.querySelector('.play-pause-btn i');
                            if (playBtn) {
                                playBtn.className = 'bi bi-pause-fill';
                                overlay.classList.remove('paused');
                            }
                        }
                    } else if (iframe) {
                        // Ensure YouTube autoplay is enabled
                        const src = iframe.src;
                        if (src.includes('autoplay=0')) {
                            iframe.src = src.replace('autoplay=0', 'autoplay=1');
                        }
                    }
                    
                    currentIndex = parseInt(entry.target.dataset.index);
                } else {
                    // Pause when scrolling away
                    if (video) {
                        video.pause();
                        video.currentTime = 0;
                    }
                    if (iframe) {
                        // Stop YouTube video
                        const src = iframe.src;
                        iframe.src = '';
                        iframe.src = src.replace('autoplay=1', 'autoplay=0');
                    }
                }
            });
        }, {
            threshold: 0.5
        });
        
        // Observe all reels
        document.querySelectorAll('.reel').forEach(reel => {
            observer.observe(reel);
        });
        
        // Navigation
        document.getElementById('scrollUp').addEventListener('click', () => {
            if (currentIndex > 0) {
                currentIndex--;
                scrollToReel(currentIndex);
            }
        });
        
        document.getElementById('scrollDown').addEventListener('click', () => {
            if (currentIndex < videos.length - 1) {
                currentIndex++;
                scrollToReel(currentIndex);
            }
        });
        
        function scrollToReel(index) {
            const reels = document.querySelectorAll('.reel');
            if (reels[index]) {
                reels[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowUp') {
                document.getElementById('scrollUp').click();
            } else if (e.key === 'ArrowDown') {
                document.getElementById('scrollDown').click();
            } else if (e.key === ' ') {
                e.preventDefault();
                const currentVideo = document.querySelector(`.reel-video[data-index="${currentIndex}"]`);
                if (currentVideo && currentVideo.tagName === 'VIDEO') {
                    togglePlayPause(currentIndex);
                }
            }
        });
        
        // Toggle play/pause
        function togglePlayPause(index) {
            const video = document.querySelector(`.reel-video[data-index="${index}"]`);
            const overlay = video.parentElement.querySelector('.video-overlay');
            const playBtn = overlay.querySelector('.play-pause-btn i');
            
            if (video.paused) {
                video.play();
                playBtn.className = 'bi bi-pause-fill';
                overlay.classList.remove('paused');
            } else {
                video.pause();
                playBtn.className = 'bi bi-play-fill';
                overlay.classList.add('paused');
            }
        }
        
        // Toggle mute/unmute
        function toggleMute(index) {
            const video = document.querySelector(`.reel-video[data-index="${index}"]`);
            const muteBtn = video.parentElement.querySelector('.mute-btn i');
            
            if (video.muted) {
                video.muted = false;
                muteBtn.className = 'bi bi-volume-up-fill';
            } else {
                video.muted = true;
                muteBtn.className = 'bi bi-volume-mute-fill';
            }
        }
        
        // Like reel
        function likeReel(id, type) {
            fetch('<?= BASE_URL ?>/api/reels/like.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id, type: type})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const btn = event.target.closest('.like-btn');
                    btn.classList.toggle('active');
                    btn.querySelector('span').textContent = data.likes;
                }
            });
        }
        
        // Share reel
        function shareReel(id, type) {
            const url = type === 'article' 
                ? '<?= BASE_URL ?>/article/' + id
                : '<?= BASE_URL ?>/reel/' + id;
                
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this reel',
                    url: url
                });
            } else {
                navigator.clipboard.writeText(url);
                alert('Link copied to clipboard!');
            }
        }
        
        // Mute/unmute on click
        videos.forEach((video, index) => {
            if (video.tagName === 'VIDEO') {
                video.addEventListener('click', () => {
                    video.muted = !video.muted;
                });
            }
        });
        
        // Auto-play first video
        window.addEventListener('load', () => {
            const firstVideo = document.querySelector('.reel-video[data-index="0"]');
            if (firstVideo && firstVideo.tagName === 'VIDEO') {
                firstVideo.play().catch(e => console.log('Autoplay prevented:', e));
            }
        });
    </script>
</body>
</html>
