<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);
$episode = $db->fetchOne("SELECT * FROM podcast_episodes WHERE id = ?", [$id]);

if (!$episode) {
    header('Location: podcasts.php');
    exit;
}

$podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = ?", [$episode['podcast_id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $audio_url = trim($_POST['audio_url']);
    $duration = (int)$_POST['duration'];
    $episode_number = (int)$_POST['episode_number'];
    $season_number = (int)$_POST['season_number'];
    $status = $_POST['status'];
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
    
    $data = [
        'title' => $title,
        'slug' => $slug,
        'description' => $description,
        'audio_url' => $audio_url,
        'duration' => $duration,
        'episode_number' => $episode_number,
        'season_number' => $season_number,
        'status' => $status,
        'published_at' => $published_at
    ];
    
    if (!empty($_FILES['thumbnail']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['thumbnail'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
        if ($result['success']) {
            $data['thumbnail'] = $result['filename'];
        }
    }
    
    try {
        $db->update('podcast_episodes', $data, 'id = ?', [$id]);
        $success = 'Episode updated successfully';
        $episode = $db->fetchOne("SELECT * FROM podcast_episodes WHERE id = ?", [$id]);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Edit Episode';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Episode</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($podcast['title']) ?></p>
        </div>
        <a href="podcast-episodes.php?podcast_id=<?= $episode['podcast_id'] ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Episodes
        </a>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Episode Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Episode Title *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($episode['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($episode['slug']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($episode['description']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Audio URL *</label>
                            <input type="url" name="audio_url" class="form-control" value="<?= htmlspecialchars($episode['audio_url']) ?>" required>
                            <small class="text-muted">Direct link to the audio file</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Season Number *</label>
                                <input type="number" name="season_number" class="form-control" value="<?= $episode['season_number'] ?>" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Episode Number *</label>
                                <input type="number" name="episode_number" class="form-control" value="<?= $episode['episode_number'] ?>" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration (seconds) *</label>
                                <input type="number" name="duration" class="form-control" value="<?= $episode['duration'] ?>" min="1" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Publishing</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="draft" <?= $episode['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $episode['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Published Date</label>
                            <input type="datetime-local" name="published_at" class="form-control" 
                                   value="<?= $episode['published_at'] ? date('Y-m-d\TH:i', strtotime($episode['published_at'])) : '' ?>">
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Current Thumbnail</label><br>
                            <?php if ($episode['thumbnail']): ?>
                            <img src="<?= UPLOADS_URL ?>/podcasts/<?= $episode['thumbnail'] ?>" class="img-thumbnail mb-2" style="max-width: 150px;">
                            <?php else: ?>
                            <p class="text-muted">Using podcast thumbnail</p>
                            <?php endif; ?>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-save"></i> Update Episode
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <i class="bi bi-eye text-primary"></i> Views: <strong><?= formatNumber($episode['views_count']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-heart text-danger"></i> Likes: <strong><?= formatNumber($episode['likes_count']) ?></strong>
                        </div>
                        <hr>
                        <small class="text-muted">
                            Created: <?= date('M d, Y', strtotime($episode['created_at'])) ?><br>
                            Updated: <?= date('M d, Y', strtotime($episode['updated_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
