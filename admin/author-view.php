<?php
/**
 * View Author Details
 */

require_once 'auth_check.php';

$page_title = 'Author Details';
$db = Database::getInstance();

$author_id = (int)($_GET['id'] ?? 0);
$author = $db->fetchOne("SELECT * FROM authors WHERE id = ?", [$author_id]);

if (!$author) {
    Session::setFlash('error', 'Author not found.', 'danger');
    redirect('authors.php');
}

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
    LIMIT 10",
    [$author_id]
);

$permissions = json_decode($author['permissions'] ?? '[]', true) ?: [];

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person-badge-fill"></i> Author Details
            </h1>
            <div class="btn-group">
                <a href="author-edit.php?id=<?= $author_id ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                <a href="authors.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <?php if (!empty($author['profile_photo'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($author['profile_photo']) ?>" 
                             alt="<?= htmlspecialchars($author['full_name']) ?>" 
                             class="rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                        <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-3" 
                             style="width: 150px; height: 150px;">
                            <i class="bi bi-person text-white" style="font-size: 4rem;"></i>
                        </div>
                        <?php endif; ?>
                        
                        <h4><?= htmlspecialchars($author['full_name']) ?></h4>
                        <p class="text-muted"><?= htmlspecialchars($author['email']) ?></p>
                        
                        <?php if ($author['status'] === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                        <?php elseif ($author['status'] === 'suspended'): ?>
                        <span class="badge bg-danger">Suspended</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Contact Information</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Email:</strong><br><?= htmlspecialchars($author['email']) ?></p>
                        <?php if ($author['phone']): ?>
                        <p><strong>Phone:</strong><br><?= htmlspecialchars($author['phone']) ?></p>
                        <?php endif; ?>
                        <p><strong>Joined:</strong><br><?= date('F d, Y', strtotime($author['created_at'])) ?></p>
                        <p class="mb-0"><strong>Last Login:</strong><br>
                            <?= $author['last_login'] ? date('M d, Y H:i', strtotime($author['last_login'])) : 'Never' ?>
                        </p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Permissions</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($permissions)): ?>
                        <p class="text-muted mb-0">No permissions assigned</p>
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
            
            <div class="col-md-8">
                <?php if ($author['bio']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Bio</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($author['bio'])) ?></p>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h3><?= $stats['total'] ?></h3>
                                <p class="mb-0">Total Articles</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body">
                                <h3><?= $stats['pending'] ?></h3>
                                <p class="mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h3><?= $stats['approved'] ?></h3>
                                <p class="mb-0">Approved</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h3><?= $stats['declined'] ?></h3>
                                <p class="mb-0">Declined</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-info">
                            <div class="card-body">
                                <h5 class="text-info">
                                    <i class="bi bi-eye-fill"></i> <?= number_format($stats['total_views']) ?>
                                </h5>
                                <p class="mb-0 text-muted">Total Views</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h5 class="text-danger">
                                    <i class="bi bi-heart-fill"></i> <?= number_format($stats['total_likes']) ?>
                                </h5>
                                <p class="mb-0 text-muted">Total Likes</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Articles -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Recent Articles</h6>
                        <a href="articles.php?author_id=<?= $author_id ?>" class="btn btn-sm btn-primary">
                            View All
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_articles)): ?>
                        <p class="text-muted text-center mb-0">No articles yet</p>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_articles as $article): ?>
                                    <tr>
                                        <td>
                                            <a href="article-edit.php?id=<?= $article['id'] ?>">
                                                <?= htmlspecialchars($article['title']) ?>
                                            </a>
                                        </td>
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
                                        <td><?= number_format($article['views']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($article['created_at'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
