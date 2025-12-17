<?php
require_once '../../config/config.php';

// Check if download is enabled
if (!ENABLE_ARTICLE_DOWNLOAD) {
    die('Article downloads are disabled');
}

$article_id = $_GET['id'] ?? 0;

if (!$article_id) {
    die('Invalid article ID');
}

$db = Database::getInstance();

// Get article with sections
$article = $db->fetchOne("
    SELECT a.*, c.name as category_name,
           CASE 
               WHEN a.author_type = 'admin' THEN ad.full_name
               WHEN a.author_type = 'reporter' THEN r.full_name
           END as author_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admin_users ad ON a.author_id = ad.id AND a.author_type = 'admin'
    LEFT JOIN reporters r ON a.author_id = r.id AND a.author_type = 'reporter'
    WHERE a.id = ? AND a.status = 'published'
", [$article_id]);

if (!$article) {
    die('Article not found');
}

// Get sections
$sections = $db->fetchAll("
    SELECT * FROM article_sections 
    WHERE article_id = ? 
    ORDER BY order_id ASC
", [$article_id]);

// Increment download count
$db->query("UPDATE articles SET downloads_count = downloads_count + 1 WHERE id = ?", [$article_id]);

// Track download if user is logged in
if (Security::isLoggedIn('user')) {
    $db->insert('article_downloads', [
        'article_id' => $article_id,
        'user_id' => $_SESSION['user_id'],
        'downloaded_at' => date('Y-m-d H:i:s')
    ]);
}

// Generate HTML for download
$filename = Security::createSlug($article['title']) . '.html';

// Set headers to display inline - users can save with Ctrl+S or right-click
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, no-cache');

// Generate HTML content with download instructions
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['title']) ?></title>
    <style>
        .download-notice {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #28a745;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .download-notice button {
            background: white;
            color: #28a745;
            border: none;
            padding: 8px 20px;
            margin: 0 5px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .download-notice button:hover {
            background: #f8f9fa;
        }
        .close-notice {
            background: transparent !important;
            color: white !important;
            font-size: 20px;
            padding: 0 10px !important;
        }
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 60px auto 0 auto;
            padding: 20px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .site-name {
            color: #007bff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 32px;
            margin: 20px 0;
            color: #000;
        }
        .meta {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
        }
        .meta span {
            margin-right: 15px;
        }
        .category {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            margin: 10px 0;
        }
        .featured-image {
            width: 100%;
            max-width: 800px;
            height: auto;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .description {
            font-size: 18px;
            font-weight: 500;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .content {
            margin: 30px 0;
        }
        .content h2, .content h3, .content h4 {
            margin: 20px 0 10px 0;
            color: #000;
        }
        .content p {
            margin: 15px 0;
            text-align: justify;
        }
        .content img {
            max-width: 100%;
            height: auto;
            margin: 20px 0;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        @media print {
            body {
                max-width: 100%;
                margin-top: 0;
            }
            .download-notice {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Download Notice Bar -->
    <div class="download-notice" id="downloadNotice">
        <span>📥 Article ready for download! </span>
        <button onclick="saveAsFile()">💾 Save as File</button>
        <button onclick="window.print()">🖨️ Print/Save as PDF</button>
        <button class="close-notice" onclick="document.getElementById('downloadNotice').style.display='none'">&times;</button>
    </div>
    
    <div class="header">
        <div class="site-name"><?= htmlspecialchars(SITE_NAME) ?></div>
        
        <?php if ($article['category_name']): ?>
        <span class="category"><?= htmlspecialchars($article['category_name']) ?></span>
        <?php endif; ?>
        
        <h1><?= htmlspecialchars($article['title']) ?></h1>
        
        <div class="meta">
            <span><strong>Author:</strong> <?= htmlspecialchars($article['author_name'] ?? 'N/A') ?></span>
            <span><strong>Published:</strong> <?= formatDate($article['published_at'] ?? '', 'd M, Y H:i', $article['created_at'] ?? '') ?></span>
            <span><strong>Views:</strong> <?= formatNumber($article['views_count']) ?></span>
        </div>
    </div>
    
    <?php if (!empty($article['thumbnail'])): ?>
    <div>
        <img src="<?= UPLOADS_URL ?>/articles/<?= htmlspecialchars($article['thumbnail']) ?>" 
             alt="<?= htmlspecialchars($article['thumbnail_alt'] ?: $article['title']) ?>" 
             class="featured-image">
    </div>
    <?php endif; ?>
    
    <?php if ($article['description']): ?>
    <div class="description">
        <?= nl2br(htmlspecialchars($article['description'])) ?></div>
    <?php endif; ?>
    
    <div class="content">
        <?php if (!empty($article['content'])): ?>
            <?= Security::cleanHTML($article['content']) ?>
        <?php endif; ?>
        
        <?php foreach ($sections as $section): ?>
            <?php if ($section['section_type'] === 'subtitle' && $section['subtitle']): ?>
                <h3><?= htmlspecialchars($section['subtitle']) ?></h3>
            <?php endif; ?>
            
            <?php if ($section['content']): ?>
                <?= Security::cleanHTML($section['content']) ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <div class="footer">
        <p>Downloaded from <?= htmlspecialchars(SITE_NAME) ?> on <?= date('d M, Y H:i') ?></p>
        <p><?= htmlspecialchars(SITE_URL) ?></p>
    </div>
    
    <script>
    // Function to save page as HTML file
    function saveAsFile() {
        const filename = '<?= addslashes($filename) ?>';
        const content = document.documentElement.outerHTML;
        const blob = new Blob([content], { type: 'text/html' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
    
    // Alternative keyboard shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveAsFile();
        }
    });
    </script>
</body>
</html>
