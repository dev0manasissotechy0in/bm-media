<?php
/**
 * Article Approval System
 * Review pending articles from authors
 */

require_once 'auth_check.php';

$page_title = 'Article Approvals';
$db = Database::getInstance();

$success = '';
$error = '';

// Handle approval/decline
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article_id = (int)$_POST['article_id'];
    $action = $_POST['action'];
    $decline_reason = $_POST['decline_reason'] ?? '';
    
    $admin_id = Session::get('admin_id');
    
    if ($action === 'approve') {
        $updated = $db->update('articles', [
            'approval_status' => 'approved',
            'status' => 'published',
            'reviewed_by' => $admin_id,
            'reviewed_at' => date('Y-m-d H:i:s')
        ], 'id = ?', [$article_id]);
        
        if ($updated) {
            $success = 'Article approved and published successfully!';
        } else {
            $error = 'Failed to approve article.';
        }
    } elseif ($action === 'decline') {
        if (empty($decline_reason)) {
            $error = 'Please provide a reason for declining.';
        } else {
            $updated = $db->update('articles', [
                'approval_status' => 'declined',
                'status' => 'draft',
                'decline_reason' => $decline_reason,
                'reviewed_by' => $admin_id,
                'reviewed_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$article_id]);
            
            if ($updated) {
                $success = 'Article declined.';
            } else {
                $error = 'Failed to decline article.';
            }
        }
    }
}

// Get pending articles
$filter = $_GET['filter'] ?? 'pending';

$where = "1=1";  // Show all articles including Super Admin articles

if ($filter === 'pending') {
    $where .= " AND approval_status = 'pending'";
} elseif ($filter === 'approved') {
    $where .= " AND approval_status = 'approved'";
} elseif ($filter === 'declined') {
    $where .= " AND approval_status = 'declined'";
}

$articles = $db->fetchAll(
    "SELECT a.*, 
    au.full_name as author_name, 
    au.email as author_email,
    au.profile_photo as author_photo,
    c.name as category_name,
    admin.username as reviewer_name
    FROM articles a
    LEFT JOIN authors au ON a.author_id = au.id
    LEFT JOIN categories c ON a.category_id = c.id
    LEFT JOIN admin_users admin ON a.reviewed_by = admin.id
    WHERE {$where}
    ORDER BY a.created_at DESC"
);

$pending_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE approval_status = 'pending'")['count'];
$approved_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE approval_status = 'approved'")['count'];
$declined_count = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE approval_status = 'declined'")['count'];

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-clipboard-check-fill"></i> Article Approval System
            </h1>
        </div>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        
        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="?filter=pending">
                    <i class="bi bi-clock-history"></i> Pending 
                    <span class="badge bg-warning text-dark"><?= $pending_count ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'approved' ? 'active' : '' ?>" href="?filter=approved">
                    <i class="bi bi-check-circle"></i> Approved 
                    <span class="badge bg-success"><?= $approved_count ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $filter === 'declined' ? 'active' : '' ?>" href="?filter=declined">
                    <i class="bi bi-x-circle"></i> Declined 
                    <span class="badge bg-danger"><?= $declined_count ?></span>
                </a>
            </li>
        </ul>
        
        <!-- Articles List -->
        <?php if (empty($articles)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3 mb-0">No articles to review</p>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($articles as $article): ?>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2">
                        <?php if (!empty($article['thumbnail'])): ?>
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($article['thumbnail']) ?>" 
                             alt="<?= htmlspecialchars($article['title']) ?>" 
                             class="img-fluid rounded">
                        <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                             style="height: 120px;">
                            <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <h5 class="mb-2">
                            <a href="<?= BASE_URL ?>/article.php?slug=<?= $article['slug'] ?>" 
                               target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars($article['title']) ?>
                            </a>
                        </h5>
                        
                        <p class="text-muted mb-2">
                            <?= substr(strip_tags($article['content']), 0, 200) ?>...
                        </p>
                        
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($article['author_photo'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($article['author_photo']) ?>" 
                                     alt="<?= htmlspecialchars($article['author_name']) ?>" 
                                     class="rounded-circle me-2" 
                                     style="width: 30px; height: 30px; object-fit: cover;">
                                <?php else: ?>
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" 
                                     style="width: 30px; height: 30px;">
                                    <i class="bi bi-person text-white" style="font-size: 14px;"></i>
                                </div>
                                <?php endif; ?>
                                <small><strong><?= htmlspecialchars($article['author_name']) ?></strong></small>
                            </div>
                            
                            <small class="text-muted">
                                <i class="bi bi-folder"></i> <?= htmlspecialchars($article['category_name']) ?>
                            </small>
                            
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> <?= date('M d, Y H:i', strtotime($article['created_at'])) ?>
                            </small>
                            
                            <?php if ($article['approval_status'] === 'approved'): ?>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle"></i> Approved
                            </span>
                            <?php elseif ($article['approval_status'] === 'declined'): ?>
                            <span class="badge bg-danger">
                                <i class="bi bi-x-circle"></i> Declined
                            </span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">
                                <i class="bi bi-clock-history"></i> Pending
                            </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($article['decline_reason']) && $article['approval_status'] === 'declined'): ?>
                        <div class="alert alert-danger mt-3 mb-0">
                            <strong>Decline Reason:</strong> <?= htmlspecialchars($article['decline_reason']) ?>
                            <?php if ($article['reviewer_name']): ?>
                            <br><small>Reviewed by: <?= htmlspecialchars($article['reviewer_name']) ?> on <?= date('M d, Y H:i', strtotime($article['reviewed_at'])) ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3">
                        <?php if ($article['approval_status'] === 'pending'): ?>
                        <form method="POST" class="mb-2">
                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-check-circle"></i> Approve & Publish
                            </button>
                        </form>
                        
                        <button type="button" class="btn btn-danger w-100 mb-2" 
                                data-bs-toggle="modal" 
                                data-bs-target="#declineModal<?= $article['id'] ?>">
                            <i class="bi bi-x-circle"></i> Decline
                        </button>
                        
                        <a href="article-edit.php?id=<?= $article['id'] ?>" 
                           class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-pencil"></i> Edit Before Approve
                        </a>
                        
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= $article['slug'] ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye"></i> Preview
                        </a>
                        
                        <!-- Decline Modal -->
                        <div class="modal fade" id="declineModal<?= $article['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Decline Article</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="article_id" value="<?= $article['id'] ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <div class="mb-3">
                                                <label class="form-label">Reason for Declining *</label>
                                                <textarea name="decline_reason" class="form-control" rows="4" 
                                                          placeholder="Please provide feedback to the author..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">
                                                <i class="bi bi-x-circle"></i> Decline Article
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <a href="article-edit.php?id=<?= $article['id'] ?>" 
                           class="btn btn-warning w-100 mb-2">
                            <i class="bi bi-pencil"></i> Edit Article
                        </a>
                        
                        <a href="<?= BASE_URL ?>/article.php?slug=<?= $article['slug'] ?>" 
                           target="_blank" 
                           class="btn btn-outline-primary w-100">
                            <i class="bi bi-eye"></i> View Article
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
