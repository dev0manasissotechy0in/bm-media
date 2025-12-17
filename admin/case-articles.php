<?php
/**
 * Case Articles Management - Link existing articles to case
 */

require_once 'auth_check.php';

$page_title = 'Case Articles';

$db = Database::getInstance();

$case_id = (int)($_GET['case_id'] ?? 0);

if (!$case_id) {
    $_SESSION['error'] = 'Invalid case ID';
    header('Location: cases.php');
    exit;
}

// Get case
$case = $db->fetchOne("SELECT * FROM case_threads WHERE id = ?", [$case_id]);

if (!$case) {
    $_SESSION['error'] = 'Case not found';
    header('Location: cases.php');
    exit;
}

// Handle Remove Article
if (isset($_GET['remove'])) {
    $article_id = (int)$_GET['remove'];
    $db->query("DELETE FROM case_article_map WHERE case_id = ? AND article_id = ?", [$case_id, $article_id]);
    
    // Update count
    $db->query("UPDATE case_threads SET total_articles = (SELECT COUNT(*) FROM case_article_map WHERE case_id = ?) WHERE id = ?", [$case_id, $case_id]);
    
    $_SESSION['success'] = 'Article removed from case';
    header('Location: case-articles.php?case_id=' . $case_id);
    exit;
}

// Handle Add Article
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id'])) {
    $article_id = (int)$_POST['article_id'];
    $relevance_score = (int)($_POST['relevance_score'] ?? 50);
    $is_key_article = isset($_POST['is_key_article']) ? 1 : 0;
    
    // Check if already linked
    $existing = $db->fetchOne("SELECT id FROM case_article_map WHERE case_id = ? AND article_id = ?", [$case_id, $article_id]);
    
    if (!$existing) {
        $db->insert('case_article_map', [
            'case_id' => $case_id,
            'article_id' => $article_id,
            'relevance_score' => $relevance_score,
            'is_key_article' => $is_key_article
        ]);
        
        // Update count
        $db->query("UPDATE case_threads SET total_articles = (SELECT COUNT(*) FROM case_article_map WHERE case_id = ?) WHERE id = ?", [$case_id, $case_id]);
        
        $_SESSION['success'] = 'Article linked successfully';
    } else {
        $_SESSION['error'] = 'Article already linked to this case';
    }
    
    header('Location: case-articles.php?case_id=' . $case_id);
    exit;
}

// Handle Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mapping'])) {
    $mapping_id = (int)$_POST['mapping_id'];
    $relevance_score = (int)($_POST['relevance_score'] ?? 50);
    $is_key_article = isset($_POST['is_key_article']) ? 1 : 0;
    
    $db->query("
        UPDATE case_article_map 
        SET relevance_score = ?, is_key_article = ?
        WHERE id = ? AND case_id = ?
    ", [$relevance_score, $is_key_article, $mapping_id, $case_id]);
    
    $_SESSION['success'] = 'Settings updated';
    header('Location: case-articles.php?case_id=' . $case_id);
    exit;
}

// Get linked articles
$linked_articles = $db->fetchAll("
    SELECT 
        a.id, a.title, a.slug, a.thumbnail, a.published_at, a.views_count,
        c.name as category_name,
        au.full_name as author_name,
        cam.id as mapping_id, cam.relevance_score, cam.is_key_article
    FROM case_article_map cam
    JOIN articles a ON cam.article_id = a.id
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN authors au ON a.author_id = au.id
    WHERE cam.case_id = ?
    ORDER BY cam.is_key_article DESC, cam.relevance_score DESC, a.published_at DESC
", [$case_id]);

// Search articles
$search = $_GET['search'] ?? '';
$available_articles = [];

if (!empty($search)) {
    $available_articles = $db->fetchAll("
        SELECT 
            a.id, a.title, a.slug, a.thumbnail, a.published_at,
            c.name as category_name,
            au.full_name as author_name
        FROM articles a
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN authors au ON a.author_id = au.id
        WHERE a.status = 'published' 
        AND (a.title LIKE ? OR a.slug LIKE ?)
        AND a.id NOT IN (SELECT article_id FROM case_article_map WHERE case_id = ?)
        ORDER BY a.published_at DESC
        LIMIT 20
    ", ["%{$search}%", "%{$search}%", $case_id]);
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Linked Articles: <?php echo htmlspecialchars($case['title']); ?></h1>
        <a href="case-edit.php?id=<?php echo $case_id; ?>" class="btn btn-secondary">← Back to Case</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">
                    <h5>Search & Link Articles</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search articles by title..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>

                    <?php if (!empty($search)): ?>
                        <?php if (empty($available_articles)): ?>
                            <p class="text-muted">No articles found matching "<?php echo htmlspecialchars($search); ?>"</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($available_articles as $article): ?>
                                    <div class="list-group-item">
                                        <form method="POST" class="mb-0">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                            
                                            <h6><?php echo htmlspecialchars($article['title']); ?></h6>
                                            <p class="mb-2">
                                                <small class="text-muted">
                                                    <?php echo $article['category_name'] ?? 'No category'; ?> • 
                                                    <?php echo date('M j, Y', strtotime($article['published_at'])); ?>
                                                </small>
                                            </p>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label><small>Relevance (0-100)</small></label>
                                                    <input type="number" name="relevance_score" class="form-control form-control-sm" 
                                                           value="50" min="0" max="100">
                                                </div>
                                                <div class="col-md-6">
                                                    <label><small>&nbsp;</small></label>
                                                    <div class="form-check">
                                                        <input type="checkbox" name="is_key_article" class="form-check-input">
                                                        <label class="form-check-label"><small>Key Article</small></label>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-sm btn-success mt-2">
                                                <i class="fas fa-link"></i> Link to Case
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">Enter keywords to search for articles</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5>Linked Articles (<?php echo count($linked_articles); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($linked_articles)): ?>
                        <p class="text-center text-muted">No articles linked yet. Search and link articles from the left panel.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Article</th>
                                        <th>Relevance</th>
                                        <th>Key</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($linked_articles as $article): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($article['title']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo $article['category_name'] ?? 'No category'; ?> • 
                                                    <?php echo date('M j, Y', strtotime($article['published_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="update_mapping" value="1">
                                                    <input type="hidden" name="mapping_id" value="<?php echo $article['mapping_id']; ?>">
                                                    <input type="number" name="relevance_score" class="form-control form-control-sm" 
                                                           value="<?php echo $article['relevance_score']; ?>" 
                                                           min="0" max="100" style="width: 70px;" 
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="update_mapping" value="1">
                                                    <input type="hidden" name="mapping_id" value="<?php echo $article['mapping_id']; ?>">
                                                    <input type="hidden" name="relevance_score" value="<?php echo $article['relevance_score']; ?>">
                                                    <input type="checkbox" name="is_key_article" 
                                                           <?php echo $article['is_key_article'] ? 'checked' : ''; ?>
                                                           onchange="this.form.submit()">
                                                </form>
                                            </td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>/article.php?slug=<?php echo $article['slug']; ?>" 
                                                   class="btn btn-sm btn-info" target="_blank" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?case_id=<?php echo $case_id; ?>&remove=<?php echo $article['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Remove this article from case?');"
                                                   title="Remove">
                                                    <i class="fas fa-unlink"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <h6>About Relevance & Key Articles</h6>
                    <ul class="mb-0">
                        <li><strong>Relevance Score:</strong> 0-100 scale indicating how relevant the article is to the case. Higher scores appear first.</li>
                        <li><strong>Key Article:</strong> Mark important/landmark articles that will be highlighted with a special badge.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
