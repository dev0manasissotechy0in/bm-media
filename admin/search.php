<?php
/**
 * Admin Search
 */

require_once 'auth_check.php';

$page_title = 'Search Results';

$db = Database::getInstance();

$query = trim($_GET['q'] ?? '');
$results = [
    'articles' => [],
    'categories' => [],
    'tags' => [],
    'users' => [],
    'stories' => [],
    'reels' => []
];

if (!empty($query)) {
    $search_term = '%' . $query . '%';
    
    // Search Articles
    $results['articles'] = $db->fetchAll("
        SELECT id, title, status, created_at, content_type 
        FROM articles 
        WHERE title LIKE ? OR description LIKE ? OR content LIKE ?
        ORDER BY created_at DESC 
        LIMIT 20
    ", [$search_term, $search_term, $search_term]);
    
    // Search Categories
    $results['categories'] = $db->fetchAll("
        SELECT id, name, slug, status 
        FROM categories 
        WHERE name LIKE ? OR slug LIKE ?
        LIMIT 10
    ", [$search_term, $search_term]);
    
    // Search Tags
    $results['tags'] = $db->fetchAll("
        SELECT id, name, slug 
        FROM tags 
        WHERE name LIKE ? OR slug LIKE ?
        LIMIT 10
    ", [$search_term, $search_term]);
    
    // Search Users
    $results['users'] = $db->fetchAll("
        SELECT id, full_name, email, phone, status 
        FROM users 
        WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?
        LIMIT 10
    ", [$search_term, $search_term, $search_term]);
    
    // Search Stories
    $results['stories'] = $db->fetchAll("
        SELECT id, title, status, created_at 
        FROM stories 
        WHERE title LIKE ?
        LIMIT 10
    ", [$search_term]);
    
    // Search Reels
    $results['reels'] = $db->fetchAll("
        SELECT id, title, status, created_at 
        FROM reels 
        WHERE title LIKE ? OR description LIKE ?
        LIMIT 10
    ", [$search_term, $search_term]);
}

$total_results = count($results['articles']) + count($results['categories']) + 
                 count($results['tags']) + count($results['users']) + 
                 count($results['stories']) + count($results['reels']);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="mb-4">
            <h1 class="h3 mb-3">
                <i class="bi bi-search"></i> Search Results
                <?php if (!empty($query)): ?>
                    <small class="text-muted">for "<?= htmlspecialchars($query) ?>"</small>
                <?php endif; ?>
            </h1>
            
            <!-- Search Form -->
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control form-control-lg" name="q" 
                           placeholder="Search articles, categories, users..." 
                           value="<?= htmlspecialchars($query) ?>" autofocus>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
            
            <?php if (!empty($query)): ?>
                <p class="text-muted">Found <?= $total_results ?> result(s)</p>
            <?php endif; ?>
        </div>

        <?php if (empty($query)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Enter a search term to find articles, categories, tags, users, and more.
            </div>
        <?php elseif ($total_results === 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No results found for "<?= htmlspecialchars($query) ?>"
            </div>
        <?php else: ?>
            
            <!-- Articles Results -->
            <?php if (!empty($results['articles'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-newspaper"></i> Articles (<?= count($results['articles']) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results['articles'] as $article): ?>
                        <a href="article-edit.php?id=<?= $article['id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($article['title']) ?></h6>
                                    <small class="text-muted">
                                        <span class="badge bg-<?= $article['status'] === 'published' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($article['status']) ?>
                                        </span>
                                        <span class="badge bg-info"><?= ucfirst($article['content_type']) ?></span>
                                        • <?= date('M d, Y', strtotime($article['created_at'])) ?>
                                    </small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($results['articles']) >= 20): ?>
                    <p class="text-center mt-3 mb-0">
                        <small class="text-muted">Showing first 20 results. Try a more specific search.</small>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Categories Results -->
            <?php if (!empty($results['categories'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-tags"></i> Categories (<?= count($results['categories']) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results['categories'] as $category): ?>
                        <a href="category-edit.php?id=<?= $category['id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($category['name']) ?></h6>
                                    <small class="text-muted">
                                        <span class="badge bg-<?= $category['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($category['status']) ?>
                                        </span>
                                        • /<?= $category['slug'] ?>
                                    </small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tags Results -->
            <?php if (!empty($results['tags'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-hash"></i> Tags (<?= count($results['tags']) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($results['tags'] as $tag): ?>
                        <a href="tag-edit.php?id=<?= $tag['id'] ?>" class="badge bg-info text-decoration-none fs-6 p-2">
                            #<?= htmlspecialchars($tag['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stories Results -->
            <?php if (!empty($results['stories'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-collection-play"></i> Stories (<?= count($results['stories']) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results['stories'] as $story): ?>
                        <a href="story-edit.php?id=<?= $story['id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($story['title']) ?></h6>
                                    <small class="text-muted">
                                        <span class="badge bg-<?= $story['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($story['status']) ?>
                                        </span>
                                        • <?= date('M d, Y', strtotime($story['created_at'])) ?>
                                    </small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reels Results -->
            <?php if (!empty($results['reels'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-film"></i> Reels (<?= count($results['reels']) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results['reels'] as $reel): ?>
                        <a href="reel-edit.php?id=<?= $reel['id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($reel['title']) ?></h6>
                                    <small class="text-muted">
                                        <span class="badge bg-<?= $reel['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($reel['status']) ?>
                                        </span>
                                        • <?= date('M d, Y', strtotime($reel['created_at'])) ?>
                                    </small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Users Results -->
            <?php if (!empty($results['users'])): ?>
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> Users (<?= count($results['users']) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($results['users'] as $user): ?>
                        <a href="user-edit.php?id=<?= $user['id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><?= htmlspecialchars($user['full_name']) ?></h6>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($user['email']) ?> 
                                        <?php if (!empty($user['phone'])): ?>• <?= htmlspecialchars($user['phone']) ?><?php endif; ?>
                                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </small>
                                </div>
                                <i class="bi bi-chevron-right"></i>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
