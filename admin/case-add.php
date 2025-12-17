<?php
/**
 * Add New Case Thread
 */

require_once 'auth_check.php';

$page_title = 'Add New Case';

$db = Database::getInstance();

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
        // Auto-generate slug from title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
    
    // Check if slug exists
    $existing = $db->fetchOne("SELECT id FROM case_threads WHERE slug = ?", [$slug]);
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
    $thumbnail = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cases/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $thumbnail = 'case_thumb_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_dir . $thumbnail);
        $thumbnail = 'uploads/cases/' . $thumbnail;
    }
    
    // Handle cover image upload
    $cover_image = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/cases/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $cover_image = 'case_cover_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_dir . $cover_image);
        $cover_image = 'uploads/cases/' . $cover_image;
    }
    
    if (empty($errors)) {
        // Insert case
        $case_id = $db->insert('case_threads', [
            'title' => $title,
            'slug' => $slug,
            'short_description' => $short_description,
            'full_description' => $full_description,
            'status' => $status,
            'category' => $category,
            'primary_location' => $primary_location,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'thumbnail' => $thumbnail,
            'cover_image' => $cover_image,
            'meta_title' => $meta_title ?: $title,
            'meta_description' => $meta_description ?: $short_description,
            'meta_keywords' => $meta_keywords
        ]);
        
        // Send notification if case is published (not draft)
        if ($status !== 'draft') {
            try {
                require_once INCLUDES_PATH . '/Settings.php';
                require_once INCLUDES_PATH . '/FCMv1HelperLite.php';
                
                if (Settings::get('enable_push_notifications', '0') === '1') {
                    $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
                    $fcm->sendCaseStudyNotification($case_id, $title, $slug, false);
                }
            } catch (Exception $e) {
                error_log('Case Notification Error: ' . $e->getMessage());
            }
        }
        
        $_SESSION['success'] = 'Case created successfully';
        header('Location: case-edit.php?id=' . $case_id);
        exit;
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Add New Case Thread</h1>
        <a href="cases.php" class="btn btn-secondary">← Back to Cases</a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

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
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="slug" class="form-control" 
                                   placeholder="Leave empty to auto-generate from title"
                                   value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
                            <small class="form-text text-muted">URL-friendly version (e.g., nirbhaya-case-delhi-gang-rape)</small>
                        </div>

                        <div class="form-group">
                            <label>Short Description *</label>
                            <textarea name="short_description" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Brief summary (shown in listings)</small>
                        </div>

                        <div class="form-group">
                            <label>Full Description</label>
                            <textarea name="full_description" class="form-control" rows="10"><?php echo htmlspecialchars($_POST['full_description'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Detailed case overview</small>
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
                                   placeholder="Leave empty to use case title"
                                   value="<?php echo htmlspecialchars($_POST['meta_title'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2" 
                                      placeholder="Leave empty to use short description"><?php echo htmlspecialchars($_POST['meta_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Meta Keywords</label>
                            <input type="text" name="meta_keywords" class="form-control" 
                                   placeholder="keyword1, keyword2, keyword3"
                                   value="<?php echo htmlspecialchars($_POST['meta_keywords'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Classification</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" class="form-control" required>
                                <option value="ongoing" <?php echo ($_POST['status'] ?? '') === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                <option value="closed" <?php echo ($_POST['status'] ?? '') === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                <option value="historic" <?php echo ($_POST['status'] ?? '') === 'historic' ? 'selected' : ''; ?>>Historic</option>
                                <option value="verdict_pending" <?php echo ($_POST['status'] ?? '') === 'verdict_pending' ? 'selected' : ''; ?>>Verdict Pending</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="crime" <?php echo ($_POST['category'] ?? '') === 'crime' ? 'selected' : ''; ?>>Crime</option>
                                <option value="war" <?php echo ($_POST['category'] ?? '') === 'war' ? 'selected' : ''; ?>>War</option>
                                <option value="corporate" <?php echo ($_POST['category'] ?? '') === 'corporate' ? 'selected' : ''; ?>>Corporate</option>
                                <option value="political" <?php echo ($_POST['category'] ?? '') === 'political' ? 'selected' : ''; ?>>Political</option>
                                <option value="environmental" <?php echo ($_POST['category'] ?? '') === 'environmental' ? 'selected' : ''; ?>>Environmental</option>
                                <option value="humanitarian" <?php echo ($_POST['category'] ?? '') === 'humanitarian' ? 'selected' : ''; ?>>Humanitarian</option>
                                <option value="scam" <?php echo ($_POST['category'] ?? '') === 'scam' ? 'selected' : ''; ?>>Scam/Fraud</option>
                                <option value="corruption" <?php echo ($_POST['category'] ?? '') === 'corruption' ? 'selected' : ''; ?>>Corruption</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Primary Location</label>
                            <input type="text" name="primary_location" class="form-control" 
                                   placeholder="e.g., New Delhi, India"
                                   value="<?php echo htmlspecialchars($_POST['primary_location'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                            <small class="form-text text-muted">Leave empty for ongoing cases</small>
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
                            <input type="file" name="thumbnail" class="form-control-file" accept="image/*">
                            <small class="form-text text-muted">Recommended: 400x300px</small>
                        </div>

                        <div class="form-group">
                            <label>Cover Image</label>
                            <input type="file" name="cover_image" class="form-control-file" accept="image/*">
                            <small class="form-text text-muted">Recommended: 1920x600px</small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">Create Case</button>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
