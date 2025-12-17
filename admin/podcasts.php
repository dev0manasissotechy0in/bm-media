<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'delete' && $id > 0) {
        $db->delete('podcasts', 'id = ?', [$id]);
        $success = 'Podcast deleted successfully';
    }
    
    elseif ($action === 'toggle_status' && $id > 0) {
        $podcast = $db->fetchOne("SELECT status FROM podcasts WHERE id = ?", [$id]);
        $new_status = $podcast['status'] === 'published' ? 'draft' : 'published';
        $db->update('podcasts', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'Status updated successfully';
    }
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Build query
$where = [];
$params = [];

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}

if ($search) {
    $where[] = "(title LIKE ? OR author_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $where[] = "category = ?";
    $params[] = $category;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get podcasts
$podcasts = $db->fetchAll("
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM podcast_episodes WHERE podcast_id = p.id) as episode_count,
        CASE 
            WHEN p.is_series = 1 THEN (SELECT COALESCE(SUM(duration), 0) FROM podcast_episodes WHERE podcast_id = p.id AND status = 'published')
            ELSE p.duration
        END as total_duration
    FROM podcasts p
    $where_clause
    ORDER BY created_at DESC
", $params);

// Get categories
$categories = $db->fetchAll("SELECT DISTINCT category FROM podcasts WHERE category IS NOT NULL ORDER BY category");

$page_title = 'Podcast Management';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Podcast Management</h1>
            <small class="text-muted">Manage both single podcasts and podcast series with episodes</small>
        </div>
        <a href="podcast-add.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Podcast
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
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title or author...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Podcasts Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="80">Image</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Episodes</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($podcasts)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="bi bi-podcast fs-1 text-muted"></i>
                                <p class="mt-2">No podcasts found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($podcasts as $podcast): ?>
                        <tr>
                            <td>
                                <?php if ($podcast['thumbnail']): ?>
                                <img src="<?= UPLOADS_URL ?>/podcasts/<?= $podcast['thumbnail'] ?>" 
                                     class="img-thumbnail" 
                                     style="width: 60px; height: 60px; object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 60px; height: 60px;">
                                    <i class="bi bi-podcast"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($podcast['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($podcast['slug']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($podcast['author_name']) ?></td>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($podcast['category'] ?? 'N/A') ?></span>
                            </td>
                            <td>
                                <?php if ($podcast['is_series']): ?>
                                <span class="badge bg-info">Series</span>
                                <?php else: ?>
                                <span class="badge bg-success">Single</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($podcast['total_duration'] > 0): ?>
                                    <?php 
                                    $hours = floor($podcast['total_duration'] / 3600);
                                    $minutes = floor(($podcast['total_duration'] % 3600) / 60);
                                    $seconds = $podcast['total_duration'] % 60;
                                    ?>
                                    <small>
                                        <?php if ($hours > 0): ?><?= $hours ?>h <?php endif; ?>
                                        <?php if ($minutes > 0): ?><?= $minutes ?>m <?php endif; ?>
                                        <?= $seconds ?>s
                                    </small>
                                    <?php if ($podcast['is_series']): ?>
                                    <br><small class="text-muted">(Total)</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($podcast['is_series']): ?>
                                <a href="podcast-episodes.php?podcast_id=<?= $podcast['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <?= $podcast['episode_count'] ?> Episodes
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <i class="bi bi-eye"></i> <?= formatNumber($podcast['views_count']) ?><br>
                                    <i class="bi bi-heart"></i> <?= formatNumber($podcast['likes_count']) ?><br>
                                    <i class="bi bi-bookmark"></i> <?= formatNumber($podcast['saves_count']) ?>
                                </small>
                            </td>
                            <td>
                                <?php
                                $statusBadge = [
                                    'published' => 'success',
                                    'draft' => 'warning',
                                    'archived' => 'secondary'
                                ];
                                ?>
                                <span class="badge bg-<?= $statusBadge[$podcast['status']] ?>">
                                    <?= ucfirst($podcast['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="podcast-edit.php?id=<?= $podcast['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($podcast['is_series']): ?>
                                    <a href="podcast-episodes.php?podcast_id=<?= $podcast['id'] ?>" class="btn btn-outline-info" title="Manage Episodes">
                                        <i class="bi bi-list-ol"></i>
                                    </a>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status?')">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $podcast['id'] ?>">
                                        <button type="submit" class="btn btn-outline-warning" title="Toggle Status">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this podcast?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $podcast['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
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
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
