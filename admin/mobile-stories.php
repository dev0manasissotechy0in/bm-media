<?php
/**
 * Mobile Stories Management - Single image per story
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$success = '';
$errors = [];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'toggle_status') {
        $story = $db->fetchOne("SELECT status FROM mobile_stories WHERE id = ?", [$id]);
        $new_status = $story['status'] === 'active' ? 'expired' : 'active';
        $db->update('mobile_stories', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'Story status updated';
    }
    
    elseif ($action === 'delete') {
        $story = $db->fetchOne("SELECT image_url FROM mobile_stories WHERE id = ?", [$id]);
        if ($story && file_exists('../' . $story['image_url'])) {
            unlink('../' . $story['image_url']);
        }
        $db->query("DELETE FROM mobile_stories WHERE id = ?", [$id]);
        $success = 'Story deleted successfully';
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';

$where = ["1=1"];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "m.status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $where[] = "m.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where);

// Auto-deactivate expired stories (older than 24 hours)
$db->query(
    "UPDATE mobile_stories 
     SET status = 'expired' 
     WHERE status = 'active' 
     AND (
         (expires_at IS NOT NULL AND expires_at < NOW())
         OR (expires_at IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))
     )"
);

// Get stories with time calculations
$stories = $db->fetchAll(
    "SELECT m.*, c.name as category_name,
     TIMESTAMPDIFF(SECOND, m.created_at, NOW()) as seconds_elapsed,
     CASE 
         WHEN m.expires_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, NOW(), m.expires_at)
         ELSE TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(m.created_at, INTERVAL 24 HOUR))
     END as seconds_remaining
     FROM mobile_stories m
     LEFT JOIN categories c ON m.category_id = c.id
     WHERE {$where_clause}
     ORDER BY m.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$per_page, $offset])
);

// Count total
$count_result = $db->fetchOne(
    "SELECT COUNT(*) as total FROM mobile_stories m WHERE {$where_clause}",
    $params
);
$total = $count_result['total'] ?? 0;

$total_pages = ceil($total / $per_page);

// Get categories for filter
$categories = $db->fetchAll("SELECT id, slug, name FROM categories WHERE status = 'active' ORDER BY name");

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as val FROM mobile_stories")['val'] ?? 0,
    'active' => $db->fetchOne("SELECT COUNT(*) as val FROM mobile_stories WHERE status = 'active'")['val'] ?? 0,
    'expired' => $db->fetchOne("SELECT COUNT(*) as val FROM mobile_stories WHERE status = 'expired'")['val'] ?? 0,
    'total_views' => $db->fetchOne("SELECT SUM(views_count) as val FROM mobile_stories")['val'] ?? 0
];

$page_title = 'Mobile Stories';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-phone"></i> Mobile Stories</h1>
                <small class="text-muted">Single image stories for mobile sharing</small>
            </div>
            <a href="mobile-story-add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Mobile Story
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button class="btn-close" data-bs-dismiss="alert"></button>
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Total Stories</h6>
                        <h2 class="mb-0"><?= number_format($stats['total']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Active</h6>
                        <h2 class="mb-0"><?= number_format($stats['active']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-secondary">
                    <div class="card-body">
                        <h6 class="text-muted">Expired</h6>
                        <h2 class="mb-0"><?= number_format($stats['expired']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body">
                        <h6 class="text-muted">Total Views</h6>
                        <h2 class="mb-0"><?= number_format($stats['total_views']) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all">All Status</option>
                            <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="expired" <?= $status_filter === 'expired' ? 'selected' : '' ?>>Expired</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="mobile-stories.php" class="btn btn-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stories Grid -->
        <div class="row g-4">
            <?php if (empty($stories)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                        <p class="text-muted">No mobile stories found</p>
                        <a href="mobile-story-add.php" class="btn btn-primary">
                            <i class="bi bi-plus-lg"></i> Add Your First Story
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($stories as $story): ?>
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($story['image_url']) ?>" 
                         class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?= htmlspecialchars($story['title']) ?>">
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($story['title']) ?></h6>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="bi bi-tag"></i> <?= htmlspecialchars($story['category_name'] ?? 'N/A') ?>
                            </small>
                        </p>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="bi bi-eye"></i> <?= number_format($story['views_count']) ?> views
                            </small>
                        </p>
                        <p class="card-text mb-1">
                            <small class="text-muted">
                                <i class="bi bi-clock-history"></i> 
                                <span data-created-at="<?= strtotime($story['created_at']) ?>">
                                <?php
                                    $elapsed = (int)$story['seconds_elapsed'];
                                    if ($elapsed < 60) {
                                        echo $elapsed . 's ago';
                                    } elseif ($elapsed < 3600) {
                                        echo floor($elapsed / 60) . 'm ago';
                                    } elseif ($elapsed < 86400) {
                                        echo floor($elapsed / 3600) . 'h ago';
                                    } else {
                                        echo floor($elapsed / 86400) . 'd ago';
                                    }
                                ?>
                                </span>
                            </small>
                        </p>
                        <p class="card-text mb-2">
                            <?php 
                                $remaining = (int)$story['seconds_remaining'];
                                $created_timestamp = strtotime($story['created_at']);
                                $expires_timestamp = $story['expires_at'] ? strtotime($story['expires_at']) : ($created_timestamp + 86400);
                                if ($remaining > 0 && $story['status'] === 'active'):
                            ?>
                            <small class="text-success" data-expires-at="<?= $expires_timestamp ?>">
                                <i class="bi bi-hourglass-split"></i> 
                                <?php
                                    if ($remaining < 60) {
                                        echo $remaining . 's left';
                                    } elseif ($remaining < 3600) {
                                        echo floor($remaining / 60) . 'm left';
                                    } else {
                                        echo floor($remaining / 3600) . 'h left';
                                    }
                                ?>
                            </small>
                            <?php else: ?>
                            <small class="text-danger">
                                <i class="bi bi-hourglass"></i> Expired
                            </small>
                            <?php endif; ?>
                        </p>
                        
                        <!-- 24-Hour Progress Bar -->
                        <?php if ($story['status'] === 'active' && $remaining > 0): ?>
                        <div class="progress mb-2" style="height: 6px;">
                            <?php 
                                $total_seconds = 86400; // 24 hours
                                $elapsed_seconds = (int)$story['seconds_elapsed'];
                                $percentage = min(100, ($elapsed_seconds / $total_seconds) * 100);
                                $bar_class = 'bg-success';
                                if ($percentage >= 75) $bar_class = 'bg-danger';
                                elseif ($percentage >= 50) $bar_class = 'bg-warning';
                            ?>
                            <div class="progress-bar <?= $bar_class ?>" 
                                 data-progress-bar 
                                 data-created-at="<?= $created_timestamp ?>"
                                 style="width: <?= $percentage ?>%"
                                 role="progressbar" 
                                 aria-valuenow="<?= $percentage ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($story['status'] === 'active'): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Expired</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="btn-group btn-group-sm w-100">
                            <a href="mobile-story-edit.php?id=<?= $story['id'] ?>" class="btn btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" class="d-inline flex-fill" onsubmit="return confirm('Toggle status?')">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $story['id'] ?>">
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="bi bi-toggle-on"></i>
                                </button>
                            </form>
                            <form method="post" class="d-inline flex-fill" onsubmit="return confirm('Delete this story?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $story['id'] ?>">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?><?= $status_filter !== 'all' ? '&status='.$status_filter : '' ?><?= $category_filter !== 'all' ? '&category='.$category_filter : '' ?>">Previous</a>
                </li>
                <?php endif; ?>

                <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= $status_filter !== 'all' ? '&status='.$status_filter : '' ?><?= $category_filter !== 'all' ? '&category='.$category_filter : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?><?= $status_filter !== 'all' ? '&status='.$status_filter : '' ?><?= $category_filter !== 'all' ? '&category='.$category_filter : '' ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto-refresh page every 5 minutes to update time displays and auto-deactivate expired stories
setTimeout(function() {
    location.reload();
}, 300000); // 5 minutes

// Update time displays dynamically every minute without full page reload
setInterval(function() {
    document.querySelectorAll('[data-created-at]').forEach(function(elem) {
        const createdAt = parseInt(elem.dataset.createdAt);
        const now = Math.floor(Date.now() / 1000);
        const elapsed = now - createdAt;
        
        if (elapsed < 60) {
            elem.textContent = elapsed + 's ago';
        } else if (elapsed < 3600) {
            elem.textContent = Math.floor(elapsed / 60) + 'm ago';
        } else if (elapsed < 86400) {
            elem.textContent = Math.floor(elapsed / 3600) + 'h ago';
        } else {
            elem.textContent = Math.floor(elapsed / 86400) + 'd ago';
        }
    });
    
    document.querySelectorAll('[data-expires-at]').forEach(function(elem) {
        const expiresAt = parseInt(elem.dataset.expiresAt);
        const now = Math.floor(Date.now() / 1000);
        const remaining = expiresAt - now;
        
        if (remaining <= 0) {
            elem.innerHTML = '<i class="bi bi-hourglass"></i> Expired';
            elem.className = 'text-danger';
            // Reload page to update status
            setTimeout(() => location.reload(), 2000);
        } else if (remaining < 60) {
            elem.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + remaining + 's left';
        } else if (remaining < 3600) {
            elem.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + Math.floor(remaining / 60) + 'm left';
        } else {
            elem.innerHTML = '<i class="bi bi-hourglass-split"></i> ' + Math.floor(remaining / 3600) + 'h left';
        }
        
        // Update progress bar if exists
        const progressBar = elem.closest('.card').querySelector('[data-progress-bar]');
        if (progressBar) {
            const createdAt = parseInt(progressBar.dataset.createdAt);
            const totalSeconds = 86400; // 24 hours
            const elapsedSeconds = now - createdAt;
            const percentage = Math.min(100, (elapsedSeconds / totalSeconds) * 100);
            progressBar.style.width = percentage + '%';
            
            if (percentage < 50) {
                progressBar.className = 'progress-bar bg-success';
            } else if (percentage < 75) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-danger';
            }
        }
    });
}, 60000); // Update every minute
</script>

<?php include 'includes/footer.php'; ?>
