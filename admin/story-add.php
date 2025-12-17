<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $media_type = $_POST['media_type'] ?? 'image';
    $link = trim($_POST['link'] ?? '');
    $duration = (int)($_POST['duration'] ?? 5);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if ($category_id === 0) {
        $errors[] = 'Please select a category';
    }
    
    // Handle multiple file uploads
    $uploaded_media = [];
    if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
        $upload_dir = '../uploads/stories/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_count = count($_FILES['media_files']['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($_FILES['media_files']['error'][$i] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($_FILES['media_files']['name'][$i], PATHINFO_EXTENSION));
                $allowed_image = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $allowed_video = ['mp4', 'webm', 'mov'];
                
                $file_media_type = in_array($file_extension, $allowed_image) ? 'image' : 
                                  (in_array($file_extension, $allowed_video) ? 'video' : null);
                
                if (!$file_media_type) {
                    $errors[] = "Invalid file format for {$_FILES['media_files']['name'][$i]}";
                    continue;
                }
                
                $filename = uniqid('story_') . '_' . $i . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['media_files']['tmp_name'][$i], $target_path)) {
                    $uploaded_media[] = [
                        'url' => 'uploads/stories/' . $filename,
                        'type' => $file_media_type,
                        'order' => $i
                    ];
                } else {
                    $errors[] = "Failed to upload {$_FILES['media_files']['name'][$i]}";
                }
            }
        }
        
        if (empty($uploaded_media)) {
            $errors[] = 'At least one media file is required';
        }
    } else {
        $errors[] = 'Media file(s) are required';
    }
    
    if (empty($errors)) {
        try {
            // Use first media as primary for backward compatibility
            $primary_media = $uploaded_media[0];
            
            $story_id = $db->insert('stories', [
                'title' => $title,
                'media_url' => $primary_media['url'],
                'media_type' => $primary_media['type'],
                'link' => $link,
                'duration' => $duration,
                'category_id' => $category_id,
                'status' => $status,
                'expires_at' => $expires_at
            ]);
            
            // Insert all media into story_media table
            if ($story_id) {
                foreach ($uploaded_media as $media) {
                    $db->insert('story_media', [
                        'story_id' => $story_id,
                        'media_url' => $media['url'],
                        'media_type' => $media['type'],
                        'order_id' => $media['order']
                    ]);
                }
            }
            
            header('Location: stories.php?success=Story added successfully');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get categories
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$page_title = 'Add Story';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-plus-lg"></i> Add Story</h1>
            <a href="stories.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Stories
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="media_files" class="form-label">Media Files * (Multiple)</label>
                                <input type="file" class="form-control" id="media_files" name="media_files[]" 
                                       accept="image/*,video/*" multiple required>
                                <small class="text-muted">
                                    Select multiple files. Images: JPG, PNG, GIF, WEBP | Videos: MP4, WEBM, MOV
                                </small>
                            </div>
                            
                            <div id="media-preview" class="mb-3">
                                <div class="row g-2"></div>
                            </div>

                            <div class="mb-3">
                                <label for="link" class="form-label">Link URL (Optional)</label>
                                <input type="url" class="form-control" id="link" name="link" 
                                       value="<?= htmlspecialchars($_POST['link'] ?? '') ?>"
                                       placeholder="https://example.com/article">
                                <small class="text-muted">Users will be redirected here when they click the story</small>
                            </div>

                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration per slide (seconds)</label>
                                <input type="number" class="form-control" id="duration" name="duration" 
                                       value="<?= htmlspecialchars($_POST['duration'] ?? '5') ?>" min="1" max="60">
                                <small class="text-muted">How long the story will be displayed (default: 5 seconds)</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" 
                                                <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= ($_POST['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="expired" <?= ($_POST['status'] ?? '') === 'expired' ? 'selected' : '' ?>>Expired</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="expires_at" class="form-label">Expires At (Optional)</label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                                       value="<?= htmlspecialchars($_POST['expires_at'] ?? '') ?>">
                                <small class="text-muted">Story will automatically expire at this date/time</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="stories.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Add Story
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Media files preview
document.getElementById('media_files')?.addEventListener('change', function(e) {
    const preview = document.getElementById('media-preview').querySelector('.row');
    preview.innerHTML = '';
    
    if (this.files && this.files.length > 0) {
        Array.from(this.files).forEach((file, index) => {
            const reader = new FileReader();
            const isVideo = file.type.startsWith('video/');
            
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-sm-4 col-6';
                
                if (isVideo) {
                    col.innerHTML = `
                        <div class="position-relative">
                            <video class="img-thumbnail w-100" style="height: 150px; object-fit: cover;">
                                <source src="${e.target.result}" type="${file.type}">
                            </video>
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <i class="bi bi-play-circle-fill text-white" style="font-size: 2rem;"></i>
                            </div>
                            <small class="d-block text-center mt-1">Video ${index + 1}</small>
                        </div>
                    `;
                } else {
                    col.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" 
                             style="width: 100%; height: 150px; object-fit: cover;">
                        <small class="d-block text-center mt-1">Image ${index + 1}</small>
                    `;
                }
                
                preview.appendChild(col);
            };
            
            reader.readAsDataURL(file);
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
