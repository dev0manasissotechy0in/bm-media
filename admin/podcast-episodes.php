<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

$podcast_id = (int)($_GET['podcast_id'] ?? 0);
$podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = ?", [$podcast_id]);

if (!$podcast || !$podcast['is_series']) {
    header('Location: podcasts.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'delete' && $id > 0) {
        $db->delete('podcast_episodes', 'id = ?', [$id]);
        // Update episode count
        $count = $db->fetchOne("SELECT COUNT(*) as count FROM podcast_episodes WHERE podcast_id = ?", [$podcast_id])['count'];
        $db->update('podcasts', ['total_episodes' => $count], 'id = ?', [$podcast_id]);
        $success = 'Episode deleted successfully';
    }
    
    elseif ($action === 'toggle_status' && $id > 0) {
        $episode = $db->fetchOne("SELECT status FROM podcast_episodes WHERE id = ?", [$id]);
        $new_status = $episode['status'] === 'published' ? 'draft' : 'published';
        $db->update('podcast_episodes', ['status' => $new_status], 'id = ?', [$id]);
        $success = 'Status updated successfully';
    }
}

// Get episodes
$episodes = $db->fetchAll("
    SELECT * FROM podcast_episodes 
    WHERE podcast_id = ? 
    ORDER BY season_number DESC, episode_number DESC
", [$podcast_id]);

$page_title = 'Manage Episodes - ' . $podcast['title'];
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Manage Episodes</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($podcast['title']) ?></p>
        </div>
        <div>
            <a href="podcast-episode-add.php?podcast_id=<?= $podcast_id ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Episode
            </a>
            <a href="podcast-edit.php?id=<?= $podcast_id ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Podcast
            </a>
        </div>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Title</th>
                            <th>Season</th>
                            <th>Duration</th>
                            <th>Stats</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($episodes)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="bi bi-list-ol fs-1 text-muted"></i>
                                <p class="mt-2">No episodes yet</p>
                                <a href="podcast-episode-add.php?podcast_id=<?= $podcast_id ?>" class="btn btn-primary">
                                    Add First Episode
                                </a>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($episodes as $episode): ?>
                        <tr>
                            <td><strong><?= $episode['episode_number'] ?></strong></td>
                            <td>
                                <strong><?= htmlspecialchars($episode['title']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars(truncateText($episode['description'], 80)) ?></small>
                            </td>
                            <td>S<?= $episode['season_number'] ?></td>
                            <td><?= gmdate('H:i:s', $episode['duration']) ?></td>
                            <td>
                                <small>
                                    <i class="bi bi-eye"></i> <?= formatNumber($episode['views_count']) ?><br>
                                    <i class="bi bi-heart"></i> <?= formatNumber($episode['likes_count']) ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $episode['status'] === 'published' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($episode['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($episode['published_at']): ?>
                                <small><?= date('M d, Y', strtotime($episode['published_at'])) ?></small>
                                <?php else: ?>
                                <small class="text-muted">Not published</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="podcast-episode-edit.php?id=<?= $episode['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Toggle status?')">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $episode['id'] ?>">
                                        <button type="submit" class="btn btn-outline-warning" title="Toggle Status">
                                            <i class="bi bi-toggle-on"></i>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this episode?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $episode['id'] ?>">
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
