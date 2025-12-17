<?php
/**
 * Edit Case Thread
 */

require_once 'auth_check.php';

$page_title = 'Edit Case';

$db = Database::getInstance();

$case_id = (int)($_GET['id'] ?? 0);

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $short_description = trim($_POST['short_description'] ?? '');
    $full_description = trim($_POST['full_description'] ?? '');
    $status = $_POST['status'] ?? 'ongoing';
    $category = trim($_POST['category'] ?? '');
    $primary_location = trim($_POST['primary_location'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
    
    // Check if slug exists for other cases
    $existing = $db->fetchOne("SELECT id FROM case_threads WHERE slug = ? AND id != ?", [$slug, $case_id]);
    if ($existing) {
        $errors[] = 'Slug already exists. Please use a different one.';
    }
    
    if (empty($short_description)) {
        $errors[] = 'Short description is required';
    }
    
    if (empty($category)) {
        $errors[] = 'Category is required';
    }
    
    // Handle thumbnail upload
    $thumbnail = $case['thumbnail'];
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cases/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Delete old thumbnail
        if ($thumbnail && file_exists('../' . $thumbnail)) {
            unlink('../' . $thumbnail);
        }
        
        $file_extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $thumbnail = 'case_thumb_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_dir . $thumbnail);
        $thumbnail = 'uploads/cases/' . $thumbnail;
    }
    
    // Handle cover image upload
    $cover_image = $case['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cases/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Delete old cover
        if ($cover_image && file_exists('../' . $cover_image)) {
            unlink('../' . $cover_image);
        }
        
        $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $cover_image = 'case_cover_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $cover_image);
        $cover_image = 'uploads/cases/' . $cover_image;
    }
    
    if (empty($errors)) {
        // Update case
        $db->query("
            UPDATE case_threads SET
                title = ?, slug = ?, short_description = ?, full_description = ?, 
                status = ?, category = ?, primary_location = ?, start_date = ?, 
                end_date = ?, thumbnail = ?, cover_image = ?, meta_title = ?, 
                meta_description = ?, meta_keywords = ?, updated_at = NOW()
            WHERE id = ?
        ", [
            $title, $slug, $short_description, $full_description, $status, $category,
            $primary_location, $start_date, $end_date, $thumbnail, $cover_image,
            $meta_title ?: $title, $meta_description ?: $short_description, $meta_keywords,
            $case_id
        ]);
        
        // Send notification if case status changed to active/published
        if ($status !== 'draft' && $case['status'] !== $status) {
            try {
                require_once INCLUDES_PATH . '/Settings.php';
                require_once INCLUDES_PATH . '/FCMv1HelperLite.php';
                
                if (Settings::get('enable_push_notifications', '0') === '1') {
                    $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
                    $fcm->sendCaseStudyNotification($case_id, $title, $slug, true);
                }
            } catch (Exception $e) {
                error_log('Case Update Notification Error: ' . $e->getMessage());
            }
        }
        
        $_SESSION['success'] = 'Case updated successfully';
        
        // Refresh case data
        $case = $db->fetchOne("SELECT * FROM case_threads WHERE id = ?", [$case_id]);
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Edit Case: <?php echo htmlspecialchars($case['title']); ?></h1>
        <div>
            <a href="cases.php" class="btn btn-secondary">← Back to Cases</a>
            <a href="<?php echo BASE_URL; ?>/case/<?php echo $case['slug']; ?>" class="btn btn-info" target="_blank">
                <i class="fas fa-eye"></i> View
            </a>
        </div>
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

    <!-- Quick Links -->
    <div class="card mb-4">
        <div class="card-body">
            <h5>Manage Case Content</h5>
            <div class="btn-group">
                <a href="case-timeline.php?case_id=<?php echo $case_id; ?>" class="btn btn-info">
                    <i class="fas fa-stream"></i> Timeline Events
                </a>
                <a href="case-documents.php?case_id=<?php echo $case_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-file-alt"></i> Documents
                </a>
                <a href="case-media.php?case_id=<?php echo $case_id; ?>" class="btn btn-warning">
                    <i class="fas fa-images"></i> Media Gallery
                </a>
                <a href="case-articles.php?case_id=<?php echo $case_id; ?>" class="btn btn-success">
                    <i class="fas fa-newspaper"></i> Linked Articles
                </a>
            </div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Title *</label>
                            <input type="text" name="title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($case['title']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Slug *</label>
                            <input type="text" name="slug" class="form-control" required
                                   value="<?php echo htmlspecialchars($case['slug']); ?>">
                            <small class="form-text text-muted">URL-friendly version</small>
                        </div>

                        <div class="form-group">
                            <label>Short Description *</label>
                            <textarea name="short_description" class="form-control" rows="3" required><?php echo htmlspecialchars($case['short_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Full Description</label>
                            <textarea name="full_description" class="form-control" rows="10"><?php echo htmlspecialchars($case['full_description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($case['meta_title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($case['meta_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Meta Keywords</label>
                            <input type="text" name="meta_keywords" class="form-control" 
                                   value="<?php echo htmlspecialchars($case['meta_keywords'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <h3><?php echo number_format($case['total_articles']); ?></h3>
                                <small>Articles</small>
                            </div>
                            <div class="col-4">
                                <h3><?php echo number_format($case['total_followers']); ?></h3>
                                <small>Followers</small>
                            </div>
                            <div class="col-4">
                                <h3><?php echo number_format($case['total_views']); ?></h3>
                                <small>Views</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Classification</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="ongoing" <?php echo $case['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="closed" <?php echo $case['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                <option value="historic" <?php echo $case['status'] === 'historic' ? 'selected' : ''; ?>>Historic</option>
                                <option value="verdict_pending" <?php echo $case['status'] === 'verdict_pending' ? 'selected' : ''; ?>>Verdict Pending</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" class="form-control" required>
                                <option value="crime" <?php echo $case['category'] === 'crime' ? 'selected' : ''; ?>>Crime</option>
                                <option value="war" <?php echo $case['category'] === 'war' ? 'selected' : ''; ?>>War</option>
                                <option value="corporate" <?php echo $case['category'] === 'corporate' ? 'selected' : ''; ?>>Corporate</option>
                                <option value="political" <?php echo $case['category'] === 'political' ? 'selected' : ''; ?>>Political</option>
                                <option value="environmental" <?php echo $case['category'] === 'environmental' ? 'selected' : ''; ?>>Environmental</option>
                                <option value="humanitarian" <?php echo $case['category'] === 'humanitarian' ? 'selected' : ''; ?>>Humanitarian</option>
                                <option value="scam" <?php echo $case['category'] === 'scam' ? 'selected' : ''; ?>>Scam/Fraud</option>
                                <option value="corruption" <?php echo $case['category'] === 'corruption' ? 'selected' : ''; ?>>Corruption</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Primary Location</label>
                            <input type="text" name="primary_location" class="form-control" 
                                   value="<?php echo htmlspecialchars($case['primary_location'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($case['start_date'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($case['end_date'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Thumbnail</label>
                            <?php if ($case['thumbnail']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo BASE_URL . '/' . $case['thumbnail']; ?>" 
                                         class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="thumbnail" class="form-control-file" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label>Cover Image</label>
                            <?php if ($case['cover_image']): ?>
                                <div class="mb-2">
                                    <img src="<?php echo BASE_URL . '/' . $case['cover_image']; ?>" 
                                         class="img-thumbnail" style="max-width: 100%;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="cover_image" class="form-control-file" accept="image/*">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Update Case</button>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
