<?php
/**
 * Single Story View - Shareable with meta tags
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Get story ID from URL
$story_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$story_id) {
    header('Location: stories.php');
    exit;
}

// Get story details
$story = $db->fetchOne("
    SELECT s.*, c.name as category_name, c.slug as category_slug
    FROM stories s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.id = ? AND s.status = 'active'
", [$story_id]);

if (!$story) {
    header('Location: stories.php');
    exit;
}

// Get media for this story
$story_media = $db->fetchAll("SELECT * FROM story_media WHERE story_id = ? ORDER BY order_id ASC", [$story_id]);

// If story_media exists, use it; otherwise fall back to stories.media_url
if (!empty($story_media)) {
    $story['media_items'] = $story_media;
    $story['thumbnail_url'] = $story_media[0]['media_url'];
    $story['thumbnail_type'] = $story_media[0]['media_type'];
} else {
    $story['media_items'] = [[
        'media_url' => $story['media_url'],
        'media_type' => $story['media_type']
    ]];
    $story['thumbnail_url'] = $story['media_url'];
    $story['thumbnail_type'] = $story['media_type'];
}

// Increment view count
trackView('story', $story_id);

// Meta tags for sharing
$page_title = htmlspecialchars($story['title']);
$page_description = htmlspecialchars($story['category_name'] ?? 'News Story');
$page_image = BASE_URL . '/' . $story['thumbnail_url'];
$page_url = BASE_URL . '/story.php?id=' . $story_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $page_description ?>">
    <meta name="keywords" content="news, story, <?= $story['category_name'] ?? '' ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= $page_url ?>">
    <meta property="og:title" content="<?= $page_title ?>">
    <meta property="og:description" content="<?= $page_description ?>">
    <meta property="og:image" content="<?= $page_image ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= $page_url ?>">
    <meta name="twitter:title" content="<?= $page_title ?>">
    <meta name="twitter:description" content="<?= $page_description ?>">
    <meta name="twitter:image" content="<?= $page_image ?>">
    
    <!-- WhatsApp -->
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body {
            background: #000;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .story-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }
        
        .story-progress-bars {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            display: flex;
            gap: 4px;
            z-index: 10;
        }
        
        .progress-segment {
            flex: 1;
            height: 3px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-segment-fill {
            height: 100%;
            background: white;
            width: 0%;
            transition: width 0.1s linear;
        }
        
        .story-media {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            opacity: 0;
            animation: fadeIn 0.3s forwards;
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        .story-header {
            position: absolute;
            top: 50px;
            left: 10px;
            right: 10px;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .story-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            background: #333;
        }
        
        .story-info h5 {
            color: white;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        
        .story-info small {
            color: rgba(255,255,255,0.8);
            font-size: 12px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        
        .story-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            z-index: 10;
        }
        
        .story-footer h4 {
            color: white;
            margin: 0 0 10px 0;
            font-size: 18px;
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
        }
        
        .story-footer .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .story-footer .btn {
            flex: 1;
            border-radius: 25px;
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 20;
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.3);
            border: none;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            font-size: 24px;
        }
        
        .nav-btn:hover {
            background: rgba(0,0,0,0.5);
        }
        
        .nav-btn.prev {
            left: 10px;
        }
        
        .nav-btn.next {
            right: 10px;
        }
        
        .share-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .share-modal.active {
            display: flex;
        }
        
        .share-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
        }
        
        .share-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .share-option {
            text-align: center;
            cursor: pointer;
            padding: 15px;
            border-radius: 10px;
            transition: background 0.2s;
        }
        
        .share-option:hover {
            background: #f0f0f0;
        }
        
        .share-option i {
            font-size: 32px;
            display: block;
            margin-bottom: 8px;
        }
        
        .share-option.whatsapp i { color: #25D366; }
        .share-option.facebook i { color: #1877F2; }
        .share-option.twitter i { color: #1DA1F2; }
        .share-option.copy i { color: #666; }
    </style>
</head>
<body>
    <div class="story-container">
        <!-- Progress Bars -->
        <div class="story-progress-bars">
            <?php foreach ($story['media_items'] as $index => $media): ?>
            <div class="progress-segment">
                <div class="progress-segment-fill" id="progress-<?= $index ?>"></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Close Button -->
        <button class="close-btn" onclick="window.location.href='stories.php'">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <!-- Story Header -->
        <div class="story-header">
            <div class="story-avatar">
                <i class="bi bi-newspaper" style="color: white; font-size: 20px; margin: 8px;"></i>
            </div>
            <div class="story-info">
                <h5><?= htmlspecialchars($story['category_name'] ?? 'News') ?></h5>
                <small><?= timeAgo($story['created_at']) ?></small>
            </div>
        </div>
        
        <!-- Story Media -->
        <div id="mediaContainer"></div>
        
        <!-- Navigation Buttons -->
        <button class="nav-btn prev" id="prevBtn" onclick="previousMedia()" style="display: none;">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="nav-btn next" id="nextBtn" onclick="nextMedia()">
            <i class="bi bi-chevron-right"></i>
        </button>
        
        <!-- Story Footer -->
        <div class="story-footer">
            <h4><?= htmlspecialchars($story['title']) ?></h4>
            <?php if ($story['link']): ?>
            <a href="<?= htmlspecialchars($story['link']) ?>" target="_blank" class="text-white small">
                <i class="bi bi-link-45deg"></i> Read full story
            </a>
            <?php endif; ?>
            <div class="btn-group mt-3">
                <button class="btn btn-light btn-sm" onclick="openShareModal()">
                    <i class="bi bi-share"></i> Share
                </button>
                <a href="stories.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> All Stories
                </a>
            </div>
        </div>
    </div>
    
    <!-- Share Modal -->
    <div class="share-modal" id="shareModal">
        <div class="share-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0">Share Story</h5>
                <button class="btn-close" onclick="closeShareModal()"></button>
            </div>
            <div class="share-options">
                <div class="share-option whatsapp" onclick="shareWhatsApp()">
                    <i class="bi bi-whatsapp"></i>
                    <small>WhatsApp</small>
                </div>
                <div class="share-option facebook" onclick="shareFacebook()">
                    <i class="bi bi-facebook"></i>
                    <small>Facebook</small>
                </div>
                <div class="share-option twitter" onclick="shareTwitter()">
                    <i class="bi bi-twitter"></i>
                    <small>Twitter</small>
                </div>
                <div class="share-option copy" onclick="copyLink()">
                    <i class="bi bi-link-45deg"></i>
                    <small>Copy Link</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        const storyData = <?= json_encode($story) ?>;
        let currentMediaIndex = 0;
        let progressInterval;
        const duration = storyData.duration || 5;
        
        function loadMedia(index) {
            if (index < 0 || index >= storyData.media_items.length) return;
            
            currentMediaIndex = index;
            const media = storyData.media_items[index];
            const container = document.getElementById('mediaContainer');
            
            // Fade out current media
            container.style.opacity = '0';
            
            setTimeout(() => {
                if (media.media_type === 'video') {
                    container.innerHTML = `<video src="${media.media_url}" class="story-media" autoplay muted></video>`;
                } else {
                    container.innerHTML = `<img src="${media.media_url}" class="story-media" alt="${storyData.title}">`;
                }
                
                // Fade in new media
                setTimeout(() => {
                    container.style.opacity = '1';
                }, 50);
            }, 300);
            
            // Update navigation buttons
            document.getElementById('prevBtn').style.display = index > 0 ? 'flex' : 'none';
            document.getElementById('nextBtn').style.display = index < storyData.media_items.length - 1 ? 'flex' : 'none';
            
            // Start progress
            startProgress(index);
        }
        
        function startProgress(index) {
            stopProgress();
            
            // Fill previous progress bars
            for (let i = 0; i < index; i++) {
                document.getElementById(`progress-${i}`).style.width = '100%';
            }
            
            // Reset future progress bars
            for (let i = index + 1; i < storyData.media_items.length; i++) {
                document.getElementById(`progress-${i}`).style.width = '0%';
            }
            
            // Animate current progress bar
            let progress = 0;
            const increment = 100 / (duration * 10);
            const currentBar = document.getElementById(`progress-${index}`);
            
            progressInterval = setInterval(() => {
                progress += increment;
                currentBar.style.width = progress + '%';
                
                if (progress >= 100) {
                    if (index < storyData.media_items.length - 1) {
                        nextMedia();
                    } else {
                        stopProgress();
                    }
                }
            }, 100);
        }
        
        function stopProgress() {
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
        }
        
        function nextMedia() {
            if (currentMediaIndex < storyData.media_items.length - 1) {
                loadMedia(currentMediaIndex + 1);
            }
        }
        
        function previousMedia() {
            if (currentMediaIndex > 0) {
                loadMedia(currentMediaIndex - 1);
            }
        }
        
        // Share functions
        function openShareModal() {
            stopProgress();
            document.getElementById('shareModal').classList.add('active');
        }
        
        function closeShareModal() {
            document.getElementById('shareModal').classList.remove('active');
            startProgress(currentMediaIndex);
        }
        
        function shareWhatsApp() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(storyData.title);
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
        }
        
        function shareFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }
        
        function shareTwitter() {
            const url = encodeURIComponent(window.location.href);
            const text = encodeURIComponent(storyData.title);
            window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
        }
        
        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link copied to clipboard!');
                closeShareModal();
            });
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') previousMedia();
            if (e.key === 'ArrowRight') nextMedia();
            if (e.key === 'Escape') window.location.href = 'stories.php';
        });
        
        // Initialize
        loadMedia(0);
    </script>
</body>
</html>
