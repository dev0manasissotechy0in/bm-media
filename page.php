<?php
/**
 * Custom Page Frontend Display
 */

require_once 'config/config.php';

// Get page slug from URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect(BASE_URL . '/404.php');
}

$db = Database::getInstance();

// Get page details
$page = $db->fetchOne("SELECT * FROM custom_pages WHERE slug = ? AND status = 'published'", [$slug]);

if (!$page) {
    redirect(BASE_URL . '/404.php');
}

// Increment views
$db->query("UPDATE custom_pages SET views_count = views_count + 1 WHERE id = ?", [$page['id']]);

// Page meta
$page_title = $page['meta_title'] ?? $page['title'];
$page_description = $page['meta_description'] ?? substr(strip_tags($page['content'] ?? ''), 0, 160);

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <!-- Page Header -->
            <div class="page-header mb-4">
                <h1 class="display-5"><?= htmlspecialchars($page['title']) ?></h1>
            </div>

            <!-- Page Content -->
            <div class="custom-page-content">
                <?php if ($page['page_type'] === 'text'): ?>
                    <!-- Text Content -->
                    <div class="content-text">
                        <?= $page['content'] ?>
                    </div>

                <?php elseif ($page['page_type'] === 'category_articles'): ?>
                    <!-- Category Based Articles -->
                    <?php
                    $articles = $db->fetchAll("
                        SELECT a.*, c.name as category_name, c.slug as category_slug
                        FROM articles a
                        LEFT JOIN categories c ON a.category_id = c.id
                        WHERE a.category_id = ? AND a.status = 'published'
                        ORDER BY a.published_at DESC
                        LIMIT 20
                    ", [$page['category_id']]);
                    ?>
                    
                    <div class="row g-4">
                        <?php foreach ($articles as $article): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <?php if ($article['thumbnail']): ?>
                                <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <?= substr(strip_tags($article['description']), 0, 100) ?>...
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= date('M d, Y', strtotime($article['published_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($page['page_type'] === 'tag_articles'): ?>
                    <!-- Tag Based Articles -->
                    <?php
                    $articles = $db->fetchAll("
                        SELECT a.*, GROUP_CONCAT(t.name) as tag_names
                        FROM articles a
                        LEFT JOIN article_tags at ON a.id = at.article_id
                        LEFT JOIN tags t ON at.tag_id = t.id
                        WHERE at.tag_id = ? AND a.status = 'published'
                        GROUP BY a.id
                        ORDER BY a.published_at DESC
                        LIMIT 20
                    ", [$page['tag_id']]);
                    ?>
                    
                    <div class="row g-4">
                        <?php foreach ($articles as $article): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100">
                                <?php if ($article['thumbnail']): ?>
                                <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($article['title']) ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted">
                                        <?= substr(strip_tags($article['description']), 0, 100) ?>...
                                    </p>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> <?= date('M d, Y', strtotime($article['published_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ($page['page_type'] === 'live_polls'): ?>
                    <!-- Live Election Polls -->
                    <div class="election-polls">
                        <div id="election-polls-container">
                            <!-- Polls will be loaded via AJAX -->
                        </div>
                    </div>
                    <script>
                    // Load election polls
                    fetch('<?= BASE_URL ?>/api/election/get_polls.php')
                        .then(response => response.json())
                        .then(data => {
                            // Display polls data
                            console.log(data);
                        });
                    </script>

                <?php elseif ($page['page_type'] === 'statistics'): ?>
                    <!-- Statistics Page -->
                    <div class="statistics-content">
                        <?= $page['content'] ?>
                    </div>

                <?php elseif ($page['page_type'] === 'graphics'): ?>
                    <!-- Graphics/Charts Page -->
                    <div class="graphics-content">
                        <?= $page['content'] ?>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
