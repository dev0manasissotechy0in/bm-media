<?php
/**
 * Edit Mobile Story - Single image only
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

// Get story ID
$story_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$story_id) {
    header('Location: mobile-stories.php');
    exit;
}

// Get story details
$story = $db->fetchOne(
    "SELECT m.*, c.name as category_name 
     FROM mobile_stories m 
     LEFT JOIN categories c ON m.category_id = c.id 
     WHERE m.id = ?", 
    [$story_id]
);

if (!$story) {
    header('Location: mobile-stories.php');
    exit;
}

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
    
    if (empty($errors)) {
        $update_data = [
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'duration' => $duration,
            'category_id' => $category_id > 0 ? $category_id : null,
            'status' => $status,
            'expires_at' => $expires_at,
            'media_type' => $media_type
        ];
        
        // Handle new image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
                    // Delete old image
                    if ($story['image_url'] && file_exists('../' . $story['image_url'])) {
                        unlink('../' . $story['image_url']);
                    }
                    
                    $update_data['image_url'] = 'uploads/mobile-stories/' . $filename;
                } else {
                    $errors[] = 'Failed to upload new image';
                }
            }
        }
        
        // Handle new video upload
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/mobile-stories/';
            $video_file = $_FILES['video'];
            $video_ext = strtolower(pathinfo($video_file['name'], PATHINFO_EXTENSION));
            $video_allowed = ['mp4', 'mov', 'avi', 'webm'];
            
            if (in_array($video_ext, $video_allowed)) {
                $video_filename = 'mobile_story_video_' . time() . '_' . uniqid() . '.' . $video_ext;
                $video_filepath = $upload_dir . $video_filename;
                
                if (move_uploaded_file($video_file['tmp_name'], $video_filepath)) {
                    // Delete old video if exists
                    if ($story['video_url'] && file_exists('../' . $story['video_url'])) {
                        unlink('../' . $story['video_url']);
                    }
                    
                    $update_data['video_url'] = 'uploads/mobile-stories/' . $video_filename;
                } else {
                    $errors[] = 'Failed to upload new video';
                }
            } else {
                $errors[] = 'Only video files are allowed (mp4, mov, avi, webm)';
            }
        }
        
        if (empty($errors)) {
            $success = $db->update('mobile_stories', $update_data, 'id = ?', [$story_id]);
            
            if ($success) {
                // Refresh story data
                $story = $db->fetchOne(
                    "SELECT m.*, c.name as category_name 
                     FROM mobile_stories m 
                     LEFT JOIN categories c ON m.category_id = c.id 
                     WHERE m.id = ?", 
                    [$story_id]
                );
                $success = 'Story updated successfully!';
            } else {
                $errors[] = 'Failed to update story';
            }
        }
    }
}

// Get categories
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$page_title = 'Edit Mobile Story';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-pencil"></i> Edit Mobile Story</h1>
            <a href="mobile-stories.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Stories
            </a>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

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
                                       value="<?= htmlspecialchars($story['title']) ?>"
                                       placeholder="Story title">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description (Optional)</label>
                                <textarea name="description" class="form-control" rows="2" 
                                          placeholder="Brief description"><?= htmlspecialchars($story['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Media Type *</label>
                                <select name="media_type" class="form-select" id="mediaType" onchange="toggleMediaFields()">
                                    <option value="image" <?= ($story['media_type'] ?? 'image') === 'image' ? 'selected' : '' ?>>Image</option>
                                    <option value="video" <?= ($story['media_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <div class="mb-2">
                                    <img src="../<?= htmlspecialchars($story['image_url']) ?>" 
                                         style="max-width: 200px; border-radius: 8px;" 
                                         alt="Current story image">
                                </div>
                                
                                <label class="form-label">Change Image <span id="imageLabelNote">(Optional)</span></label>
                                <input type="file" name="image" class="form-control" accept="image/*" 
                                       onchange="previewImage(this)">
                                <small class="text-muted">Leave empty to keep current image. Recommended: 1080x1920 (9:16 ratio)</small>
                                
                                <div id="imagePreview" class="mt-3" style="display: none;">
                                    <p class="text-muted small">New image preview:</p>
                                    <img id="preview" src="" style="max-width: 200px; border-radius: 8px;">
                                </div>
                            </div>

                            <div id="videoField" style="display: none;">
                                <div class="card border-primary mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <i class="bi bi-camera-video"></i> Video Upload Section
                                    </div>
                                    <div class="card-body">
                                        <?php if (!empty($story['video_url'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Current Video</label>
                                            <div class="mb-2">
                                                <video controls style="max-width: 100%; max-height: 300px; border-radius: 8px;">
                                                    <source src="../<?= htmlspecialchars($story['video_url']) ?>" type="video/mp4">
                                                </video>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Change Video (Optional)</label>
                                            <input type="file" name="video" class="form-control" accept="video/*" 
                                                   onchange="previewVideo(this)">
                                            <small class="text-muted">Leave empty to keep current video. Formats: MP4, MOV, AVI, WebM</small>
                                        </div>
                                        
                                        <div id="videoPreview" class="mt-3" style="display: none;">
                                            <p class="text-muted small">New video preview:</p>
                                            <video id="videoPreviewPlayer" controls style="max-width: 100%; max-height: 400px; border-radius: 8px;"></video>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Link (Optional)</label>
                                <input type="url" name="link" class="form-control" 
                                       value="<?= htmlspecialchars($story['link'] ?? '') ?>"
                                       placeholder="https://example.com/article">
                                <small class="text-muted">Link to full article or related content</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="0">No Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($story['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Duration (seconds)</label>
                                    <input type="number" name="duration" class="form-control" 
                                           value="<?= htmlspecialchars($story['duration'] ?? 5) ?>" 
                                           min="1" max="30">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $story['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="expired" <?= $story['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Expires At (Optional)</label>
                                    <input type="datetime-local" name="expires_at" class="form-control"
                                           value="<?= !empty($story['expires_at']) ? date('Y-m-d\TH:i', strtotime($story['expires_at'])) : '' ?>">
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Update Story
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
                        <h6 class="card-title"><i class="bi bi-info-circle"></i> Story Info</h6>
                        <ul class="small mb-0">
                            <li><strong>Views:</strong> <?= number_format($story['views_count'] ?? 0) ?></li>
                            <li><strong>Created:</strong> <?= date('M d, Y h:i A', strtotime($story['created_at'])) ?></li>
                            <li><strong>Status:</strong> 
                                <span class="badge bg-<?= $story['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($story['status']) ?>
                                </span>
                            </li>
                            <?php if ($story['category_name']): ?>
                            <li><strong>Category:</strong> <?= htmlspecialchars($story['category_name']) ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div class="card bg-light mt-3">
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
                        <p class="small mb-2">View this story:</p>
                        <a href="<?= BASE_URL ?>/mobile-story.php?id=<?= $story['id'] ?>" 
                           target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-box-arrow-up-right"></i> Open Story
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMediaFields() {
    const mediaType = document.getElementById('mediaType').value;
    const videoField = document.getElementById('videoField');
    const imageLabelNote = document.getElementById('imageLabelNote');
    
    if (mediaType === 'video') {
        imageLabelNote.textContent = '(Thumbnail for video - Optional)';
        videoField.style.display = 'block';
    } else {
        imageLabelNote.textContent = '(Optional)';
        videoField.style.display = 'none';
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
