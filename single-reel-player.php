<?php
/**
 * Single Reel Player Page
 * Shows one reel with controls on the right side
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Session.php';
require_once 'includes/header.php';

$db = Database::getInstance();

// Get article ID from URL
$article_id = isset($_GET['article_id']) ? (int)$_GET['article_id'] : null;

if (!$article_id) {
    redirect(BASE_URL . '/reels.php');
}

// Get reel article
$article = $db->fetchOne("
    SELECT 
        a.id,
        a.title,
        a.slug,
        a.description,
        a.content,
        a.thumbnail,
        a.media_reel_url,
        a.media_reel_file,
        a.views_count,
        a.likes_count,
        a.status,
        a.published_at,
        c.id as category_id,
        c.name as category_name,
        c.slug as category_slug,
        au.id as author_id,
        au.full_name as author_name,
        au.profile_photo as author_avatar
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN authors au ON a.author_id = au.id
    WHERE a.id = ? AND a.content_type = 'reel' AND a.status = 'published'
", [$article_id]);

if (!$article) {
    redirect(BASE_URL . '/404.php');
}

// Update view count
trackView('reel', $article_id);

// Determine video URL
$video_url = !empty($article['media_reel_url']) 
    ? $article['media_reel_url'] 
    : BASE_URL . '/uploads/reels/' . $article['media_reel_file'];

// Check if user has liked this article
$user_has_liked = false;
if (Session::has('user_id')) {
    $user_id = Session::get('user_id');
    $like_check = $db->fetchOne("SELECT id FROM article_likes WHERE article_id = ? AND user_id = ?", [$article_id, $user_id]);
    $user_has_liked = !empty($like_check);
}

$has_article_content = !empty($article['content']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?> - <?= SITE_NAME ?></title>
    <meta name="description" content="<?= htmlspecialchars($article['description']) ?>">
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .single-reel-container {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .reel-video-section {
            flex: 1;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
            position: relative;
        }
        
        .reel-video-section video {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: 100%;
            object-fit: contain;
        }
        
        .video-controls {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        }
        
        .play-pause-btn {
            background: rgba(0, 0, 0, 0.6);
            border: none;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .play-pause-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }
        
        .play-pause-btn i {
            color: white;
            font-size: 40px;
        }
        
        .reel-sidebar {
            width: 400px;
            height: 100vh;
            background: #1a1a1a;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-header {
            margin-bottom: 20px;
        }
        
        .sidebar-header h2 {
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .sidebar-header .meta {
            color: #888;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .sidebar-header .meta i {
            margin-right: 5px;
        }
        
        .thumbnail-section {
            margin-bottom: 20px;
        }
        
        .thumbnail-section img {
            width: 100%;
            border-radius: 10px;
            object-fit: cover;
            aspect-ratio: 16/9;
        }
        
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .action-btn i {
            font-size: 20px;
        }
        
        .btn-read-article {
            background: #007bff;
            color: white;
        }
        
        .btn-read-article:hover {
            background: #0056b3;
        }
        
        .btn-play-reel {
            background: #28a745;
            color: white;
        }
        
        .btn-play-reel:hover {
            background: #1e7e34;
        }
        
        .btn-like {
            background: #dc3545;
            color: white;
        }
        
        .btn-like:hover {
            background: #c82333;
        }
        
        .btn-like.liked {
            background: #ff4757;
        }
        
        .btn-share {
            background: #6c757d;
            color: white;
        }
        
        .btn-share:hover {
            background: #545b62;
        }
        
        .description-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }
        
        .description-section h3 {
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .description-section p {
            color: #ccc;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
            background: rgba(0, 0, 0, 0.6);
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .back-button:hover {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .back-button i {
            color: white;
            font-size: 24px;
        }
        
        @media (max-width: 768px) {
            .single-reel-container {
                flex-direction: column;
            }
            
            .reel-video-section {
                height: 60vh;
            }
            
            .reel-sidebar {
                width: 100%;
                height: 40vh;
            }
        }
    </style>
</head>
<body>
    <div class="single-reel-container">
        <!-- Back Button -->
        <button class="back-button" onclick="window.history.back()">
            <i class="bi bi-arrow-left"></i>
        </button>
        
        <!-- Video Section -->
        <div class="reel-video-section">
            <video id="reelVideo" loop>
                <source src="<?= $video_url ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            
            <div class="video-controls" id="videoControls">
                <button class="play-pause-btn" id="playPauseBtn">
                    <i class="bi bi-play-fill" id="playPauseIcon"></i>
                </button>
            </div>
        </div>
        
        <!-- Right Sidebar -->
        <div class="reel-sidebar">
            <div class="sidebar-header">
                <h2><?= htmlspecialchars($article['title']) ?></h2>
                <div class="meta">
                    <span><i class="bi bi-eye"></i> <?= number_format($article['views_count']) ?></span>
                    <span><i class="bi bi-heart-fill"></i> <?= number_format($article['likes_count']) ?></span>
                </div>
            </div>
            
            <!-- Thumbnail -->
            <div class="thumbnail-section">
                <img src="<?= BASE_URL . '/' . $article['thumbnail'] ?>" alt="<?= htmlspecialchars($article['title']) ?>">
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <?php if ($has_article_content): ?>
                <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="action-btn btn-read-article">
                    <i class="bi bi-file-text"></i>
                    Read Full Article
                </a>
                <?php endif; ?>
                
                <button class="action-btn btn-play-reel" id="playReelBtn">
                    <i class="bi bi-play-circle"></i>
                    Play Reel
                </button>
                
                <button class="action-btn btn-like <?= $user_has_liked ? 'liked' : '' ?>" id="likeBtn" data-article-id="<?= $article['id'] ?>">
                    <i class="bi bi-heart-fill"></i>
                    <span id="likeText"><?= $user_has_liked ? 'Liked' : 'Like' ?></span>
                    <span id="likeCount">(<?= number_format($article['likes_count']) ?>)</span>
                </button>
                
                <button class="action-btn btn-share" id="shareBtn">
                    <i class="bi bi-share"></i>
                    Share
                </button>
            </div>
            
            <!-- Description -->
            <?php if (!empty($article['description'])): ?>
            <div class="description-section">
                <h3>Description</h3>
                <p><?= nl2br(htmlspecialchars($article['description'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const video = document.getElementById('reelVideo');
        const playPauseBtn = document.getElementById('playPauseBtn');
        const playPauseIcon = document.getElementById('playPauseIcon');
        const videoControls = document.getElementById('videoControls');
        const playReelBtn = document.getElementById('playReelBtn');
        const likeBtn = document.getElementById('likeBtn');
        const shareBtn = document.getElementById('shareBtn');
        
        let controlsTimeout;
        
        // Auto-play video on load
        video.play().catch(e => console.log('Autoplay prevented:', e));
        
        // Play/Pause toggle
        function togglePlayPause() {
            if (video.paused) {
                video.play();
                playPauseIcon.className = 'bi bi-pause-fill';
                playReelBtn.innerHTML = '<i class="bi bi-pause-circle"></i> Pause Reel';
            } else {
                video.pause();
                playPauseIcon.className = 'bi bi-play-fill';
                playReelBtn.innerHTML = '<i class="bi bi-play-circle"></i> Play Reel';
            }
            
            showControls();
        }
        
        playPauseBtn.addEventListener('click', togglePlayPause);
        playReelBtn.addEventListener('click', togglePlayPause);
        
        // Click video to toggle play/pause
        video.addEventListener('click', () => {
            togglePlayPause();
        });
        
        // Show controls temporarily
        function showControls() {
            videoControls.style.opacity = '1';
            clearTimeout(controlsTimeout);
            
            if (!video.paused) {
                controlsTimeout = setTimeout(() => {
                    videoControls.style.opacity = '0';
                }, 2000);
            }
        }
        
        // Show controls on mouse move
        document.querySelector('.reel-video-section').addEventListener('mousemove', showControls);
        
        videoControls.style.transition = 'opacity 0.3s';
        showControls();
        
        // Update button when video state changes
        video.addEventListener('play', () => {
            playPauseIcon.className = 'bi bi-pause-fill';
            playReelBtn.innerHTML = '<i class="bi bi-pause-circle"></i> Pause Reel';
        });
        
        video.addEventListener('pause', () => {
            playPauseIcon.className = 'bi bi-play-fill';
            playReelBtn.innerHTML = '<i class="bi bi-play-circle"></i> Play Reel';
        });
        
        // Like button handler
        likeBtn.addEventListener('click', function() {
            const articleId = this.dataset.articleId;
            
            <?php if (!Session::has('user_id')): ?>
                window.location.href = '<?= BASE_URL ?>/login.php';
                return;
            <?php endif; ?>
            
            $.ajax({
                url: '<?= BASE_URL ?>/api/articles/like.php',
                type: 'POST',
                data: JSON.stringify({ article_id: articleId }),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success) {
                        const isLiked = response.liked;
                        likeBtn.classList.toggle('liked', isLiked);
                        document.getElementById('likeText').textContent = isLiked ? 'Liked' : 'Like';
                        document.getElementById('likeCount').textContent = `(${response.likes_count.toLocaleString()})`;
                    }
                },
                error: function() {
                    alert('Failed to update like status');
                }
            });
        });
        
        // Share button handler
        shareBtn.addEventListener('click', function() {
            const shareUrl = '<?= BASE_URL ?>/reel/<?= $article['id'] ?>';
            const shareTitle = '<?= addslashes($article['title']) ?>';
            
            if (navigator.share) {
                navigator.share({
                    title: shareTitle,
                    url: shareUrl
                }).catch(err => console.log('Error sharing:', err));
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(shareUrl).then(() => {
                    const originalText = shareBtn.innerHTML;
                    shareBtn.innerHTML = '<i class="bi bi-check"></i> Link Copied!';
                    setTimeout(() => {
                        shareBtn.innerHTML = originalText;
                    }, 2000);
                });
            }
        });
    </script>
</body>
</html>
