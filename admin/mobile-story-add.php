<?php
/**
 * Add Mobile Story - Single image only
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
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $duration = (int)($_POST['duration'] ?? 5);
    $status = $_POST['status'] ?? 'active';
    $expires_at = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $media_type = $_POST['media_type'] ?? 'image';
    
    // Validation
    if (empty($title)) $errors[] = 'Title is required';
    if ($duration < 1 || $duration > 30) $errors[] = 'Duration must be between 1-30 seconds';
    
    // Check if image or video is uploaded based on media type
    if ($media_type === 'video') {
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Video is required for video stories';
        }
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Thumbnail image is required for video stories';
        }
    } else {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image is required';
        }
    }
    
    if (empty($errors)) {
        // Upload image
        $upload_dir = '../uploads/mobile-stories/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Only image files are allowed (jpg, png, gif, webp)';
        } else {
            $filename = 'mobile_story_' . time() . '_' . uniqid() . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $image_url = 'uploads/mobile-stories/' . $filename;
                $video_url = null;
                
                // Handle video upload if media type is video
                if ($media_type === 'video' && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    $video_file = $_FILES['video'];
                    $video_ext = strtolower(pathinfo($video_file['name'], PATHINFO_EXTENSION));
                    $video_allowed = ['mp4', 'mov', 'avi', 'webm'];
                    
                    if (in_array($video_ext, $video_allowed)) {
                        $video_filename = 'mobile_story_video_' . time() . '_' . uniqid() . '.' . $video_ext;
                        $video_filepath = $upload_dir . $video_filename;
                        
                        if (move_uploaded_file($video_file['tmp_name'], $video_filepath)) {
                            $video_url = 'uploads/mobile-stories/' . $video_filename;
                        } else {
                            $errors[] = 'Failed to upload video';
                        }
                    } else {
                        $errors[] = 'Only video files are allowed (mp4, mov, avi, webm)';
                    }
                }
                
                if (empty($errors)) {
                    // Insert into database
                    $data = [
                        'title' => $title,
                        'image_url' => $image_url,
                        'video_url' => $video_url,
                        'media_type' => $media_type,
                        'description' => $description,
                        'link' => $link,
                        'duration' => $duration,
                        'category_id' => $category_id > 0 ? $category_id : null,
                        'status' => $status,
                        'expires_at' => $expires_at
                    ];
                    
                    $id = $db->insert('mobile_stories', $data);
                    
                    if ($id) {
                        // Send notification if story is active
                        if ($status === 'active') {
                            try {
                                require_once INCLUDES_PATH . '/Settings.php';
                                require_once INCLUDES_PATH . '/FCMv1HelperLite.php';
                                
                                if (Settings::get('enable_push_notifications', '0') === '1') {
                                    $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
                                    $storyImageUrl = SITE_URL . '/' . $image_url;
                                    $fcm->sendStoryNotification($id, $title, $storyImageUrl);
                                }
                            } catch (Exception $e) {
                                error_log('Story Notification Error: ' . $e->getMessage());
                            }
                        }
                        
                        header('Location: mobile-stories.php?success=Story added successfully');
                        exit;
                    } else {
                        $errors[] = 'Failed to save story';
                    }
                }
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    }
}

// Get categories
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$page_title = 'Add Mobile Story';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-plus-lg"></i> Add Mobile Story</h1>
            <a href="mobile-stories.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Stories
            </a>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" required 
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                       placeholder="Story title">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="2" 
                                          placeholder="Brief description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Media Type *</label>
                                <select name="media_type" class="form-select" id="mediaType" onchange="toggleMediaFields()">
                                    <option value="image" <?= ($_POST['media_type'] ?? 'image') === 'image' ? 'selected' : '' ?>>Image</option>
                                    <option value="video" <?= ($_POST['media_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                                </select>
                            </div>

                            <div class="mb-3" id="imageField">
                                <label class="form-label">Image * <span id="imageLabelNote">(Single image only)</span></label>
                                <input type="file" name="image" class="form-control" accept="image/*" required 
                                       onchange="previewImage(this)">
                                <small class="text-muted">Recommended: 1080x1920 (9:16 ratio for mobile)</small>
                                
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <img id="preview" src="" style="max-width: 200px; border-radius: 8px;">
                                </div>
                            </div>

                            <div id="videoField" style="display: none;">
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <i class="bi bi-camera-video"></i> Video Upload Section
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Video File *</label>
                                            <input type="file" name="video" class="form-control" accept="video/*" 
                                                   onchange="previewVideo(this)">
                                            <small class="text-muted">Formats: MP4, MOV, AVI, WebM. Max 50MB recommended.</small>
                                        </div>
                                        
                                        <div id="videoPreview" class="mt-3" style="display: none;">
                                            <video id="videoPreviewPlayer" controls style="max-width: 100%; max-height: 400px; border-radius: 8px;"></video>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Link (Optional)</label>
                                <input type="url" name="link" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['link'] ?? '') ?>"
                                       placeholder="https://example.com/article">
                                <small class="text-muted">Link to full article or related content</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="0">No Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active">Active</option>
                                        <option value="expired">Expired</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expires At (Optional)</label>
                                    <input type="datetime-local" name="expires_at" class="form-control"
                                           value="<?= $_POST['expires_at'] ?? '' ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Save Story
                                </button>
                                <a href="mobile-stories.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-info-circle"></i> Mobile Story Tips</h6>
                        <ul class="small mb-0">
                            <li><strong>Images:</strong> Use 9:16 ratio, 1080x1920px</li>
                            <li><strong>Videos:</strong> MP4/MOV/AVI/WebM, max 50MB</li>
                            <li>Keep files optimized for fast loading</li>
                            <li>Video stories require thumbnail image</li>
                            <li>Stories expire after 24 hours by default</li>
                            <li>Perfect for mobile sharing</li>
                        </ul>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title"><i class="bi bi-share"></i> Sharing</h6>
                        <p class="small mb-0">Mobile stories are optimized for WhatsApp, Facebook, and Instagram sharing with proper meta tags for previews.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMediaFields() {
    const mediaType = document.getElementById('mediaType').value;
    const imageField = document.getElementById('imageField');
    const videoField = document.getElementById('videoField');
    const imageLabelNote = document.getElementById('imageLabelNote');
    const imageInput = document.querySelector('input[name="image"]');
    const videoInput = document.querySelector('input[name="video"]');
    
    if (mediaType === 'video') {
        imageLabelNote.textContent = '(Thumbnail for video)';
        videoField.style.display = 'block';
        videoInput.setAttribute('required', 'required');
    } else {
        imageLabelNote.textContent = '(Single image only)';
        videoField.style.display = 'none';
        videoInput.removeAttribute('required');
    }
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewVideo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        const videoPlayer = document.getElementById('videoPreviewPlayer');
        reader.onload = function(e) {
            videoPlayer.src = e.target.result;
            document.getElementById('videoPreview').style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleMediaFields);
</script>

<?php include 'includes/footer.php'; ?>
