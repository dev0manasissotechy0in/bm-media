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
    $video_url = trim($_POST['video_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $video_source = $_POST['video_source'] ?? 'url';
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if ($category_id === 0) {
        $errors[] = 'Please select a category';
    }
    
    // Handle video upload or URL
    if ($video_source === 'upload') {
        // Handle video file upload
        if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/reels/videos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
            $allowed_video = ['mp4', 'webm', 'mov', 'avi'];
            
            if (!in_array($file_extension, $allowed_video)) {
                $errors[] = 'Invalid video format. Allowed: MP4, WEBM, MOV, AVI';
            } elseif ($_FILES['video_file']['size'] > 100 * 1024 * 1024) {
                $errors[] = 'Video file too large. Maximum size: 100MB';
            } else {
                $filename = uniqid('reel_video_') . '.' . $file_extension;
                $target_path = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['video_file']['tmp_name'], $target_path)) {
                    $video_url = 'uploads/reels/videos/' . $filename;
                } else {
                    $errors[] = 'Failed to upload video file';
                }
            }
        } else {
            $errors[] = 'Video file is required when using upload option';
        }
    } else {
        // Validate video URL
        if (empty($video_url)) {
            $errors[] = 'Video URL is required when using URL option';
        }
    }
    
    // Handle thumbnail upload
    $thumbnail = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/reels/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_extension, $allowed)) {
            $errors[] = 'Invalid thumbnail format. Allowed: JPG, PNG, GIF, WEBP';
        } else {
            $filename = uniqid('reel_thumb_') . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_path)) {
                $thumbnail = 'uploads/reels/' . $filename;
            } else {
                $errors[] = 'Failed to upload thumbnail';
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->insert('reels', [
                'title' => $title,
                'video_url' => $video_url,
                'thumbnail' => $thumbnail,
                'description' => $description,
                'category_id' => $category_id,
                'status' => $status
            ]);
            
            header('Location: reels.php?success=Reel added successfully');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get categories
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$page_title = 'Add Reel';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-plus-lg"></i> Add Reel</h1>
            <a href="reels.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Reels
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
                                <label class="form-label">Video Source *</label>
                                <div class="btn-group w-100 mb-2" role="group">
                                    <input type="radio" class="btn-check" name="video_source" id="source_url" value="url" 
                                           <?= ($_POST['video_source'] ?? 'url') === 'url' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary" for="source_url">
                                        <i class="bi bi-link-45deg"></i> Video URL
                                    </label>
                                    
                                    <input type="radio" class="btn-check" name="video_source" id="source_upload" value="upload"
                                           <?= ($_POST['video_source'] ?? '') === 'upload' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary" for="source_upload">
                                        <i class="bi bi-upload"></i> Upload Video
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3" id="url_input_group">
                                <label for="video_url" class="form-label">Video URL</label>
                                <input type="url" class="form-control" id="video_url" name="video_url" 
                                       value="<?= htmlspecialchars($_POST['video_url'] ?? '') ?>"
                                       placeholder="https://www.youtube.com/watch?v=... or direct video URL">
                                <small class="text-muted">Supports YouTube, Vimeo, or direct video URLs (MP4, WEBM)</small>
                            </div>

                            <div class="mb-3" id="upload_input_group" style="display: none;">
                                <label for="video_file" class="form-label">Upload Video File</label>
                                <input type="file" class="form-control" id="video_file" name="video_file" 
                                       accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                                <small class="text-muted">Supported formats: MP4, WEBM, MOV, AVI (Max: 100MB)</small>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="thumbnail" class="form-label">Thumbnail (Optional)</label>
                                <input type="file" class="form-control" id="thumbnail" name="thumbnail" 
                                       accept="image/*">
                                <small class="text-muted">Upload a custom thumbnail image. If not provided, video platform thumbnail will be used.</small>
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
                                    <option value="inactive" <?= ($_POST['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
<script>
// Toggle video input based on source selection
function toggleVideoInput() {
    const sourceUrl = document.getElementById('source_url').checked;
    const urlGroup = document.getElementById('url_input_group');
    const uploadGroup = document.getElementById('upload_input_group');
    const urlInput = document.getElementById('video_url');
    const fileInput = document.getElementById('video_file');
    
    if (sourceUrl) {
        urlGroup.style.display = 'block';
        uploadGroup.style.display = 'none';
        urlInput.required = true;
        fileInput.required = false;
    } else {
        urlGroup.style.display = 'none';
        uploadGroup.style.display = 'block';
        urlInput.required = false;
        fileInput.required = true;
    }
}

document.getElementById('source_url').addEventListener('change', toggleVideoInput);
document.getElementById('source_upload').addEventListener('change', toggleVideoInput);

// Initialize on page load
toggleVideoInput();

// Preview video URL format
document.getElementById('video_url').addEventListener('blur', function() {
    const url = this.value.trim();
    if (url) {
        let platform = 'Direct Video';
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            platform = 'YouTube';
        } else if (url.includes('vimeo.com')) {
            platform = 'Vimeo';
        }
        console.log('Detected platform:', platform);
    }
});
</script>               <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Add Reel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Preview video URL format
document.getElementById('video_url').addEventListener('blur', function() {
    const url = this.value.trim();
    if (url) {
        let platform = 'Direct Video';
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            platform = 'YouTube';
        } else if (url.includes('vimeo.com')) {
            platform = 'Vimeo';
        }
        console.log('Detected platform:', platform);
    }
});
</script>

<?php include 'includes/footer.php'; ?>
