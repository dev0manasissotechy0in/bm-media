<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

$id = (int)($_GET['id'] ?? 0);
$podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = ?", [$id]);

if (!$podcast) {
    header('Location: podcasts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    
    // Handle episode addition
    if ($action === 'add_episode') {
        $episode_title = trim($_POST['episode_title']);
        $episode_slug = generateSlug($episode_title);
        $episode_description = trim($_POST['episode_description'] ?? '');
        $season_number = (int)$_POST['season_number'];
        $episode_number = (int)$_POST['episode_number'];
        $episode_duration = (int)$_POST['episode_duration'];
        $episode_status = $_POST['episode_status'];
        $audio_source = $_POST['audio_source'] ?? 'file';
        
        $episode_audio_url = '';
        if ($audio_source === 'url') {
            $episode_audio_url = trim($_POST['episode_audio_url'] ?? '');
            
            // Validate URL format
            if (!empty($episode_audio_url)) {
                // Check if it's a valid URL
                if (!filter_var($episode_audio_url, FILTER_VALIDATE_URL)) {
                    $error = 'Invalid audio URL format.';
                }
                // Check if URL ends with audio file extension (basic validation)
                elseif (!preg_match('/\.(mp3|wav|ogg|m4a|aac)(\?.*)?$/i', $episode_audio_url)) {
                    $error = 'Audio URL must be a direct link to an audio file (.mp3, .wav, .ogg, .m4a, .aac)';
                }
            }
        } else {
            if (!empty($_FILES['episode_audio_file']['name'])) {
                $upload_path = UPLOADS_PATH . '/podcasts';
                $result = uploadFile($_FILES['episode_audio_file'], $upload_path, ['mp3', 'wav', 'ogg', 'm4a'], 50 * 1024 * 1024);
                if ($result['success']) {
                    $episode_audio_url = UPLOADS_URL . '/podcasts/' . $result['filename'];
                } else {
                    $error = 'Failed to upload audio file: ' . ($result['message'] ?? 'Unknown error');
                }
            }
        }
        
        // Validate audio URL
        if (!isset($error) && empty($episode_audio_url)) {
            $error = 'Audio file or URL is required for episode.';
        }
        
        $episode_thumbnail = '';
        if (!empty($_FILES['episode_thumbnail']['name'])) {
            $upload_path = UPLOADS_PATH . '/podcasts';
            $result = uploadFile($_FILES['episode_thumbnail'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
            if ($result['success']) {
                $episode_thumbnail = $result['filename'];
            }
        }
        
        // Only proceed if no error and audio URL exists
        if (!isset($error) && !empty($episode_audio_url)) {
        try {
            $episode_data = [
                'podcast_id' => $id,
                'title' => $episode_title,
                'slug' => $episode_slug,
                'description' => $episode_description,
                'audio_url' => $episode_audio_url,
                'duration' => $episode_duration,
                'episode_number' => $episode_number,
                'season_number' => $season_number,
                'status' => $episode_status
            ];
            
            if ($episode_thumbnail) $episode_data['thumbnail'] = $episode_thumbnail;
            
            $db->insert('podcast_episodes', $episode_data);
            
            // Update episode count
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM podcast_episodes WHERE podcast_id = ?", [$id])['count'];
            $db->update('podcasts', ['total_episodes' => $count], 'id = ?', [$id]);
            
            header('Location: podcast-edit.php?id=' . $id . '&success=episode_added');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        }
    }
    
    // Handle episode deletion
    elseif ($action === 'delete_episode') {
        $episode_id = (int)$_POST['episode_id'];
        try {
            $db->delete('podcast_episodes', 'id = ?', [$episode_id]);
            
            // Update episode count
            $count = $db->fetchOne("SELECT COUNT(*) as count FROM podcast_episodes WHERE podcast_id = ?", [$id])['count'];
            $db->update('podcasts', ['total_episodes' => $count], 'id = ?', [$id]);
            
            header('Location: podcast-edit.php?id=' . $id . '&success=episode_deleted');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
    
    // Handle podcast update
    else {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $description = trim($_POST['description']);
    $author_name = trim($_POST['author_name']);
    $author_bio = trim($_POST['author_bio'] ?? '');
    $category = trim($_POST['category']);
    $language = trim($_POST['language']);
    $is_series = (int)($_POST['is_series'] ?? 0);
    $status = $_POST['status'];
    $duration = (int)($_POST['duration'] ?? 0);
    
    $data = [
        'title' => $title,
        'slug' => $slug,
        'description' => $description,
        'author_name' => $author_name,
        'author_bio' => $author_bio,
        'category' => $category,
        'language' => $language,
        'is_series' => $is_series,
        'status' => $status
    ];
    
    if (!$is_series && $duration > 0) {
        $data['duration'] = $duration;
    }
    
    // Handle file uploads
    if (!empty($_FILES['thumbnail']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['thumbnail'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
        if ($result['success']) {
            $data['thumbnail'] = $result['filename'];
        }
    }
    
    if (!empty($_FILES['cover_image']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['cover_image'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
        if ($result['success']) {
            $data['cover_image'] = $result['filename'];
        }
    }
    
    // Handle audio (for single podcasts)
    if (!$is_series) {
        $audio_source = $_POST['audio_source'] ?? 'file';
        
        if ($audio_source === 'url') {
            $podcast_audio_url = trim($_POST['podcast_audio_url'] ?? '');
            if (!empty($podcast_audio_url)) {
                // Validate URL format
                if (!filter_var($podcast_audio_url, FILTER_VALIDATE_URL)) {
                    $error = 'Invalid audio URL format.';
                } 
                // Check if URL ends with audio file extension
                elseif (!preg_match('/\.(mp3|wav|ogg|m4a|aac)(\?.*)?$/i', $podcast_audio_url)) {
                    $error = 'Audio URL must be a direct link to an audio file (ending with .mp3, .wav, .ogg, .m4a, or .aac)';
                }
                else {
                    $data['audio_url'] = $podcast_audio_url;
                }
            }
        } elseif (!empty($_FILES['audio_file']['name'])) {
            $upload_path = UPLOADS_PATH . '/podcasts';
            $result = uploadFile($_FILES['audio_file'], $upload_path, ['mp3', 'wav', 'ogg', 'm4a'], 50 * 1024 * 1024);
            if ($result['success']) {
                $data['audio_url'] = UPLOADS_URL . '/podcasts/' . $result['filename'];
            }
        }
    }
    
    try {
        $db->update('podcasts', $data, 'id = ?', [$id]);
        $success = 'Podcast updated successfully';
        $podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = ?", [$id]);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    }
}

$page_title = 'Edit Podcast';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Edit Podcast: <?= htmlspecialchars($podcast['title']) ?></h1>
            <small class="text-muted">
                <?php if ($podcast['is_series']): ?>
                <span class="badge bg-info">Series with Episodes</span>
                <?php else: ?>
                <span class="badge bg-success">Single Podcast</span>
                <?php endif; ?>
            </small>
        </div>
        <a href="podcasts.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Podcasts
        </a>
    </div>

    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?php if ($_GET['success'] === 'episode_added'): ?>
            <i class="bi bi-check-circle"></i> Episode added successfully!
        <?php elseif ($_GET['success'] === 'episode_deleted'): ?>
            <i class="bi bi-check-circle"></i> Episode deleted successfully!
        <?php endif; ?>
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
                        <h5 class="mb-0">Podcast Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($podcast['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug *</label>
                            <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($podcast['slug']) ?>" required>
                            <small class="text-muted">URL-friendly version of title</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($podcast['description']) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author Name *</label>
                                <input type="text" name="author_name" class="form-control" value="<?= htmlspecialchars($podcast['author_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($podcast['category'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Author Bio (Optional)</label>
                            <textarea name="author_bio" class="form-control" rows="3"><?= htmlspecialchars($podcast['author_bio'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Podcast Type *</label>
                            <select name="is_series" class="form-select form-select-lg border-primary" required>
                                <option value="0" <?= !$podcast['is_series'] ? 'selected' : '' ?>>🎵 Single Podcast</option>
                                <option value="1" <?= $podcast['is_series'] ? 'selected' : '' ?>>📚 Series (Multiple Episodes)</option>
                            </select>
                            <?php if ($podcast['is_series']): ?>
                            <div class="alert alert-info mt-2 mb-0 small">
                                <i class="bi bi-info-circle"></i> This is a series. Episodes are managed separately below.
                            </div>
                            <?php else: ?>
                            <div class="alert alert-success mt-2 mb-0 small">
                                <i class="bi bi-check-circle"></i> This is a single podcast with one audio file.
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Language *</label>
                            <select name="language" class="form-select" required>
                                <option value="English" <?= $podcast['language'] === 'English' ? 'selected' : '' ?>>English</option>
                                <option value="Hindi" <?= $podcast['language'] === 'Hindi' ? 'selected' : '' ?>>Hindi</option>
                                <option value="Spanish" <?= $podcast['language'] === 'Spanish' ? 'selected' : '' ?>>Spanish</option>
                                <option value="French" <?= $podcast['language'] === 'French' ? 'selected' : '' ?>>French</option>
                                <option value="German" <?= $podcast['language'] === 'German' ? 'selected' : '' ?>>German</option>
                                <option value="Other" <?= $podcast['language'] === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="draft" <?= $podcast['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $podcast['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $podcast['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Current Thumbnail</label><br>
                            <?php if ($podcast['thumbnail']): ?>
                            <img src="<?= UPLOADS_URL ?>/podcasts/<?= $podcast['thumbnail'] ?>" class="img-thumbnail mb-2" style="max-width: 200px;">
                            <?php endif; ?>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Cover Image</label><br>
                            <?php if ($podcast['cover_image']): ?>
                            <img src="<?= UPLOADS_URL ?>/podcasts/<?= $podcast['cover_image'] ?>" class="img-thumbnail mb-2" style="max-width: 200px;">
                            <?php endif; ?>
                            <input type="file" name="cover_image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current</small>
                        </div>

                        <hr>

                        <div id="audioUploadSection" style="<?= $podcast['is_series'] ? 'display: none;' : '' ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Current Audio</label><br>
                                <?php if (!empty($podcast['audio_url'])): ?>
                                <div class="alert alert-info mb-2">
                                    <small><strong>Current:</strong> <?= htmlspecialchars($podcast['audio_url']) ?></small>
                                </div>
                                <audio controls class="w-100 mb-2">
                                    <source src="<?= $podcast['audio_url'] ?>" type="audio/mpeg">
                                </audio>
                                <?php else: ?>
                                <div class="alert alert-warning mb-2">
                                    <i class="bi bi-exclamation-triangle"></i> No audio file uploaded yet
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Update Audio</label>
                                <div class="btn-group w-100 mb-2" role="group">
                                    <input type="radio" class="btn-check" name="audio_source" id="audioSourceFile" value="file" checked>
                                    <label class="btn btn-outline-primary" for="audioSourceFile">
                                        <i class="bi bi-upload"></i> Upload File
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="audio_source" id="audioSourceUrl" value="url">
                                    <label class="btn btn-outline-primary" for="audioSourceUrl">
                                        <i class="bi bi-link-45deg"></i> Use URL
                                    </label>
                                </div>

                                <div id="audioFileInput">
                                    <input type="file" name="audio_file" class="form-control" accept="audio/*" id="podcastAudioFile">
                                    <small class="text-muted">Upload MP3, WAV, M4A (max 50MB)</small>
                                </div>

                                <div id="audioUrlInput" style="display: none;">
                                    <input type="url" name="podcast_audio_url" class="form-control" placeholder="https://example.com/audio.mp3" id="podcastAudioUrl" pattern="https?://.+\.(mp3|wav|ogg|m4a|aac)(\?.*)?$">
                                    <small class="text-muted">Direct link to audio file (must end with .mp3, .wav, .ogg, .m4a, or .aac)</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="durationSection" style="<?= $podcast['is_series'] ? 'display: none;' : '' ?>">
                            <label class="form-label">Duration (seconds)</label>
                            <input type="number" name="duration" class="form-control" value="<?= $podcast['duration'] ?? '' ?>" min="1" placeholder="e.g., 3600 for 1 hour">
                            <small class="text-muted">30 min = 1800, 1 hr = 3600, 2 hrs = 7200</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-save"></i> Update Podcast
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <i class="bi bi-eye text-primary"></i> Views: <strong><?= formatNumber($podcast['views_count']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-heart text-danger"></i> Likes: <strong><?= formatNumber($podcast['likes_count']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-bookmark text-warning"></i> Saves: <strong><?= formatNumber($podcast['saves_count']) ?></strong>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-list-ol text-info"></i> Episodes: <strong><?= $podcast['total_episodes'] ?></strong>
                        </div>
                        <hr>
                        <small class="text-muted">
                            Created: <?= date('M d, Y', strtotime($podcast['created_at'])) ?><br>
                            Updated: <?= date('M d, Y', strtotime($podcast['updated_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Episodes Management Section (Only for Series) -->
    <?php if ($podcast['is_series']): ?>
    <?php
    // Get existing episodes
    $episodes = $db->fetchAll("
        SELECT * FROM podcast_episodes 
        WHERE podcast_id = ? 
        ORDER BY season_number DESC, episode_number DESC
    ", [$podcast['id']]);
    ?>
    
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-list-ol"></i> Episodes Management</h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEpisodeModal">
                        <i class="bi bi-plus-circle"></i> Add Episode
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($episodes)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-mic-mute fs-1 text-muted"></i>
                        <p class="mt-3 text-muted">No episodes added yet. Click "Add Episode" to get started.</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="80">Thumbnail</th>
                                    <th>Episode</th>
                                    <th>Title</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($episodes as $ep): ?>
                                <tr>
                                    <td>
                                        <?php if ($ep['thumbnail']): ?>
                                        <img src="<?= UPLOADS_URL ?>/podcasts/<?= $ep['thumbnail'] ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-mic"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong>S<?= $ep['season_number'] ?>E<?= $ep['episode_number'] ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($ep['title']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars(truncateText($ep['description'], 80)) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($ep['duration'] > 0): ?>
                                        <small><?= gmdate('H:i:s', $ep['duration']) ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $ep['status'] === 'published' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($ep['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editEpisode(<?= $ep['id'] ?>)" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteEpisode(<?= $ep['id'] ?>)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
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

    <!-- Add Episode Modal -->
    <div class="modal fade" id="addEpisodeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Episode</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addEpisodeForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_episode">
                    <input type="hidden" name="podcast_id" value="<?= $podcast['id'] ?>">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Episode Title *</label>
                                <input type="text" name="episode_title" class="form-control" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Season *</label>
                                <input type="number" name="season_number" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Episode # *</label>
                                <input type="number" name="episode_number" class="form-control" value="1" min="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="episode_description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Audio Source *</label>
                            <select name="audio_source" class="form-select" id="audioSourceSelect" required>
                                <option value="file">Upload Audio File</option>
                                <option value="url">External URL</option>
                            </select>
                        </div>

                        <div class="mb-3" id="audioFileSection">
                            <label class="form-label">Audio File *</label>
                            <input type="file" name="episode_audio_file" class="form-control" accept="audio/*" id="episodeAudioFile" required>
                            <small class="text-muted"><strong class="text-danger">Required!</strong> MP3, WAV, M4A (max 50MB)</small>
                        </div>

                        <div class="mb-3" id="audioUrlSection" style="display: none;">
                            <label class="form-label">Audio URL *</label>
                            <input type="url" name="episode_audio_url" class="form-control" placeholder="https://example.com/audio.mp3" id="episodeAudioUrl" pattern="https?://.+\.(mp3|wav|ogg|m4a|aac)(\?.*)?$">
                            <small class="text-muted"><strong class="text-danger">Required!</strong> Direct link to audio file (.mp3, .wav, .ogg, .m4a)</small>
                            <small class="form-text text-info d-block mt-1">Example: https://example.com/audio.mp3</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Duration (seconds) *</label>
                                <input type="number" name="episode_duration" class="form-control" min="1" required id="episodeDurationInput">
                                <small class="text-muted">Auto-detected for uploaded files</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status *</label>
                                <select name="episode_status" class="form-select" required>
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Episode Thumbnail (Optional)</label>
                            <input type="file" name="episode_thumbnail" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to use series thumbnail</small>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Episode
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Audio source toggle
document.getElementById('audioSourceSelect')?.addEventListener('change', function() {
    const isFile = this.value === 'file';
    document.getElementById('audioFileSection').style.display = isFile ? 'block' : 'none';
    document.getElementById('audioUrlSection').style.display = isFile ? 'none' : 'block';
    
    const fileInput = document.getElementById('episodeAudioFile');
    const urlInput = document.getElementById('episodeAudioUrl');
    
    if (isFile) {
        fileInput.setAttribute('required', 'required');
        urlInput.removeAttribute('required');
    } else {
        fileInput.removeAttribute('required');
        urlInput.setAttribute('required', 'required');
    }
});

// Auto-detect episode audio duration from file
document.getElementById('episodeAudioFile')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        const validTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a', 'audio/x-m4a'];
        if (!validTypes.includes(file.type) && !file.name.match(/\.(mp3|wav|ogg|m4a)$/i)) {
            alert('Please select a valid audio file (MP3, WAV, OGG, M4A)');
            this.value = '';
            return;
        }
        
        const audio = new Audio();
        audio.src = URL.createObjectURL(file);
        audio.addEventListener('loadedmetadata', function() {
            const duration = Math.round(audio.duration);
            document.getElementById('episodeDurationInput').value = duration;
            URL.revokeObjectURL(audio.src);
        });
        audio.addEventListener('error', function() {
            alert('Could not read audio file. Please make sure it is a valid audio file.');
            URL.revokeObjectURL(audio.src);
        });
    }
});

// Validate audio URL format
document.getElementById('episodeAudioUrl')?.addEventListener('blur', function() {
    const url = this.value.trim();
    if (url && !url.match(/\.(mp3|wav|ogg|m4a|aac)(\?.*)?$/i)) {
        alert('Warning: URL does not appear to be a direct link to an audio file.\nMake sure it ends with .mp3, .wav, .ogg, or .m4a');
    }
});

function editEpisode(episodeId) {
    window.location.href = 'podcast-episode-edit.php?id=' + episodeId;
}

function deleteEpisode(episodeId) {
    if (confirm('Are you sure you want to delete this episode?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete_episode"><input type="hidden" name="episode_id" value="' + episodeId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Show/hide audio upload based on podcast type
document.querySelector('select[name="is_series"]').addEventListener('change', function() {
    const isSeries = this.value === '1';
    const audioSection = document.getElementById('audioUploadSection');
    const durationSection = document.getElementById('durationSection');
    
    if (isSeries) {
        audioSection.style.display = 'none';
        durationSection.style.display = 'none';
    } else {
        audioSection.style.display = 'block';
        durationSection.style.display = 'block';
    }
});

// Toggle between file upload and URL for podcast audio
document.getElementById('audioSourceFile')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('audioFileInput').style.display = 'block';
        document.getElementById('audioUrlInput').style.display = 'none';
    }
});

document.getElementById('audioSourceUrl')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('audioFileInput').style.display = 'none';
        document.getElementById('audioUrlInput').style.display = 'block';
    }
});

// Validate podcast audio file type
document.getElementById('podcastAudioFile')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const validTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a', 'audio/x-m4a'];
        if (!validTypes.includes(file.type) && !file.name.match(/\.(mp3|wav|ogg|m4a)$/i)) {
            alert('Please select a valid audio file (MP3, WAV, OGG, M4A)');
            this.value = '';
        }
    }
});

// Validate podcast audio URL format
document.getElementById('podcastAudioUrl')?.addEventListener('blur', function() {
    const url = this.value.trim();
    if (url && !url.match(/\.(mp3|wav|ogg|m4a|aac)(\?.*)?$/i)) {
        alert('Warning: URL does not appear to be a direct link to an audio file.\nMake sure it ends with .mp3, .wav, .ogg, .m4a, or .aac');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
