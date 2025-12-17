<?php
/**
 * Reels Management
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
        $reel = $db->fetchOne("SELECT status FROM reels WHERE id = ?", [$id]);
        $new_status = $reel['status'] === 'active' ? 'inactive' : 'active';
        $db->update('reels', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'Reel status updated';
    }
    
    elseif ($action === 'delete') {
        $db->query("DELETE FROM user_reel_likes WHERE reel_id = ?", [$id]);
        $db->query("DELETE FROM reels WHERE id = ?", [$id]);
        $success = 'Reel deleted successfully';
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
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $where[] = "r.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where);

// Get reels
$reels = $db->fetchAll(
    "SELECT r.*, c.name as category_name 
     FROM reels r
     LEFT JOIN categories c ON r.category_id = c.id
     WHERE {$where_clause}
     ORDER BY r.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$per_page, $offset])
);

// Count total
$count_result = $db->fetchOne(
    "SELECT COUNT(*) as total FROM reels r WHERE {$where_clause}",
    $params
);
$total = $count_result['total'] ?? 0;

$total_pages = ceil($total / $per_page);

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT slug, name FROM categories WHERE status = 'active' ORDER BY name");

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as val FROM reels")['val'] ?? 0,
    'active' => $db->fetchOne("SELECT COUNT(*) as val FROM reels WHERE status = 'active'")['val'] ?? 0,
    'inactive' => $db->fetchOne("SELECT COUNT(*) as val FROM reels WHERE status = 'inactive'")['val'] ?? 0,
    'total_views' => $db->fetchOne("SELECT SUM(views_count) as val FROM reels")['val'] ?? 0,
    'total_likes' => $db->fetchOne("SELECT SUM(likes_count) as val FROM reels")['val'] ?? 0
];

$page_title = 'Manage Reels';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-camera-reels"></i> Manage Reels</h1>
            <a href="reel-add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Reel
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
            <div class="col-md-2">
                <div class="card border-primary">
                    <div class="card-body">
                        <h6 class="text-muted">Total Reels</h6>
                        <h2 class="mb-0"><?= number_format($stats['total']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-success">
                    <div class="card-body">
                        <h6 class="text-muted">Active</h6>
                        <h2 class="mb-0"><?= number_format($stats['active']) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-secondary">
                    <div class="card-body">
                        <h6 class="text-muted">Inactive</h6>
                        <h2 class="mb-0"><?= number_format($stats['inactive']) ?></h2>
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
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="text-muted">Total Likes</h6>
                        <h2 class="mb-0"><?= number_format($stats['total_likes']) ?></h2>
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
                            <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['slug'] ?>" <?= $category_filter === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="reels.php" class="btn btn-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reels Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Thumbnail</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Likes</th>
                                <th>Source</th>
                                <th>Created</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reels)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted d-block mb-2"></i>
                                    <p class="text-muted">No reels found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($reels as $reel): ?>
                            <tr>
                                <td>
                                    <?php if ($reel['thumbnail']): ?>
                                    <img src="<?= BASE_URL ?>/<?= $reel['thumbnail'] ?>" 
                                         alt="Thumbnail" class="img-thumbnail" style="width: 60px; height: 90px; object-fit: cover;">
                                    <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 90px;">
                                        <i class="bi bi-camera-reels"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($reel['title']) ?></strong>
                                    <?php if ($reel['description']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(substr($reel['description'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($reel['category_name'] ?? $reel['category']) ?></span>
                                </td>
                                <td>
                                    <?php if ($reel['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><i class="bi bi-eye"></i> <?= number_format($reel['views_count']) ?></td>
                                <td><i class="bi bi-heart-fill text-danger"></i> <?= number_format($reel['likes_count']) ?></td>
                                <td>
                                    <?php if (strpos($reel['video_url'], 'youtube') !== false): ?>
                                        <span class="badge bg-danger">YouTube</span>
                                    <?php elseif (strpos($reel['video_url'], 'vimeo') !== false): ?>
                                        <span class="badge bg-info">Vimeo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Direct</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($reel['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="reel-edit.php?id=<?= $reel['id'] ?>" class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Toggle status?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $reel['id'] ?>">
                                            <button type="submit" class="btn btn-info" title="Toggle Status">
                                                <i class="bi bi-toggle-on"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this reel?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $reel['id'] ?>">
                                            <button type="submit" class="btn btn-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
            
            <a href="dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>