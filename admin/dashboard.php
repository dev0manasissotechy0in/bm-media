<?php
/**
 * Admin Dashboard - Main Page
 */

require_once 'auth_check.php';

$page_title = 'Dashboard';

$db = Database::getInstance();

// Fetch statistics
$stats = [
    'total_articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles")['count'],
    'total_users' => $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_reporters' => $db->fetchOne("SELECT COUNT(*) as count FROM reporters")['count'],
    'live_articles' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE is_live = 1")['count'],
    'published_today' => $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE DATE(published_at) = CURDATE()")['count'],
    'pending_comments' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE status = 'pending'")['count']
];

// Recent articles
$recent_articles = $db->fetchAll("
    SELECT a.*, c.name as category_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");

// Popular articles (by views)
$popular_articles = $db->fetchAll("
    SELECT a.*, c.name as category_name 
    FROM articles a 
    LEFT JOIN categories c ON a.category_id = c.id 
    WHERE a.status = 'published'
    ORDER BY a.views_count DESC 
    LIMIT 10
");

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 mb-0">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </h1>
                    <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($admin_name) ?>!</p>
                </div>
                <?php
                // Check if this admin has an author account
                $author_account = $db->fetchOne("SELECT id FROM authors WHERE email = ?", [$_SESSION['admin_email'] ?? '']);
                if ($author_account && $author_account['id'] == 1):
                ?>
                <a href="switch-to-author.php" class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square"></i> Switch to Author Mode
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Articles</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_articles']) ?></h3>
                        </div>
                        <div class="text-primary fs-1">
                            <i class="bi bi-newspaper"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                        </div>
                        <div class="text-success fs-1">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Live Articles</h6>
                            <h3 class="mb-0"><?= number_format($stats['live_articles']) ?></h3>
                        </div>
                        <div class="text-danger fs-1">
                            <i class="bi bi-broadcast"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Published Today</h6>
                            <h3 class="mb-0"><?= number_format($stats['published_today']) ?></h3>
                        </div>
                        <div class="text-info fs-1">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Articles -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Articles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_articles as $article): ?>
                                <tr>
                                    <td>
                                        <a href="article-edit.php?id=<?= $article['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars(substr($article['title'], 0, 40)) ?>...
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($article['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'published' => 'success',
                                            'draft' => 'warning',
                                            'scheduled' => 'info'
                                        ];
                                        $color = $status_colors[$article['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?>"><?= ucfirst($article['status']) ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= date('M j, Y', strtotime($article['created_at'])) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Articles -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-fire"></i> Popular Articles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Views</th>
                                    <th>Likes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popular_articles as $article): ?>
                                <tr>
                                    <td>
                                        <a href="article-edit.php?id=<?= $article['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars(substr($article['title'], 0, 40)) ?>...
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($article['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <i class="bi bi-eye"></i> <?= number_format($article['views_count']) ?>
                                    </td>
                                    <td>
                                        <i class="bi bi-heart-fill text-danger"></i> <?= number_format($article['likes_count']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row g-3 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="articles_add.php" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> New Article
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="categories.php" class="btn btn-success w-100">
                                <i class="bi bi-tags"></i> Manage Categories
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="election.php" class="btn btn-info w-100">
                                <i class="bi bi-ballot"></i> Election Data
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="cricket.php" class="btn btn-warning w-100">
                                <i class="bi bi-trophy"></i> Cricket Matches
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
