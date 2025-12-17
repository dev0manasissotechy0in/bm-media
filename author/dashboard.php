<?php
/**
 * Author Dashboard
 */

require_once 'auth_check.php';

$page_title = 'Dashboard';
$db = Database::getInstance();

// Get article statistics
$stats = $db->fetchOne(
    "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN approval_status = 'declined' THEN 1 ELSE 0 END) as declined,
    SUM(views_count) as total_views,
    SUM(likes_count) as total_likes
    FROM articles WHERE author_id = ?",
    [$author_id]
);

// Get recent articles
$recent_articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name 
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.author_id = ?
    ORDER BY a.created_at DESC
    LIMIT 5",
    [$author_id]
);

// Get declined articles with feedback
$declined_articles = $db->fetchAll(
    "SELECT a.*, c.name as category_name, au.full_name as reviewer_name
    FROM articles a
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN authors au ON a.reviewed_by = au.id
    WHERE a.author_id = ? AND a.approval_status = 'declined'
    ORDER BY a.reviewed_at DESC
    LIMIT 3",
    [$author_id]
);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </h1>
                <p class="text-muted mb-0">Welcome back, <?= htmlspecialchars($author['full_name']) ?>!</p>
            </div>
            <div class="d-flex gap-2">
                <?php if ($author_id == 1): ?>
                <a href="switch-to-admin.php" class="btn btn-danger">
                    <i class="bi bi-shield-lock"></i> Switch to Admin
                </a>
                <?php endif; ?>
                <?php if (hasPermission('create_article')): ?>
                <a href="article-add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Article
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?= $stats['total'] ?? 0 ?></h3>
                                <p class="mb-0">Total Articles</p>
                            </div>
                            <i class="bi bi-file-text" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?= $stats['pending'] ?? 0 ?></h3>
                                <p class="mb-0">Pending Review</p>
                            </div>
                            <i class="bi bi-clock-history" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?= $stats['approved'] ?? 0 ?></h3>
                                <p class="mb-0">Approved</p>
                            </div>
                            <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-0"><?= $stats['declined'] ?? 0 ?></h3>
                                <p class="mb-0">Declined</p>
                            </div>
                            <i class="bi bi-x-circle" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Engagement Stats -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-body">
                        <h5 class="text-info">
                            <i class="bi bi-eye-fill"></i> <?= number_format($stats['total_views'] ?? 0) ?>
                        </h5>
                        <p class="mb-0 text-muted">Total Views Across All Articles</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-body">
                        <h5 class="text-danger">
                            <i class="bi bi-heart-fill"></i> <?= number_format($stats['total_likes'] ?? 0) ?>
                        </h5>
                        <p class="mb-0 text-muted">Total Likes Received</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Recent Articles -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Articles</h5>
                        <a href="articles.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_articles)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-file-text text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2">No articles yet. Create your first article!</p>
                            <?php if (hasPermission('create_article')): ?>
                            <a href="article-add.php" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Create Article
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_articles as $article): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($article['title']) ?></td>
                                        <td><?= htmlspecialchars($article['category_name']) ?></td>
                                        <td>
                                            <?php if ($article['approval_status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                            <?php elseif ($article['approval_status'] === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Declined</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= number_format($article['views_count'] ?? 0) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($article['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/article.php?id=<?= $article['id'] ?>" 
                                               class="btn btn-sm btn-info" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if (hasPermission('edit_own_article')): ?>
                                            <a href="article-edit.php?id=<?= $article['id'] ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Declined Articles with Feedback -->
                <?php if (!empty($declined_articles)): ?>
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Articles Needing Attention</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($declined_articles as $article): ?>
                        <div class="alert alert-danger">
                            <h6 class="alert-heading">
                                <i class="bi bi-file-text"></i> <?= htmlspecialchars($article['title']) ?>
                            </h6>
                            <p class="mb-2"><strong>Decline Reason:</strong></p>
                            <p class="mb-2"><?= nl2br(htmlspecialchars($article['decline_reason'])) ?></p>
                            <hr>
                            <small class="text-muted">
                                Reviewed by <?= htmlspecialchars($article['reviewer_name']) ?> on 
                                <?= date('M d, Y H:i', strtotime($article['reviewed_at'])) ?>
                            </small>
                            <div class="mt-2">
                                <?php if (hasPermission('edit_own_article')): ?>
                                <a href="article-edit.php?id=<?= $article['id'] ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Edit & Resubmit
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <!-- Profile Card -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <?php if (!empty($author['profile_photo'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($author['profile_photo']) ?>" 
                             alt="<?= htmlspecialchars($author['full_name']) ?>" 
                             class="rounded-circle mb-3" 
                             style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 120px; height: 120px;">
                            <i class="bi bi-person text-white" style="font-size: 3rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h5><?= htmlspecialchars($author['full_name']) ?></h5>
                        <p class="text-muted mb-2"><?= htmlspecialchars($author['email']) ?></p>
                        
                        <a href="profile.php" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-person-gear"></i> Edit Profile
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Quick Links</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php if (hasPermission('create_article')): ?>
                        <a href="article-add.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-plus-circle text-primary"></i> Create New Article
                        </a>
                        <?php endif; ?>
                        <a href="articles.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-file-text text-info"></i> My Articles
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-gear text-success"></i> Profile Settings
                        </a>
                    </div>
                </div>
                
                <!-- Permissions -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Your Permissions</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $permissions = Session::get('author_permissions', []);
                        if (empty($permissions)): 
                        ?>
                        <p class="text-muted mb-0">No permissions assigned yet. Contact admin.</p>
                        <?php else: ?>
                        <?php foreach ($permissions as $permission): ?>
                        <div class="mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <?= ucwords(str_replace('_', ' ', $permission)) ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
