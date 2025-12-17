<?php
/**
 * Case Media Management
 */

require_once 'auth_check.php';

$page_title = 'Case Media';

$db = Database::getInstance();

$case_id = (int)($_GET['case_id'] ?? 0);

if (!$case_id) {
    $_SESSION['error'] = 'Invalid case ID';
    header('Location: cases.php');
    exit;
}

// Get case
$case = $db->fetchOne("SELECT * FROM case_threads WHERE id = ?", [$case_id]);

if (!$case) {
    $_SESSION['error'] = 'Case not found';
    header('Location: cases.php');
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $media = $db->fetchOne("SELECT file_url FROM case_media WHERE id = ? AND case_id = ?", [$id, $case_id]);
    
    if ($media && $media['file_url'] && file_exists('../' . $media['file_url'])) {
        unlink('../' . $media['file_url']);
    }
    
    $db->query("DELETE FROM case_media WHERE id = ? AND case_id = ?", [$id, $case_id]);
    $_SESSION['success'] = 'Media deleted successfully';
    header('Location: case-media.php?case_id=' . $case_id);
    exit;
}

// Handle Toggle Featured
if (isset($_GET['toggle_featured'])) {
    $id = (int)$_GET['toggle_featured'];
    $media = $db->fetchOne("SELECT is_featured FROM case_media WHERE id = ? AND case_id = ?", [$id, $case_id]);
    $new_featured = $media['is_featured'] ? 0 : 1;
    
    $db->query("UPDATE case_media SET is_featured = ? WHERE id = ? AND case_id = ?", [$new_featured, $id, $case_id]);
    $_SESSION['success'] = 'Featured status updated';
    header('Location: case-media.php?case_id=' . $case_id);
    exit;
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    $caption = trim($_POST['caption'] ?? '');
    $media_type = $_POST['media_type'] ?? 'photo';
    $media_date = $_POST['media_date'] ?? null;
    $credit = trim($_POST['credit'] ?? '');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    $errors = [];
    
    // Handle file upload
    if ($_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cases/media/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = $_FILES['media_file']['name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type based on media type
        $allowed_types = [
            'photo' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'video' => ['mp4', 'avi', 'mov', 'webm'],
            'audio' => ['mp3', 'wav', 'ogg', 'm4a']
        ];
        
        if (!in_array($file_extension, $allowed_types[$media_type])) {
            $errors[] = 'Invalid file type for ' . $media_type;
        }
        
        if (empty($errors)) {
            $new_filename = $media_type . '_' . $case_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            $file_url = 'uploads/cases/media/' . $new_filename;
            
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $upload_dir . $new_filename)) {
                $db->insert('case_media', [
                    'case_id' => $case_id,
                    'media_type' => $media_type,
                    'file_url' => $file_url,
                    'caption' => $caption,
                    'media_date' => $media_date,
                    'source' => $credit,
                    'is_featured' => $is_featured
                ]);
                
                $_SESSION['success'] = 'Media uploaded successfully';
                header('Location: case-media.php?case_id=' . $case_id);
                exit;
            } else {
                $errors[] = 'Failed to upload file';
            }
        }
    } else {
        $errors[] = 'Please select a file to upload';
    }
}

// Get media with filter
$media_type_filter = $_GET['type'] ?? 'all';
$where = "case_id = ?";
$params = [$case_id];

if ($media_type_filter !== 'all') {
    $where .= " AND media_type = ?";
    $params[] = $media_type_filter;
}

