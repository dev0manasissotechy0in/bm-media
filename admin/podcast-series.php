<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'delete' && $id > 0) {
        // Delete all episodes first
        $db->delete('podcast_episodes', 'podcast_id = ?', [$id]);
        // Delete podcast
        $db->delete('podcasts', 'id = ?', [$id]);
        $success = 'Series deleted successfully';
    }
    
    elseif ($action === 'toggle_status' && $id > 0) {
        $podcast = $db->fetchOne("SELECT status FROM podcasts WHERE id = ?", [$id]);
        $new_status = $podcast['status'] === 'published' ? 'draft' : 'published';
        $db->update('podcasts', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'Status updated successfully';
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search and filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';

// Build query
$where = ["is_series = 1"];
$params = [];

if ($search) {
    $where[] = "(title LIKE ? OR author_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if ($category_filter) {
    $where[] = "category_id = ?";
    $params[] = $category_filter;
}

$where_clause = implode(' AND ', $where);

// Get total count
$total = $db->fetchOne("SELECT COUNT(*) as count FROM podcasts WHERE $where_clause", $params)['count'];
$total_pages = ceil($total / $per_page);

// Get series
$params[] = $per_page;
$params[] = $offset;

$series_list = $db->fetchAll("
    SELECT 
        p.*,
        c.name as category_name,
        (SELECT COUNT(*) FROM podcast_episodes WHERE podcast_id = p.id) as episode_count,
        (SELECT COUNT(*) FROM podcast_episodes WHERE podcast_id = p.id AND status = 'published') as published_episodes,
        (SELECT COALESCE(SUM(views_count), 0) FROM podcast_episodes WHERE podcast_id = p.id) as total_views,
        (SELECT COALESCE(SUM(likes_count), 0) FROM podcast_episodes WHERE podcast_id = p.id) as total_likes,
        (SELECT COALESCE(SUM(duration), 0) FROM podcast_episodes WHERE podcast_id = p.id) as total_duration
    FROM podcasts p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_clause
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");

$page_title = 'Podcast Series Management';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-collection-play"></i> Podcast Series</h1>
            <p class="text-muted mb-0">Manage multi-episode podcast series</p>
        </div>
        <a href="podcast-add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Series
        </a>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search series..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="published" <?= $status_filter === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $status_filter === 'draft' ? 'selected' : '' ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-1">Total Series</h6>
                    <h2 class="mb-0"><?= $total ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-1">Published Series</h6>
                    <h2 class="mb-0">
                        <?= $db->fetchOne("SELECT COUNT(*) as count FROM podcasts WHERE is_series = 1 AND status = 'published'")['count'] ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-1">Total Episodes</h6>
                    <h2 class="mb-0">
                        <?= $db->fetchOne("SELECT COUNT(*) as count FROM podcast_episodes")['count'] ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="text-white-50 mb-1">Draft Series</h6>
                    <h2 class="mb-0">
                        <?= $db->fetchOne("SELECT COUNT(*) as count FROM podcasts WHERE is_series = 1 AND status = 'draft'")['count'] ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Series List -->
    <div class="row g-4">
        <?php if (empty($series_list)): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-collection-play fs-1 text-muted"></i>
                    <h4 class="mt-3">No Series Found</h4>
                    <p class="text-muted">Start by creating your first podcast series</p>
                    <a href="podcast-add.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Series
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <?php foreach ($series_list as $series): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <!-- Cover Image -->
                <div class="position-relative" style="height: 200px; overflow: hidden;">
                    <?php if ($series['cover_image']): ?>
                    <img src="<?= UPLOADS_URL ?>/podcasts/<?= htmlspecialchars($series['cover_image']) ?>" 
                         class="w-100 h-100" style="object-fit: cover;" alt="Cover">
                    <?php else: ?>
                    <div class="w-100 h-100 bg-secondary d-flex align-items-center justify-content-center">
                        <i class="bi bi-mic fs-1 text-white"></i>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Status Badge -->
                    <span class="position-absolute top-0 start-0 m-2 badge <?= $series['status'] === 'published' ? 'bg-success' : 'bg-warning' ?>">
                        <?= ucfirst($series['status']) ?>
                    </span>
                    
                    <!-- Episode Count Badge -->
                    <span class="position-absolute top-0 end-0 m-2 badge bg-primary">
                        <?= $series['episode_count'] ?> Episodes
                    </span>
                </div>

                <div class="card-body">
                    <h5 class="card-title">
                        <a href="podcast-episodes.php?podcast_id=<?= $series['id'] ?>" class="text-decoration-none text-dark">
                            <?= htmlspecialchars($series['title']) ?>
                        </a>
                    </h5>
                    
                    <?php if ($series['category_name']): ?>
                    <p class="text-muted small mb-2">
                        <i class="bi bi-folder"></i> <?= htmlspecialchars($series['category_name']) ?>
                    </p>
                    <?php endif; ?>
                    
                    <p class="card-text text-muted small" style="max-height: 60px; overflow: hidden;">
                        <?= htmlspecialchars(substr($series['description'], 0, 120)) ?>...
                    </p>

                    <!-- Duration -->
                    <?php if ($series['total_duration'] > 0): ?>
                    <div class="alert alert-info py-2 mb-2 small">
                        <i class="bi bi-clock"></i> <strong>Total Duration:</strong> 
                        <?php 
                        $hours = floor($series['total_duration'] / 3600);
                        $minutes = floor(($series['total_duration'] % 3600) / 60);
                        $seconds = $series['total_duration'] % 60;
                        ?>
                        <?php if ($hours > 0): ?><?= $hours ?>h <?php endif; ?>
                        <?php if ($minutes > 0): ?><?= $minutes ?>m <?php endif; ?>
                        <?= $seconds ?>s
                    </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div class="d-flex justify-content-between text-muted small mb-3">
                        <span title="Published Episodes">
                            <i class="bi bi-check-circle text-success"></i> <?= $series['published_episodes'] ?>
                        </span>
                        <span title="Total Views">
                            <i class="bi bi-eye"></i> <?= number_format($series['total_views'] ?? 0) ?>
                        </span>
                        <span title="Total Downloads">
                            <i class="bi bi-download"></i> <?= number_format($series['total_downloads'] ?? 0) ?>
                        </span>
                    </div>

                    <!-- Author & Date -->
                    <div class="text-muted small mb-3">
                        <i class="bi bi-person"></i> <?= htmlspecialchars($series['author_name']) ?>
                        <br>
                        <i class="bi bi-calendar"></i> <?= date('M d, Y', strtotime($series['created_at'])) ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <a href="podcast-episodes.php?podcast_id=<?= $series['id'] ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-list-ol"></i> Manage Episodes (<?= $series['episode_count'] ?>)
                        </a>
                        <div class="btn-group btn-group-sm">
                            <a href="podcast-edit.php?id=<?= $series['id'] ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="podcast-episode-add.php?podcast_id=<?= $series['id'] ?>" class="btn btn-outline-success">
                                <i class="bi bi-plus"></i> Add Episode
                            </a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="id" value="<?= $series['id'] ?>">
                                <button type="submit" class="btn btn-outline-info" 
                                        title="<?= $series['status'] === 'published' ? 'Unpublish' : 'Publish' ?>">
                                    <i class="bi bi-<?= $series['status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?= $series['id'] ?>, '<?= htmlspecialchars(addslashes($series['title'])) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
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
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?><?= $category_filter ? '&category=' . $category_filter : '' ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function confirmDelete(id, title) {
    if (confirm(`Are you sure you want to delete the series "${title}"?\n\nThis will also delete all episodes in this series.\n\nThis action cannot be undone.`)) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
