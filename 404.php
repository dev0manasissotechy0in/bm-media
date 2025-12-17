<?php
require_once 'config/config.php';

http_response_code(404);

$page_title = '404 - Page Not Found';
$page_description = 'The page you are looking for could not be found.';

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="error-404">
                <h1 class="display-1 fw-bold text-primary">404</h1>
                <h2 class="mb-3">Page Not Found</h2>
                <p class="lead text-muted mb-4">
                    Sorry, the page you are looking for doesn't exist or has been moved.
                </p>
                
                <div class="mb-4">
                    <a href="<?= BASE_URL ?>" class="btn btn-primary btn-lg me-2">
                        <i class="bi bi-house-door"></i> Go Home
                    </a>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-arrow-left"></i> Go Back
                    </a>
                </div>
                
                <!-- Search Form -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Or search for something:</h5>
                        <form method="GET" action="<?= BASE_URL ?>/search.php">
                            <div class="input-group">
                                <input type="text" 
                                       name="q" 
                                       class="form-control" 
                                       placeholder="Search articles..." 
                                       required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popular Articles -->
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="mb-3">Popular Articles</h3>
        </div>
        <?php
        $db = Database::getInstance();
        $popular = $db->fetchAll("
            SELECT * FROM articles 
            WHERE status = 'published' 
            ORDER BY views_count DESC 
            LIMIT 4
        ");
        
        foreach ($popular as $article):
        ?>
        <div class="col-md-6 col-lg-3">
            <div class="card h-100">
                <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>">
                    <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                         class="card-img-top" 
                         alt="<?= htmlspecialchars($article['title']) ?>"
                         onerror="this.src='<?= ASSETS_URL ?>/images/placeholder.jpg'">
                </a>
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="text-decoration-none text-dark">
                            <?= htmlspecialchars(truncateText($article['title'], 60)) ?>
                        </a>
                    </h5>
                    <div class="article-meta">
                        <small class="text-muted">
                            <i class="bi bi-eye"></i> <?= formatNumber($article['views_count']) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