$media_items = $db->fetchAll("
    SELECT * FROM case_media 
    WHERE {$where}
    ORDER BY is_featured DESC, media_date DESC, id DESC
", $params);

// Get counts
$photo_count = $db->fetchOne("SELECT COUNT(*) as count FROM case_media WHERE case_id = ? AND media_type = 'photo'", [$case_id]);
$video_count = $db->fetchOne("SELECT COUNT(*) as count FROM case_media WHERE case_id = ? AND media_type = 'video'", [$case_id]);
$audio_count = $db->fetchOne("SELECT COUNT(*) as count FROM case_media WHERE case_id = ? AND media_type = 'audio'", [$case_id]);

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Media Gallery: <?php echo htmlspecialchars($case['title']); ?></h1>
        <a href="case-edit.php?id=<?php echo $case_id; ?>" class="btn btn-secondary">← Back to Case</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $photo_count['count']; ?></h3>
                    <p class="mb-0">Photos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $video_count['count']; ?></h3>
                    <p class="mb-0">Videos</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3><?php echo $audio_count['count']; ?></h3>
                    <p class="mb-0">Audio Files</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Upload Media</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Media Type *</label>
                            <select name="media_type" class="form-control" id="mediaType" required>
                                <option value="photo">Photo</option>
                                <option value="video">Video</option>
                                <option value="audio">Audio</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Media File *</label>
                            <input type="file" name="media_file" class="form-control" required id="mediaFile">
                            <small class="form-text text-muted" id="fileHelp">Allowed: JPG, PNG, GIF, WEBP</small>
                        </div>

                        <div class="form-group">
                            <label>Caption</label>
                            <input type="text" name="caption" class="form-control" 
                                   placeholder="Brief description">
                        </div>

                        <div class="form-group">
                            <label>Media Date</label>
                            <input type="date" name="media_date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Credit/Source</label>
                            <input type="text" name="credit" class="form-control" 
                                   placeholder="Photo credit or source">
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured">
                            <label class="form-check-label" for="isFeatured">
                                Featured (show first)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Upload Media</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Media Gallery (<?php echo count($media_items); ?>)</h5>
                    <div class="btn-group">
                        <a href="?case_id=<?php echo $case_id; ?>&type=all" 
                           class="btn btn-sm <?php echo $media_type_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All
                        </a>
                        <a href="?case_id=<?php echo $case_id; ?>&type=photo" 
                           class="btn btn-sm <?php echo $media_type_filter === 'photo' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Photos
                        </a>
                        <a href="?case_id=<?php echo $case_id; ?>&type=video" 
                           class="btn btn-sm <?php echo $media_type_filter === 'video' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Videos
                        </a>
                        <a href="?case_id=<?php echo $case_id; ?>&type=audio" 
                           class="btn btn-sm <?php echo $media_type_filter === 'audio' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Audio
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($media_items)): ?>
                        <p class="text-center text-muted">No media yet. Upload your first file.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($media_items as $media): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <?php if ($media['media_type'] === 'photo'): ?>
                                                <img src="<?php echo BASE_URL . '/' . $media['file_url']; ?>" 
                                                     class="img-fluid mb-2" style="max-height: 200px; width: 100%; object-fit: cover;">
                                            <?php elseif ($media['media_type'] === 'video'): ?>
                                                <video controls class="w-100 mb-2" style="max-height: 200px;">
                                                    <source src="<?php echo BASE_URL . '/' . $media['file_url']; ?>">
                                                </video>
                                            <?php else: ?>
                                                <audio controls class="w-100 mb-2">
                                                    <source src="<?php echo BASE_URL . '/' . $media['file_url']; ?>">
                                                </audio>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <span class="badge badge-<?php echo $media['media_type'] === 'photo' ? 'primary' : ($media['media_type'] === 'video' ? 'danger' : 'info'); ?>">
                                                    <?php echo ucfirst($media['media_type']); ?>
                                                </span>
                                                <?php if ($media['is_featured']): ?>
                                                    <span class="badge badge-warning">Featured</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($media['caption']): ?>
                                                <p class="mt-2 mb-1"><small><?php echo htmlspecialchars($media['caption']); ?></small></p>
                                            <?php endif; ?>
                                            
                                            <?php if ($media['source']): ?>
                                                <p class="mb-1"><small class="text-muted">Source: <?php echo htmlspecialchars($media['source']); ?></small></p>
                                            <?php endif; ?>
                                            
                                            <div class="mt-2">
                                                <a href="?case_id=<?php echo $case_id; ?>&toggle_featured=<?php echo $media['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Toggle Featured">
                                                    <i class="fas fa-star"></i>
                                                </a>
                                                <a href="?case_id=<?php echo $case_id; ?>&delete=<?php echo $media['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Delete this media?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('mediaType').addEventListener('change', function() {
    const fileHelp = document.getElementById('fileHelp');
    const helpText = {
        'photo': 'Allowed: JPG, PNG, GIF, WEBP',
        'video': 'Allowed: MP4, AVI, MOV, WEBM',
        'audio': 'Allowed: MP3, WAV, OGG, M4A'
    };
    fileHelp.textContent = helpText[this.value];
});
</script>

<?php include 'includes/footer.php'; ?>
