<?php
/**
 * Manage Live Updates for Live Articles
 */

require_once 'auth_check.php';

$page_title = 'Manage Live Updates';

$db = Database::getInstance();

// Get article ID from URL
$article_id = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;

if (!$article_id) {
    $_SESSION['error'] = 'Invalid article ID';
    header('Location: articles.php');
    exit;
}

// Get article details
$article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$article_id]);

if (!$article) {
    $_SESSION['error'] = 'Article not found';
    header('Location: articles.php');
    exit;
}

$errors = [];
$success = '';

// Handle add update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_update') {
    $update_text = trim($_POST['update_text'] ?? '');
    
    if (empty($update_text)) {
        $errors[] = 'Update text is required';
    }
    
    if (empty($errors)) {
        // Insert into article_live_updates table
        $inserted = $db->insert('article_live_updates', [
            'article_id' => $article_id,
            'update_text' => $update_text,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        // Mark article as live
        $db->update('articles', ['is_live' => 1], 'id = ?', [$article_id]);
        
        if ($inserted) {
            $_SESSION['success'] = 'Live update added successfully';
            header('Location: live-updates.php?article_id=' . $article_id);
            exit;
        } else {
            $errors[] = 'Failed to add live update';
        }
    }
}

// Handle delete update
if (isset($_GET['delete_update']) && !isset($_POST['action'])) {
    $update_id = (int)$_GET['delete_update'];
    
    // Delete from article_live_updates table
    $deleted = $db->delete('article_live_updates', 'id = ? AND article_id = ?', [$update_id, $article_id]);
    
    if ($deleted) {
        $_SESSION['success'] = 'Live update deleted successfully';
    }
    header('Location: live-updates.php?article_id=' . $article_id);
    exit;
}

// Refresh article data after any updates
if (isset($_SESSION['success']) || isset($_SESSION['error'])) {
    $article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$article_id]);
}

// Get all live updates for this article from article_live_updates table
$live_updates = $db->fetchAll("
    SELECT * FROM article_live_updates 
    WHERE article_id = ? 
    ORDER BY created_at DESC
", [$article_id]);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">
                    <i class="bi bi-broadcast text-danger"></i> Manage Live Updates
                </h1>
                <p class="text-muted mb-0">
                    Article: <strong><?= htmlspecialchars($article['title']) ?></strong>
                    <?php if ($article['is_live']): ?>
                        <span class="badge bg-danger ms-2">
                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> LIVE
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="btn btn-info me-2" target="_blank">
                    <i class="bi bi-eye"></i> View Article
                </a>
                <a href="article-edit.php?id=<?= $article_id ?>" class="btn btn-secondary me-2">
                    <i class="bi bi-pencil"></i> Edit Article
                </a>
                <a href="articles.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Add New Update Form -->
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Live Update</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_update">
                            
                            <div class="mb-3">
                                <label class="form-label">Update Text <span class="text-danger">*</span></label>
                                <textarea name="update_text" class="form-control" rows="6" 
                                          placeholder="Enter the live update text here..." required></textarea>
                                <small class="text-muted">This will appear in the timeline on the article page.</small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <small>
                                    <strong>Tips:</strong> Keep updates concise and informative. 
                                    Latest updates appear at the top of the timeline.
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-send"></i> Publish Update
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="bi bi-graph-up"></i> Article Stats</h6>
                        <div class="row text-center">
                            <div class="col-6 mb-2">
                                <div class="border rounded p-2">
                                    <h4 class="text-primary mb-0"><?= count($live_updates) ?></h4>
                                    <small class="text-muted">Updates</small>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="border rounded p-2">
                                    <h4 class="text-success mb-0"><?= number_format($article['views_count']) ?></h4>
                                    <small class="text-muted">Views</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Updates Timeline -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history"></i> Live Updates Timeline
                            </h5>
                            <span class="badge bg-secondary"><?= count($live_updates) ?> Total</span>
                        </div>
                    </div>
                    <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                        <?php if (empty($live_updates)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-3">No live updates yet. Add your first update to start the timeline!</p>
                        </div>
                        <?php else: ?>
                        <div class="timeline-admin">
                            <?php foreach ($live_updates as $index => $update): ?>
                            <div class="timeline-admin-item mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <?php if ($index === 0): ?>
                                                <span class="badge bg-danger me-2">LATEST</span>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i>
                                                    <?= formatDate($update['created_at'], 'd M, Y g:i A') ?>
                                                </small>
                                            </div>
                                            <a href="?article_id=<?= $article_id ?>&delete_update=<?= $update['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this update?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                        <p class="mb-0"><?= nl2br(htmlspecialchars($update['update_text'])) ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-event"></i> 
                                            <?= timeAgo($update['created_at']) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-admin-item {
    animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.timeline-admin-item .card {
    transition: all 0.3s;
    border-left: 3px solid #dee2e6;
}

.timeline-admin-item:first-child .card {
    border-left-color: #dc3545;
}

.timeline-admin-item .card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateX(5px);
}
</style>

<?php include 'includes/footer.php'; ?>
