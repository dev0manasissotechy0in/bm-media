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
$story_id = (int)($_GET['id'] ?? 0);

if (!$story_id) {
    header('Location: stories.php');
    exit;
}

// Get story details
$story = $db->fetchOne("SELECT * FROM stories WHERE id = ?", [$story_id]);
if (!$story) {
    header('Location: stories.php');
    exit;
}

// Get existing media for this story
$existing_media = $db->fetchAll("SELECT * FROM story_media WHERE story_id = ? ORDER BY order_id ASC", [$story_id]);

// If no media in story_media table, use the main story media (backward compatibility)
if (empty($existing_media) && !empty($story['media_url'])) {
    $existing_media[] = [
        'id' => 0,
        'story_id' => $story_id,
        'media_url' => $story['media_url'],
        'media_type' => $story['media_type'],
        'order_id' => 0,
        'created_at' => $story['created_at']
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';
    
    // Handle delete media action
    if ($action === 'delete_media') {
        $media_id = (int)($_POST['media_id'] ?? 0);
        $media_url = $_POST['media_url'] ?? '';
        
        if ($media_id > 0) {
            // Delete from story_media table
            $db->query("DELETE FROM story_media WHERE id = ? AND story_id = ?", [$media_id, $story_id]);
        }
        
        // Delete physical file
        if (!empty($media_url) && file_exists('../' . $media_url)) {
            unlink('../' . $media_url);
        }
        
        // Check if story still has media, if not, update main story record
        $remaining_media = $db->fetchAll("SELECT * FROM story_media WHERE story_id = ? ORDER BY order_id ASC", [$story_id]);
        if (!empty($remaining_media)) {
            // Update primary media to first remaining media
            $db->update('stories', [
                'media_url' => $remaining_media[0]['media_url'],
                'media_type' => $remaining_media[0]['media_type']
            ], 'id = ?', [$story_id]);
        }
        
        header('Location: story-edit.php?id=' . $story_id . '&success=Media deleted successfully');
        exit;
    }
    
    $title = trim($_POST['title'] ?? '');
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
    
    // Handle new file uploads
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
                        'order' => count($existing_media) + $i
                    ];
                } else {
                    $errors[] = "Failed to upload {$_FILES['media_files']['name'][$i]}";
                }
            }
        }
    }
    
    if (empty($errors)) {
        try {
            // Update story details
            $update_data = [
                'title' => $title,
                'link' => $link,
                'duration' => $duration,
                'category_id' => $category_id,
                'status' => $status,
                'expires_at' => $expires_at
            ];
            
            // If new media uploaded, update primary media too
            if (!empty($uploaded_media)) {
                $primary_media = $uploaded_media[0];
                $update_data['media_url'] = $primary_media['url'];
                $update_data['media_type'] = $primary_media['type'];
            }
            
            $db->update('stories', $update_data, 'id = ?', [$story_id]);
            
            // Insert new media into story_media table
            if (!empty($uploaded_media)) {
                foreach ($uploaded_media as $media) {
                    $db->insert('story_media', [
                        'story_id' => $story_id,
                        'media_url' => $media['url'],
                        'media_type' => $media['type'],
                        'order_id' => $media['order']
                    ]);
                }
            }
            
            header('Location: stories.php?success=Story updated successfully');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get categories
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");

$page_title = 'Edit Story';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><i class="bi bi-pencil"></i> Edit Story</h1>
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

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_GET['success']) ?>
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
                                       value="<?= htmlspecialchars($story['title']) ?>" required>
                            </div>

                            <?php if (!empty($existing_media)): ?>
                            <div class="mb-3">
                                <label class="form-label">Current Media (<?= count($existing_media) ?>)</label>
                                <div class="row g-2">
                                    <?php foreach ($existing_media as $index => $media): ?>
                                    <div class="col-md-3 col-sm-4 col-6" id="media-<?= $media['id'] ?>">
                                        <div class="card">
                                            <div class="position-relative">
                                                <?php if ($media['media_type'] === 'video'): ?>
                                                <video class="card-img-top" style="height: 120px; object-fit: cover;">
                                                    <source src="../<?= htmlspecialchars($media['media_url']) ?>">
                                                </video>
                                                <div class="position-absolute top-50 start-50 translate-middle">
                                                    <i class="bi bi-play-circle-fill text-white" style="font-size: 2rem;"></i>
                                                </div>
                                                <?php else: ?>
                                                <img src="../<?= htmlspecialchars($media['media_url']) ?>" 
                                                     class="card-img-top" 
                                                     style="height: 120px; object-fit: cover;">
                                                <?php endif; ?>
                                                
                                                <!-- Delete button -->
                                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1" 
                                                        onclick="deleteMedia(<?= $media['id'] ?>, '<?= htmlspecialchars($media['media_url'], ENT_QUOTES) ?>')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            <div class="card-body p-2">
                                                <small class="d-block text-center">
                                                    <?= ucfirst($media['media_type']) ?> #<?= $index + 1 ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="media_files" class="form-label">Add More Media (Multiple)</label>
                                <input type="file" class="form-control" id="media_files" name="media_files[]" 
                                       accept="image/*,video/*" multiple>
                                <small class="text-muted">
                                    Select multiple files to add. Images: JPG, PNG, GIF, WEBP | Videos: MP4, WEBM, MOV
                                </small>
                            </div>
                            
                            <div id="media-preview" class="mb-3">
                                <div class="row g-2"></div>
                            </div>

                            <div class="mb-3">
                                <label for="link" class="form-label">Link URL (Optional)</label>
                                <input type="url" class="form-control" id="link" name="link" 
                                       value="<?= htmlspecialchars($story['link'] ?? '') ?>"
                                       placeholder="https://example.com/article">
                                <small class="text-muted">Users will be redirected here when they click the story</small>
                            </div>

                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration per slide (seconds)</label>
                                <input type="number" class="form-control" id="duration" name="duration" 
                                       value="<?= htmlspecialchars($story['duration']) ?>" min="1" max="60">
                                <small class="text-muted">Time each media stays visible before auto-advancing</small>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $story['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= $story['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="expired" <?= $story['status'] === 'expired' ? 'selected' : '' ?>>Expired</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="expires_at" class="form-label">Expires At (Optional)</label>
                                <input type="datetime-local" class="form-control" id="expires_at" name="expires_at" 
                                       value="<?= $story['expires_at'] ? date('Y-m-d\TH:i', strtotime($story['expires_at'])) : '' ?>">
                                <small class="text-muted">Leave empty for no expiration</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="stories.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Update Story
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for deleting media -->
<form id="deleteMediaForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_media">
    <input type="hidden" name="media_id" id="deleteMediaId">
    <input type="hidden" name="media_url" id="deleteMediaUrl">
</form>

<script>
function deleteMedia(mediaId, mediaUrl) {
    if (confirm('Are you sure you want to delete this media? This action cannot be undone.')) {
        document.getElementById('deleteMediaId').value = mediaId;
        document.getElementById('deleteMediaUrl').value = mediaUrl;
        document.getElementById('deleteMediaForm').submit();
    }
}

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
                            <small class="d-block text-center mt-1">New Video ${index + 1}</small>
                        </div>
                    `;
                } else {
                    col.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail" 
                             style="width: 100%; height: 150px; object-fit: cover;">
                        <small class="d-block text-center mt-1">New Image ${index + 1}</small>
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
