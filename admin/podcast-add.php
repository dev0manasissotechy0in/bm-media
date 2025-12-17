<?php
require_once '../config/config.php';
require_once 'auth_check.php';

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']) ?: generateSlug($title);
    $description = trim($_POST['description']);
    $author_name = trim($_POST['author_name']);
    $author_bio = trim($_POST['author_bio'] ?? '');
    $category = trim($_POST['category']);
    $language = trim($_POST['language']);
    $is_series = (int)($_POST['is_series'] ?? 0);
    $status = $_POST['status'];
    $duration = (int)($_POST['duration'] ?? 0);
    
    // Handle file uploads
    $thumbnail = '';
    $cover_image = '';
    $audio_url = '';
    
    if (!empty($_FILES['thumbnail']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['thumbnail'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
        if ($result['success']) {
            $thumbnail = $result['filename'];
        }
    }
    
    if (!empty($_FILES['cover_image']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['cover_image'], $upload_path, ['jpg', 'jpeg', 'png', 'webp'], 5 * 1024 * 1024);
        if ($result['success']) {
            $cover_image = $result['filename'];
        }
    }
    
    // Handle audio file upload (for single podcasts)
    if (!$is_series && !empty($_FILES['audio_file']['name'])) {
        $upload_path = UPLOADS_PATH . '/podcasts';
        $result = uploadFile($_FILES['audio_file'], $upload_path, ['mp3', 'wav', 'ogg', 'm4a'], 50 * 1024 * 1024); // 50MB max
        if ($result['success']) {
            $audio_url = UPLOADS_URL . '/podcasts/' . $result['filename'];
        }
    }
    
    try {
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
        
        if ($thumbnail) $data['thumbnail'] = $thumbnail;
        if ($cover_image) $data['cover_image'] = $cover_image;
        if ($audio_url) $data['audio_url'] = $audio_url;
        if (!$is_series && $duration > 0) $data['duration'] = $duration;
        
        $podcast_id = $db->insert('podcasts', $data);
        
        // Redirect to episode management for series, or podcast list for single
        if ($is_series) {
            header('Location: podcast-episodes.php?podcast_id=' . $podcast_id . '&success=series_created');
        } else {
            header('Location: podcasts.php?success=1');
        }
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = 'Add New Podcast';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Add New Podcast</h1>
            <small class="text-muted">Create a single podcast or a series with multiple episodes</small>
        </div>
        <a href="podcasts.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Podcasts
        </a>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="alert alert-info alert-dismissible fade show">
        <i class="bi bi-info-circle"></i> <strong>Choose your podcast type:</strong>
        <ul class="mb-0 mt-2">
            <li><strong>Single Podcast:</strong> Upload one audio file directly - perfect for standalone episodes</li>
            <li><strong>Series (Multiple Episodes):</strong> Create a series container first, then add individual episodes with their own audio files</li>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

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
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug (Optional - Auto-generated if empty)</label>
                            <input type="text" name="slug" class="form-control">
                            <small class="text-muted">URL-friendly version of title</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="5" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Author Name *</label>
                                <input type="text" name="author_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category *</label>
                                <input type="text" name="category" class="form-control" required placeholder="e.g., Technology, Business, Education">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Author Bio (Optional)</label>
                            <textarea name="author_bio" class="form-control" rows="3"></textarea>
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
                            <select name="is_series" class="form-select form-select-lg border-primary" required id="podcastTypeSelect">
                                <option value="">-- Select Type --</option>
                                <option value="0">🎵 Single Podcast</option>
                                <option value="1">📚 Series (Multiple Episodes)</option>
                            </select>
                            <div class="alert alert-light mt-2 mb-0 small" id="typeHint" style="display: none;">
                                <div id="singleHint" style="display: none;">
                                    ✓ You'll upload the audio file in this form
                                </div>
                                <div id="seriesHint" style="display: none;">
                                    ✓ After creating the series, you'll be redirected to add individual episodes
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Language *</label>
                            <select name="language" class="form-select" required>
                                <option value="English">English</option>
                                <option value="Hindi">Hindi</option>
                                <option value="Spanish">Spanish</option>
                                <option value="French">French</option>
                                <option value="German">German</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Thumbnail *</label>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*" required>
                            <small class="text-muted">Recommended: 400x400px</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cover Image (Optional)</label>
                            <input type="file" name="cover_image" class="form-control" accept="image/*">
                            <small class="text-muted">Larger banner image for detail page</small>
                        </div>

                        <hr>

                        <div class="mb-3" id="audioUploadSection" style="display: none;">
                            <label class="form-label">Audio File * (Single Podcast Only)</label>
                            <input type="file" name="audio_file" id="audioFileInput" class="form-control" accept="audio/*">
                            <small class="text-muted">Upload MP3, WAV, M4A (max 50MB) - <strong class="text-danger">Required for single podcasts!</strong></small>
                            <div class="mt-2">
                                <audio id="audioPreview" style="width: 100%; display: none;" controls></audio>
                            </div>
                        </div>

                        <div class="mb-3" id="durationSection" style="display: none;">
                            <label class="form-label">Duration (Auto-detected)</label>
                            <input type="number" name="duration" id="durationInput" class="form-control" readonly style="background-color: #e9ecef;">
                            <small class="text-muted" id="durationDisplay">Duration will be detected automatically when audio file is uploaded</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-check-circle"></i> Create Podcast
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Show/hide audio upload based on podcast type
document.getElementById('podcastTypeSelect').addEventListener('change', function() {
    const value = this.value;
    const isSeries = value === '1';
    const audioSection = document.getElementById('audioUploadSection');
    const durationSection = document.getElementById('durationSection');
    const audioInput = document.querySelector('input[name="audio_file"]');
    const typeHint = document.getElementById('typeHint');
    const singleHint = document.getElementById('singleHint');
    const seriesHint = document.getElementById('seriesHint');
    
    // Show/hide hints
    if (value === '') {
        typeHint.style.display = 'none';
    } else {
        typeHint.style.display = 'block';
        singleHint.style.display = value === '0' ? 'block' : 'none';
        seriesHint.style.display = value === '1' ? 'block' : 'none';
    }
    
    if (isSeries) {
        audioSection.style.display = 'none';
        durationSection.style.display = 'none';
        audioInput.removeAttribute('required');
    } else if (value === '0') {
        audioSection.style.display = 'block';
        durationSection.style.display = 'block';
        audioInput.setAttribute('required', 'required');
    }
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

// Don't trigger on page load - wait for user selection
</script>

<?php include 'includes/footer.php'; ?>
