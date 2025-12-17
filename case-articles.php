<?php
/**
 * Case Articles List Page - All articles for a specific case
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Get case slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: case-threads');
    exit;
}

// Fetch case details
$case = $db->fetchOne("SELECT * FROM case_threads WHERE slug = ?", [$slug]);

if (!$case) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Fetch total articles count
$totalCount = $db->fetchOne("
    SELECT COUNT(*) as total 
    FROM case_article_map cam
    JOIN articles a ON cam.article_id = a.id
    WHERE cam.case_id = ? AND a.status = 'published'
", [$case['id']]);

$total = $totalCount['total'] ?? 0;
$totalPages = ceil($total / $limit);

// Fetch articles
$articles = $db->fetchAll("
    SELECT 
        a.id, a.title, a.slug, a.thumbnail, a.description, a.published_at, 
        a.views_count, a.comments_count,
        cam.is_key_article, cam.relevance_score,
        c.name as category_name, c.slug as category_slug,
        au.full_name as author_name, au.id as author_id
    FROM case_article_map cam
    JOIN articles a ON cam.article_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN authors au ON a.author_id = au.id
    WHERE cam.case_id = ? AND a.status = 'published'
    ORDER BY cam.is_key_article DESC, a.published_at DESC
    LIMIT ? OFFSET ?
", [$case['id'], $limit, $offset]);

$page_title = "Articles - " . $case['title'];
$page_description = "All articles and news coverage related to " . $case['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: #f5f5f5;
        }
        
        .page-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .breadcrumb {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            font-size: 14px;
        }
        
        .breadcrumb a {
            color: #1e3c72;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }
        
        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .article-thumbnail {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .article-content {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .key-article-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .article-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1a1a1a;
            line-height: 1.4;
        }
        
        .article-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .article-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 13px;
            color: #888;
            margin-top: auto;
        }
        
        .article-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 40px;
        }
        
        .pagination a, .pagination span {
            padding: 10px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            text-decoration: none;
            color: #1e3c72;
            font-weight: 500;
        }
        
        .pagination a:hover {
            background: #1e3c72;
            color: white;
        }
        
        .pagination .current {
            background: #1e3c72;
            color: white;
            border-color: #1e3c72;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }
        
        .empty-state svg {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="page-header">
        <h1><?php echo htmlspecialchars($case['title']); ?></h1>
        <p>All Articles & News Coverage (<?php echo number_format($total); ?>)</p>
    </div>
    
    <div class="breadcrumb">
        <a href="/">Home</a> / 
        <a href="/case-threads">Case Threads</a> / 
        <a href="/case/<?php echo $case['slug']; ?>"><?php echo htmlspecialchars($case['title']); ?></a> / 
        Articles
    </div>
    
    <div class="container">
        <?php if (empty($articles)): ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h2>No Articles Found</h2>
                <p>There are no articles linked to this case yet.</p>
            </div>
        <?php else: ?>
            <div class="articles-grid">
                <?php foreach ($articles as $article): ?>
                    <a href="/article/<?php echo $article['slug']; ?>" class="article-card">
                        <img src="<?php echo htmlspecialchars($article['thumbnail'] ?: '/assets/images/default-thumbnail.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($article['title']); ?>" 
                             class="article-thumbnail"
                             onerror="this.src='/assets/images/default-thumbnail.jpg'">
                        <div class="article-content">
                            <?php if ($article['is_key_article']): ?>
                                <span class="key-article-badge">⭐ Key Article</span>
                            <?php endif; ?>
                            <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                            <?php if ($article['description']): ?>
                                <p class="article-description"><?php echo htmlspecialchars($article['description']); ?></p>
                            <?php endif; ?>
                            <div class="article-meta">
                                <?php if ($article['author_name']): ?>
                                    <span><?php echo htmlspecialchars($article['author_name']); ?></span>
                                    <span>•</span>
                                <?php endif; ?>
                                <span><?php echo date('M j, Y', strtotime($article['published_at'])); ?></span>
                                <?php if ($article['category_name']): ?>
                                    <span>•</span>
                                    <span><?php echo htmlspecialchars($article['category_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>">← Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
