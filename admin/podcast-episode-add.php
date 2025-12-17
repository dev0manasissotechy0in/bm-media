<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

$podcast_id = (int)($_GET['podcast_id'] ?? 0);
$podcast = $db->fetchOne("SELECT * FROM podcasts WHERE id = ? AND is_series = 1", [$podcast_id]);

if (!$podcast) {
    header('Location: podcasts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']) ?: generateSlug($title);
    $description = trim($_POST['description']);
    $audio_source = $_POST['audio_source'] ?? 'file';
    $duration = (int)$_POST['duration'];
    $episode_number = (int)$_POST['episode_number'];
    $season_number = (int)$_POST['season_number'];
    $status = $_POST['status'];
    $published_at = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
    
    // Handle audio source
    $audio_url = '';
    if ($audio_source === 'url') {
        $audio_url = trim($_POST['audio_url']);
    } else {
        // Handle audio file upload
        if (!empty($_FILES['audio_file']['name'])) {
            $upload_path = UPLOADS_PATH . '/podcasts';
            $result = uploadFile($_FILES['audio_file'], $upload_path, ['mp3', 'wav', 'ogg', 'm4a'], 50 * 1024 * 1024);
            if ($result['success']) {
                $audio_url = UPLOADS_URL . '/podcasts/' . $result['filename'];
            }
        }
    }
    
    $thumbnail = '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['thumbnail'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
        if ($result['success']) {
            $thumbnail = $result['filename'];
        }
    }
    
    try {
        $data = [
            'podcast_id' => $podcast_id,
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
        
        if ($thumbnail) $data['thumbnail'] = $thumbnail;
        
        $episode_id = $db->insert('podcast_episodes', $data);
        
        // Update total episodes count
        $count = $db->fetchOne("SELECT COUNT(*) as count FROM podcast_episodes WHERE podcast_id = ?", [$podcast_id])['count'];
        $db->update('podcasts', ['total_episodes' => $count], 'id = ?', [$podcast_id]);
        
        header('Location: podcast-episodes.php?podcast_id=' . $podcast_id . '&success=1');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Add Episode';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Add New Episode</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($podcast['title']) ?></p>
        </div>
        <a href="podcast-episodes.php?podcast_id=<?= $podcast_id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Episodes
        </a>
    </div>

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
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug (Optional - Auto-generated if empty)</label>
                            <input type="text" name="slug" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="5" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Audio Source</label>
                            <div class="btn-group w-100 mb-2" role="group">
                                <input type="radio" class="btn-check" name="audio_source" id="audioFile" value="file" checked>
                                <label class="btn btn-outline-primary" for="audioFile">Upload File</label>
                                
                                <input type="radio" class="btn-check" name="audio_source" id="audioUrl" value="url">
                                <label class="btn btn-outline-primary" for="audioUrl">External URL</label>
                            </div>
                        </div>

                        <div class="mb-3" id="audioFileSection">
                            <label class="form-label">Audio File *</label>
                            <input type="file" name="audio_file" id="audioFileInput" class="form-control" accept="audio/*">
                            <small class="text-muted">Upload MP3, WAV, M4A (max 50MB) - Duration will be detected automatically</small>
                            <div class="mt-2">
                                <audio id="audioPreview" style="width: 100%; display: none;" controls></audio>
                            </div>
                        </div>

                        <div class="mb-3" id="audioUrlSection" style="display: none;">
                            <label class="form-label">Audio URL *</label>
                            <input type="url" name="audio_url" class="form-control" placeholder="https://example.com/audio.mp3">
                            <small class="text-muted">Direct link to the audio file (MP3, WAV, etc.)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Season Number *</label>
                                <input type="number" name="season_number" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Episode Number *</label>
                                <input type="number" name="episode_number" class="form-control" min="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Duration (Auto-detected)</label>
                                <input type="number" name="duration" id="durationInput" class="form-control" readonly style="background-color: #e9ecef;">
                                <small class="text-muted" id="durationDisplay">Upload audio to detect duration</small>
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
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Published Date (Optional)</label>
                            <input type="datetime-local" name="published_at" class="form-control">
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Episode Thumbnail (Optional)</label>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <small class="text-muted">If empty, will use podcast thumbnail</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-check-circle"></i> Create Episode
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Duration Helper</h5>
                    </div>
                    <div class="card-body">
                        <p class="small mb-2">Convert time to seconds:</p>
                        <ul class="small mb-0">
                            <li>30 minutes = 1800 seconds</li>
                            <li>1 hour = 3600 seconds</li>
                            <li>1.5 hours = 5400 seconds</li>
                            <li>2 hours = 7200 seconds</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Toggle between file upload and URL input
document.querySelectorAll('input[name="audio_source"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const isFile = this.value === 'file';
        document.getElementById('audioFileSection').style.display = isFile ? 'block' : 'none';
        document.getElementById('audioUrlSection').style.display = isFile ? 'none' : 'block';
        
        const fileInput = document.querySelector('input[name="audio_file"]');
        const urlInput = document.querySelector('input[name="audio_url"]');
        
        if (isFile) {
            fileInput.setAttribute('required', 'required');
            urlInput.removeAttribute('required');
        } else {
            urlInput.setAttribute('required', 'required');
            fileInput.removeAttribute('required');
        }
    });
});

// Auto-detect audio duration when file is selected
document.getElementById('audioFileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const audioPreview = document.getElementById('audioPreview');
        const durationInput = document.getElementById('durationInput');
        const durationDisplay = document.getElementById('durationDisplay');
        
        // Create object URL for the audio file
        const fileURL = URL.createObjectURL(file);
        audioPreview.src = fileURL;
        audioPreview.style.display = 'block';
        
        // Wait for audio metadata to load
        audioPreview.addEventListener('loadedmetadata', function() {
            const duration = Math.round(audioPreview.duration);
            durationInput.value = duration;
            
            // Format duration for display
            const hours = Math.floor(duration / 3600);
            const minutes = Math.floor((duration % 3600) / 60);
            const seconds = duration % 60;
            
            let displayText = 'Duration: ';
            if (hours > 0) displayText += hours + 'h ';
            if (minutes > 0) displayText += minutes + 'm ';
            displayText += seconds + 's (' + duration + ' seconds)';
            
            durationDisplay.textContent = displayText;
            durationDisplay.className = 'text-success';
            
            // Clean up the object URL after use
            URL.revokeObjectURL(fileURL);
        });
        
        audioPreview.addEventListener('error', function() {
            durationDisplay.textContent = 'Error: Could not load audio file';
            durationDisplay.className = 'text-danger';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
