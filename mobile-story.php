<?php
/**
 * Single Mobile Story View - Shareable with meta tags
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Initialize ads manager for mobile story view
require_once 'includes/AdsManager.php';
AdsManager::init($db);

// Get story ID from URL
$story_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$story_id) {
    header('Location: mobile-stories.php');
    exit;
}

// Get story details
$story = $db->fetchOne("
    SELECT m.*, c.name as category_name, c.slug as category_slug
    FROM mobile_stories m
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE m.id = ? AND m.status = 'active'
", [$story_id]);

if (!$story) {
    header('Location: mobile-stories.php');
    exit;
}

// Increment view count
trackView('mobile_story', $story_id);

// Meta tags for sharing
$page_title = htmlspecialchars($story['title']);
$page_description = htmlspecialchars($story['category_name'] ?? 'Mobile Story');
$page_image = BASE_URL . '/' . $story['image_url'];
$page_url = BASE_URL . '/mobile-story.php?id=' . $story_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $page_description ?>">
    <meta name="keywords" content="mobile story, news, <?= $story['category_name'] ?? '' ?>">
    
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
    <meta property="og:image:width" content="1080">
    <meta property="og:image:height" content="1920">
    
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
        
        .story-progress {
            position: absolute;
            top: 10px;
            left: 10px;
            right: 10px;
            height: 3px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            z-index: 10;
            overflow: hidden;
        }
        
        .story-progress-fill {
            height: 100%;
            background: white;
            width: 0%;
            animation: progress <?= $story['duration'] ?? 5 ?>s linear forwards;
        }
        
        @keyframes progress {
            to { width: 100%; }
        }
        
        .story-image {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
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
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
        
        .share-btns {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .share-btns .btn {
            flex: 1;
        }
    </style>
    
    <!-- Google AdSense Script for Mobile -->
    <?php echo AdsManager::getAdsenseScript(); ?>
</head>
<body>
    <div class="story-container">
        <!-- Progress Bar -->
        <div class="story-progress">
            <div class="story-progress-fill"></div>
        </div>
        
        <!-- Close Button -->
        <button class="close-btn" onclick="window.location.href='mobile-stories.php'">
            <i class="bi bi-x-lg"></i>
        </button>
        
        <!-- Story Header -->
        <div class="story-header">
            <div class="story-avatar">
                <i class="bi bi-phone"></i>
            </div>
            <div class="story-info">
                <h5><?= htmlspecialchars($story['category_name'] ?? 'Mobile Story') ?></h5>
                <small><?= timeAgo($story['created_at']) ?></small>
            </div>
        </div>
        
        <!-- Story Image -->
        <img src="<?= htmlspecialchars($story['image_url']) ?>" 
             alt="<?= htmlspecialchars($story['title']) ?>" 
             class="story-image">
        
        <!-- Story Footer -->
        <div class="story-footer">
            <h4><?= htmlspecialchars($story['title']) ?></h4>
            <?php if ($story['link']): ?>
            <a href="<?= htmlspecialchars($story['link']) ?>" target="_blank" class="text-white small">
                <i class="bi bi-link-45deg"></i> Read more
            </a>
            <?php endif; ?>
            <div class="share-btns">
                <button class="btn btn-light btn-sm" onclick="shareWhatsApp()">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </button>
                <button class="btn btn-light btn-sm" onclick="shareFacebook()">
                    <i class="bi bi-facebook"></i> Facebook
                </button>
                <button class="btn btn-light btn-sm" onclick="copyLink()">
                    <i class="bi bi-link-45deg"></i> Copy
                </button>
            </div>
            <a href="mobile-stories.php" class="btn btn-outline-light btn-sm w-100 mt-2">
                <i class="bi bi-arrow-left"></i> All Stories
            </a>
        </div>
    </div>

    <!-- Mobile Ad (Bottom) -->
    <?php echo AdsManager::showBannerAd(); ?>
    <?php echo AdsManager::showCustomAd('mobile', 'bottom'); ?>

    <script>
        const storyUrl = '<?= $page_url ?>';
        const storyTitle = '<?= addslashes($page_title) ?>';
        
        function shareWhatsApp() {
            const url = encodeURIComponent(storyUrl);
            const text = encodeURIComponent(storyTitle);
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
        }
        
        function shareFacebook() {
            const url = encodeURIComponent(storyUrl);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }
        
        function copyLink() {
            navigator.clipboard.writeText(storyUrl).then(() => {
                alert('Link copied to clipboard!');
            });
        }
        
        // Auto-redirect after duration
        setTimeout(() => {
            window.location.href = 'mobile-stories.php';
        }, <?= ($story['duration'] ?? 5) * 1000 ?>);
    </script>
</body>
</html>
