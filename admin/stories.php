<?php
/**
 * Stories Management
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
        $story = $db->fetchOne("SELECT status FROM stories WHERE id = ?", [$id]);
        $new_status = $story['status'] === 'active' ? 'inactive' : 'active';
        $db->update('stories', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'Story status updated';
    }
    
    elseif ($action === 'delete') {
        $db->query("DELETE FROM stories WHERE id = ?", [$id]);
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
    $where[] = "s.status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $where[] = "s.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where);

// Get stories
$stories = $db->fetchAll(
    "SELECT s.*, c.name as category_name 
     FROM stories s
     LEFT JOIN categories c ON s.category_id = c.id
     WHERE {$where_clause}
     ORDER BY s.created_at DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$per_page, $offset])
);

// Count total
$count_result = $db->fetchOne(
    "SELECT COUNT(*) as total FROM stories s WHERE {$where_clause}",
    $params
);
$total = $count_result['total'] ?? 0;

$total_pages = ceil($total / $per_page);

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT slug, name FROM categories WHERE status = 'active' ORDER BY name");

// Get statistics
$stats = [
    'total' => $db->fetchOne("SELECT COUNT(*) as val FROM stories")['val'] ?? 0,
    'active' => $db->fetchOne("SELECT COUNT(*) as val FROM stories WHERE status = 'active'")['val'] ?? 0,
    'inactive' => $db->fetchOne("SELECT COUNT(*) as val FROM stories WHERE status = 'inactive'")['val'] ?? 0,
    'total_views' => $db->fetchOne("SELECT SUM(views_count) as val FROM stories")['val'] ?? 0
];

$page_title = 'Manage Stories';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-film"></i> Manage Stories</h1>
            <a href="story-add.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Story
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
                        <a href="stories.php" class="btn btn-secondary w-100">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Stories Table -->
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
                                <th>Duration</th>
                                <th>Created</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stories)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox display-4 text-muted d-block mb-2"></i>
                                    <p class="text-muted">No stories found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($stories as $story): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($story['media_url'])): ?>
                                        <?php if ($story['media_type'] === 'video'): ?>
                                        <video src="<?= BASE_URL ?>/<?= htmlspecialchars($story['media_url']) ?>" 
                                               class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;"></video>
                                        <?php else: ?>
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($story['media_url']) ?>" 
                                             alt="Thumbnail" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                         style="width: 60px; height: 60px;">
                                        <i class="bi bi-film"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($story['title']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($story['category_name'] ?? $story['category']) ?></span>
                                </td>
                                <td>
                                    <?php if ($story['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format($story['views_count']) ?></td>
                                <td><?= $story['duration'] ?>s</td>
                                <td><?= date('M d, Y', strtotime($story['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="story-edit.php?id=<?= $story['id'] ?>" class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Toggle status?')">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="id" value="<?= $story['id'] ?>">
                                            <button type="submit" class="btn btn-info" title="Toggle Status">
                                                <i class="bi bi-toggle-on"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this story?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $story['id'] ?>">
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