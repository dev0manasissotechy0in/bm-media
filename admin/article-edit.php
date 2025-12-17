<?php
/**
 * Edit Article
 */

require_once 'auth_check.php';

$page_title = 'Edit Article';

$db = Database::getInstance();

// Get article ID
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$article_id) {
    $_SESSION['error'] = 'Invalid article ID';
    header('Location: articles.php');
    exit;
}

// Get article details
$article = $db->fetchOne("SELECT * FROM articles WHERE id = ?", [$article_id]);

if (!$article) {
    $_SESSION['error'] = 'Article not found';
    header('Location: articles.php');
    exit;
}

$errors = [];
$form_data = $article;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $content = $_POST['content'] ?? '';
    
    // Handle parent/subcategory logic
    $parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;
    $subcategory_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    
    // If subcategory is selected, use it; otherwise use parent category
    $category_id = $subcategory_id ?: $parent_category_id;
    
    $content_type = $_POST['content_type'] ?? 'standard';
    $status = $_POST['status'] ?? 'draft';
    
    // Flags
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_top_news = isset($_POST['is_top_news']) ? 1 : 0;
    $is_breaking = isset($_POST['is_breaking']) ? 1 : 0;
    $is_live = isset($_POST['is_live']) ? 1 : 0;
    
    // SEO fields
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    
    // Tags
    $tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    // Delete flags
    $delete_thumbnail = isset($_POST['delete_thumbnail']) ? 1 : 0;
    
    $form_data = $_POST;
    
    if (empty($title)) {
        $errors[] = 'Article title is required';
    }
    
    if (empty($parent_category_id)) {
        $errors[] = 'Parent category is required';
    }
    
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $title));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    }
    
    // Check slug uniqueness (excluding current article)
    $existing = $db->fetchOne("SELECT id FROM articles WHERE slug = ? AND id != ?", [$slug, $article_id]);
    if ($existing) {
        $errors[] = 'Slug already exists. Please use a different slug.';
    }
    
    // Handle thumbnail upload or deletion
    $thumbnail = $article['thumbnail'];
    $thumbnail_alt = trim($_POST['thumbnail_alt'] ?? '');
    
    if ($delete_thumbnail && $thumbnail) {
        $file_path = UPLOAD_ARTICLE_PATH . '/' . $thumbnail;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $thumbnail = null;
    }
    
    if (!empty($_FILES['thumbnail']['name'])) {
        $file_info = pathinfo($_FILES['thumbnail']['name']);
        $file_ext = strtolower($file_info['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed)) {
            // Delete old thumbnail if exists
            if ($thumbnail) {
                $old_file = UPLOAD_ARTICLE_PATH . '/' . $thumbnail;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            $thumbnail = time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = UPLOAD_ARTICLE_PATH . '/' . $thumbnail;
            
            if (!move_uploaded_file($_FILES['thumbnail']['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to upload thumbnail';
            }
        } else {
            $errors[] = 'Invalid image format. Allowed: ' . implode(', ', $allowed);
        }
    }
    
    // Handle Media Content based on content_type
    $media_video_url = $article['media_video_url'];
    $media_video_file = $article['media_video_file'];
    $media_reel_url = $article['media_reel_url'];
    $media_reel_file = $article['media_reel_file'];
    $media_gallery_images = $article['media_gallery_images'];
    
    if ($content_type === 'video') {
        $video_source = $_POST['video_source'] ?? 'url';
        
        if ($video_source === 'url') {
            $media_video_url = trim($_POST['media_video_url'] ?? '');
            // Clear file if switching to URL
            if ($media_video_file && !empty($media_video_url)) {
                $old_file = UPLOAD_ARTICLE_PATH . '/' . $media_video_file;
                if (file_exists($old_file)) unlink($old_file);
                $media_video_file = null;
            }
        } else {
            // Handle video file upload
            if (!empty($_FILES['media_video_file']['name'])) {
                $file_info = pathinfo($_FILES['media_video_file']['name']);
                $file_ext = strtolower($file_info['extension']);
                $allowed_video = ['mp4', 'webm', 'mov', 'avi'];
                
                if (in_array($file_ext, $allowed_video)) {
                    // Delete old video file
                    if ($media_video_file) {
                        $old_file = UPLOAD_ARTICLE_PATH . '/' . $media_video_file;
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    
                    $media_video_file = 'video_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = UPLOAD_ARTICLE_PATH . '/' . $media_video_file;
                    
                    if (move_uploaded_file($_FILES['media_video_file']['tmp_name'], $upload_path)) {
                        $media_video_url = null; // Clear URL if using file
                    } else {
                        $errors[] = 'Failed to upload video file';
                    }
                } else {
                    $errors[] = 'Invalid video format. Allowed: MP4, WEBM, MOV, AVI';
                }
            }
        }
    } elseif ($content_type === 'reel') {
        $reel_source = $_POST['reel_source'] ?? 'url';
        
        if ($reel_source === 'url') {
            $media_reel_url = trim($_POST['media_reel_url'] ?? '');
            // Clear file if switching to URL
            if ($media_reel_file && !empty($media_reel_url)) {
                $old_file = UPLOAD_ARTICLE_PATH . '/' . $media_reel_file;
                if (file_exists($old_file)) unlink($old_file);
                $media_reel_file = null;
            }
        } else {
            // Handle reel file upload
            if (!empty($_FILES['media_reel_file']['name'])) {
                $file_info = pathinfo($_FILES['media_reel_file']['name']);
                $file_ext = strtolower($file_info['extension']);
                $allowed_video = ['mp4', 'webm', 'mov'];
                
                if (in_array($file_ext, $allowed_video)) {
                    // Delete old reel file
                    if ($media_reel_file) {
                        $old_file = UPLOAD_ARTICLE_PATH . '/' . $media_reel_file;
                        if (file_exists($old_file)) unlink($old_file);
                    }
                    
                    $media_reel_file = 'reel_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = UPLOAD_ARTICLE_PATH . '/' . $media_reel_file;
                    
                    if (move_uploaded_file($_FILES['media_reel_file']['tmp_name'], $upload_path)) {
                        $media_reel_url = null; // Clear URL if using file
                    } else {
                        $errors[] = 'Failed to upload reel file';
                    }
                } else {
                    $errors[] = 'Invalid reel format. Allowed: MP4, WEBM, MOV';
                }
            }
        }
    } elseif ($content_type === 'gallery') {
        // Handle multiple gallery images
        $gallery_type = $_POST['gallery_type'] ?? 'simple';
        $gallery_meta_titles = $_POST['gallery_meta_title'] ?? [];
        $gallery_meta_descriptions = $_POST['gallery_meta_description'] ?? [];
        
        if (!empty($_FILES['media_gallery_images']['name'][0])) {
            $existing_images = $media_gallery_images ? json_decode($media_gallery_images, true) : [];
            $gallery_images = $existing_images;
            $upload_dir = UPLOAD_ARTICLE_PATH . '/';
            
            foreach ($_FILES['media_gallery_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['media_gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_info = pathinfo($_FILES['media_gallery_images']['name'][$key]);
                    $file_ext = strtolower($file_info['extension']);
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_ext, $allowed)) {
                        $filename = 'gallery_' . time() . '_' . uniqid() . '_' . $key . '.' . $file_ext;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($tmp_name, $upload_path)) {
                            $gallery_images[] = $filename;
                            
                            // Save metadata for detailed gallery
                            if ($gallery_type === 'detailed') {
                                $img_title = $gallery_meta_titles[$key] ?? '';
                                $img_description = $gallery_meta_descriptions[$key] ?? '';
                                
                                // Insert metadata after article is updated
                                $metadata_to_save[] = [
                                    'filename' => $filename,
                                    'title' => $img_title,
                                    'description' => $img_description,
                                    'order_id' => count($gallery_images) - 1
                                ];
                            }
                        }
                    }
                }
            }
            
            $media_gallery_images = json_encode($gallery_images);
        }
    }
    
    if (empty($errors)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'description' => $description,
            'content' => $content,
            'thumbnail' => $thumbnail,
            'thumbnail_alt' => $thumbnail_alt,
            'category_id' => $category_id,
            'content_type' => $content_type,
            'gallery_type' => $gallery_type ?? 'simple',
            'media_video_url' => $media_video_url,
            'media_video_file' => $media_video_file,
            'media_reel_url' => $media_reel_url,
            'media_reel_file' => $media_reel_file,
            'media_gallery_images' => $media_gallery_images,
            'is_featured' => $is_featured,
            'is_top_news' => $is_top_news,
            'is_breaking' => $is_breaking,
            'is_live' => $is_live,
            'status' => $status,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_keywords' => $seo_keywords,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Update published_at if status changed to published
        if ($status === 'published' && $article['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        $success = $db->update('articles', $data, 'id = ?', [$article_id]);
        
        if ($success) {
            // Save gallery metadata if detailed gallery
            if ($content_type === 'gallery' && ($gallery_type ?? 'simple') === 'detailed' && !empty($metadata_to_save)) {
                foreach ($metadata_to_save as $meta) {
                    $db->insert('article_gallery_metadata', [
                        'article_id' => $article_id,
                        'image_filename' => $meta['filename'],
                        'title' => $meta['title'],
                        'description' => $meta['description'],
                        'order_id' => $meta['order_id']
                    ]);
                }
            }
            
            // Update tags - delete old and insert new
            $db->query("DELETE FROM article_tags WHERE article_id = ?", [$article_id]);
            
            if (!empty($tags)) {
                foreach ($tags as $tag_id) {
                    $db->insert('article_tags', [
                        'article_id' => $article_id,
                        'tag_id' => $tag_id
                    ]);
                }
            }
            
            // Send FCM notification if article is newly published (status changed from draft to published)
            if ($status === 'published' && $article['status'] !== 'published') {
                try {
                    require_once INCLUDES_PATH . '/Settings.php';
                    require_once INCLUDES_PATH . '/FCMv1HelperLite.php';
                    
                    if (Settings::get('enable_push_notifications', '0') === '1') {
                        $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
                        $imageUrl = !empty($featured_image) ? SITE_URL . '/uploads/articles/' . $featured_image : null;
                        
                        if ($is_breaking) {
                            $fcm->sendBreakingNewsNotification($article_id, $title, $slug, $imageUrl);
                        } elseif ($is_live) {
                            $fcm->sendLiveNewsNotification($article_id, $title, $slug, $imageUrl);
                        } else {
                            $categoryName = '';
                            if ($category_id) {
                                $cat = $db->fetchOne("SELECT name FROM categories WHERE id = ?", [$category_id]);
                                $categoryName = $cat['name'] ?? '';
                            }
                            
                            $data = [
                                'type' => 'article',
                                'notification_type' => 'article',
                                'article_id' => (string)$article_id,
                                'article_slug' => $slug,
                                'route' => '/article/' . $slug
                            ];
                            
                            $options = [
                                'channel_id' => 'article',
                                'badge' => '📰 New Article',
                                'priority' => 'default',
                                'image' => $imageUrl,
                                'actions' => [
                                    ['action' => 'open', 'title' => 'Read Now'],
                                    ['action' => 'save', 'title' => 'Save'],
                                    ['action' => 'share', 'title' => 'Share']
                                ]
                            ];
                            
                            $notifTitle = $categoryName ? "New in $categoryName" : 'New Article';
                            $fcm->sendToTopic('all', $notifTitle, $title, $data, $options);
                        }
                    }
                } catch (Exception $e) {
                    error_log('Notification Error: ' . $e->getMessage());
                }
            }
            
            $_SESSION['success'] = 'Article updated successfully';
            header('Location: articles.php');
            exit;
        } else {
            $errors[] = 'Failed to update article';
        }
    }
}

// Get parent categories for dropdown
$parent_categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' AND parent_id IS NULL ORDER BY name ASC");

// Get tags for selection
$all_tags = $db->fetchAll("SELECT id, name FROM tags ORDER BY name ASC");

// Get current article tags
$current_tags = $db->fetchAll("SELECT tag_id FROM article_tags WHERE article_id = ?", [$article_id]);
$current_tag_ids = array_column($current_tags, 'tag_id');

// Determine parent and subcategory
$current_category = null;
$current_parent_id = null;
$current_subcategories = [];

if ($article['category_id']) {
    $current_category = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$article['category_id']]);
    if ($current_category) {
        if ($current_category['parent_id']) {
            // Article is in a subcategory
            $current_parent_id = $current_category['parent_id'];
            $current_subcategories = $db->fetchAll(
                "SELECT id, name FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY order_id ASC",
                [$current_parent_id]
            );
        } else {
            // Article is in a parent category
            $current_parent_id = $current_category['id'];
            $current_subcategories = $db->fetchAll(
                "SELECT id, name FROM categories WHERE parent_id = ? AND status = 'active' ORDER BY order_id ASC",
                [$current_parent_id]
            );
        }
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square"></i> Edit Article
            </h1>
            <div>
                <?php if ($article['is_live']): ?>
                <a href="live-updates.php?article_id=<?= $article_id ?>" class="btn btn-danger me-2">
                    <i class="bi bi-broadcast"></i> Manage Live Updates
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/article/<?= $article['slug'] ?>" class="btn btn-info me-2" target="_blank">
                    <i class="bi bi-eye"></i> View Article
                </a>
                <a href="articles.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Articles
                </a>
            </div>
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

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Article Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       value="<?= htmlspecialchars($form_data['title'] ?? '') ?>"
                                       placeholder="Enter article title">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control"
                                       value="<?= htmlspecialchars($form_data['slug'] ?? '') ?>"
                                       placeholder="Auto-generated if empty">
                                <small class="text-muted">URL-friendly version. Leave empty for auto-generation.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description/Summary</label>
                                <textarea name="description" class="form-control" rows="3"
                                          placeholder="Brief summary of the article"><?= htmlspecialchars($form_data['description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea name="content" id="articleContent" class="form-control" rows="15"><?= htmlspecialchars($form_data['content'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-search"></i> SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">SEO Title</label>
                                <input type="text" name="seo_title" class="form-control" maxlength="255"
                                       value="<?= htmlspecialchars($form_data['seo_title'] ?? '') ?>"
                                       placeholder="Custom title for search engines">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Description</label>
                                <textarea name="seo_description" class="form-control" rows="3" maxlength="500"
                                          placeholder="Description for search engines"><?= htmlspecialchars($form_data['seo_description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Keywords</label>
                                <input type="text" name="seo_keywords" class="form-control"
                                       value="<?= htmlspecialchars($form_data['seo_keywords'] ?? '') ?>"
                                       placeholder="keyword1, keyword2, keyword3">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Publish Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Publish</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= $article['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Parent Category <span class="text-danger">*</span></label>
                                <select name="parent_category_id" id="parentCategory" class="form-select" required>
                                    <option value="">-- Select Parent Category --</option>
                                    <?php foreach ($parent_categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $current_parent_id == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="subcategoryWrapper" style="<?= !empty($current_subcategories) ? '' : 'display: none;' ?>">
                                <label class="form-label">Sub Category <small class="text-muted">(Optional)</small></label>
                                <select name="category_id" id="subCategory" class="form-select">
                                    <option value="">-- Select Sub Category --</option>
                                    <?php foreach ($current_subcategories as $sub): ?>
                                    <option value="<?= $sub['id'] ?>" <?= $article['category_id'] == $sub['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Content Type</label>
                                <select name="content_type" class="form-select">
                                    <option value="standard" <?= $article['content_type'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                                    <option value="video" <?= $article['content_type'] === 'video' ? 'selected' : '' ?>>Video</option>
                                    <option value="reel" <?= $article['content_type'] === 'reel' ? 'selected' : '' ?>>Reel</option>
                                    <option value="photo" <?= $article['content_type'] === 'photo' ? 'selected' : '' ?>>Photo</option>
                                    <option value="gallery" <?= $article['content_type'] === 'gallery' ? 'selected' : '' ?>>Gallery</option>
                                </select>
                            </div>
                            
                            <?php if ($article['published_at']): ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> Published: <?= date('M j, Y g:i A', strtotime($article['published_at'])) ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Featured Image (Thumbnail)</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($article['thumbnail']): ?>
                            <div class="mb-3">
                                <img src="<?= UPLOADS_URL ?>/articles/<?= $article['thumbnail'] ?>" 
                                     class="img-thumbnail mb-2" style="max-width: 100%;" 
                                     alt="Current thumbnail">
                                <div class="form-check">
                                    <input type="checkbox" name="delete_thumbnail" class="form-check-input" id="deleteThumbnail" value="1">
                                    <label class="form-check-label text-danger" for="deleteThumbnail">
                                        Delete current image
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label">Upload New Thumbnail</label>
                                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image Alt Text</label>
                                <input type="text" name="thumbnail_alt" class="form-control"
                                       value="<?= htmlspecialchars($article['thumbnail_alt'] ?? '') ?>"
                                       placeholder="Alt text for SEO">
                            </div>
                            <div id="thumbnail-preview"></div>
                        </div>
                    </div>

                    <!-- Media Content (Dynamic based on Media Type) -->
                    <div class="card mb-4" id="mediaContentCard" style="display: <?= in_array($article['content_type'], ['video', 'reel', 'gallery']) ? 'block' : 'none' ?>;">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-collection-play"></i> Media Content</h5>
                        </div>
                        <div class="card-body">
                            <!-- Video Content -->
                            <div id="videoContent" style="display: <?= $article['content_type'] === 'video' ? 'block' : 'none' ?>;">
                                <div class="mb-3">
                                    <label class="form-label">Video Source</label>
                                    <div class="btn-group w-100 mb-2" role="group">
                                        <input type="radio" class="btn-check" name="video_source" id="video_url_option" value="url" 
                                               <?= !empty($article['media_video_url']) || empty($article['media_video_file']) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="video_url_option">
                                            <i class="bi bi-link-45deg"></i> Video URL
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="video_source" id="video_file_option" value="file"
                                               <?= !empty($article['media_video_file']) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="video_file_option">
                                            <i class="bi bi-upload"></i> Upload Video
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="video_url_input" style="display: <?= !empty($article['media_video_url']) || empty($article['media_video_file']) ? 'block' : 'none' ?>;">
                                    <label class="form-label">Video URL</label>
                                    <input type="url" class="form-control" name="media_video_url" 
                                           value="<?= htmlspecialchars($article['media_video_url'] ?? '') ?>"
                                           placeholder="https://youtube.com/watch?v=... or direct URL">
                                    <small class="text-muted">YouTube, Vimeo, or direct video URL supported</small>
                                </div>
                                
                                <div class="mb-3" id="video_file_input" style="display: <?= !empty($article['media_video_file']) ? 'block' : 'none' ?>;">
                                    <?php if (!empty($article['media_video_file'])): ?>
                                    <div class="alert alert-info">
                                        Current: <strong><?= htmlspecialchars($article['media_video_file']) ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    <label class="form-label">Upload New Video File</label>
                                    <input type="file" class="form-control" name="media_video_file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                                    <small class="text-muted">MP4, WEBM, MOV, AVI (Max: 100MB)</small>
                                </div>
                            </div>

                            <!-- Reel Content -->
                            <div id="reelContent" style="display: <?= $article['content_type'] === 'reel' ? 'block' : 'none' ?>;">
                                <div class="mb-3">
                                    <label class="form-label">Reel Source</label>
                                    <div class="btn-group w-100 mb-2" role="group">
                                        <input type="radio" class="btn-check" name="reel_source" id="reel_url_option" value="url"
                                               <?= !empty($article['media_reel_url']) || empty($article['media_reel_file']) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="reel_url_option">
                                            <i class="bi bi-link-45deg"></i> Reel URL
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="reel_source" id="reel_file_option" value="file"
                                               <?= !empty($article['media_reel_file']) ? 'checked' : '' ?>>
                                        <label class="btn btn-outline-primary" for="reel_file_option">
                                            <i class="bi bi-upload"></i> Upload Reel
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="reel_url_input" style="display: <?= !empty($article['media_reel_url']) || empty($article['media_reel_file']) ? 'block' : 'none' ?>;">
                                    <label class="form-label">Reel URL</label>
                                    <input type="url" class="form-control" name="media_reel_url" 
                                           value="<?= htmlspecialchars($article['media_reel_url'] ?? '') ?>"
                                           placeholder="https://youtube.com/shorts/... or direct URL">
                                    <small class="text-muted">Short video URL (YouTube Shorts, Instagram Reels format)</small>
                                </div>
                                
                                <div class="mb-3" id="reel_file_input" style="display: <?= !empty($article['media_reel_file']) ? 'block' : 'none' ?>;">
                                    <?php if (!empty($article['media_reel_file'])): ?>
                                    <div class="alert alert-info">
                                        Current: <strong><?= htmlspecialchars($article['media_reel_file']) ?></strong>
                                    </div>
                                    <?php endif; ?>
                                    <label class="form-label">Upload New Reel File</label>
                                    <input type="file" class="form-control" name="media_reel_file" accept="video/mp4,video/webm,video/quicktime">
                                    <small class="text-muted">MP4, WEBM, MOV (Max: 50MB, 60 seconds recommended)</small>
                                </div>
                            </div>

                            <!-- Gallery Content -->
                            <div id="galleryContent" style="display: <?= $article['content_type'] === 'gallery' ? 'block' : 'none' ?>;">
                                <div class="mb-3">
                                    <label class="form-label">Gallery Type</label>
                                    <select class="form-select" name="gallery_type" id="galleryType">
                                        <option value="simple" <?= ($article['gallery_type'] ?? 'simple') === 'simple' ? 'selected' : '' ?>>
                                            Simple Slider (Photos only - Thumbnail becomes slider)
                                        </option>
                                        <option value="detailed" <?= ($article['gallery_type'] ?? 'simple') === 'detailed' ? 'selected' : '' ?>>
                                            Detailed Gallery (Photos with title & description)
                                        </option>
                                    </select>
                                    <small class="text-muted">Simple: Quick photo slider. Detailed: Each photo has title and description.</small>
                                </div>
                                
                                <?php if (!empty($article['media_gallery_images'])): 
                                    $gallery_images = json_decode($article['media_gallery_images'], true);
                                    if ($gallery_images):
                                ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Gallery Images</label>
                                    <div class="row g-2">
                                        <?php foreach ($gallery_images as $img): ?>
                                        <div class="col-md-3 col-sm-4 col-6">
                                            <img src="<?= UPLOADS_URL ?>/articles/<?= htmlspecialchars($img) ?>" 
                                                 class="img-thumbnail" style="width: 100%; height: 120px; object-fit: cover;">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endif; 
                                ?>
                                <div class="mb-3">
                                    <label class="form-label">Add More Gallery Images</label>
                                    <input type="file" class="form-control" name="media_gallery_images[]" 
                                           accept="image/*" multiple id="galleryImagesInput">
                                    <small class="text-muted">Select multiple images to add to the gallery (JPG, PNG, GIF, WEBP)</small>
                                </div>
                                <div id="gallery-preview" class="row g-2 mt-3"></div>
                                
                                <!-- Metadata for Detailed Gallery -->
                                <div id="galleryMetadataSection" style="display: <?= ($article['gallery_type'] ?? 'simple') === 'detailed' ? 'block' : 'none' ?>;">
                                    <hr class="my-4">
                                    <h6 class="mb-3">Add Titles & Descriptions for New Images</h6>
                                    <div id="gallery-metadata-inputs"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-tags"></i> Tags</h5>
                        </div>
                        <div class="card-body">
                            <select name="tags[]" class="form-select" multiple size="8">
                                <?php foreach ($all_tags as $tag): ?>
                                <option value="<?= $tag['id'] ?>" <?= in_array($tag['id'], $current_tag_ids) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tag['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple tags</small>
                        </div>
                    </div>

                    <!-- Article Flags -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Article Flags</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured" value="1" <?= $article['is_featured'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isFeatured">
                                    <i class="bi bi-star-fill text-warning"></i> Featured
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_top_news" class="form-check-input" id="isTopNews" value="1" <?= $article['is_top_news'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isTopNews">
                                    <i class="bi bi-arrow-up-circle-fill text-primary"></i> Top News
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_breaking" class="form-check-input" id="isBreaking" value="1" <?= $article['is_breaking'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isBreaking">
                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> Breaking News
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_live" class="form-check-input" id="isLive" value="1" <?= $article['is_live'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="isLive">
                                    <i class="bi bi-broadcast text-danger"></i> Live
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="bi bi-eye"></i> Views:</span>
                                <strong><?= number_format($article['views_count']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="bi bi-heart"></i> Likes:</span>
                                <strong><?= number_format($article['likes_count']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="bi bi-chat"></i> Comments:</span>
                                <strong><?= number_format($article['comments_count']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-download"></i> Downloads:</span>
                                <strong><?= number_format($article['downloads_count']) ?></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Update Article
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- TinyMCE HTML Editor (Free Version) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
// Initialize TinyMCE
tinymce.init({
    selector: '#articleContent',
    height: 500,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media table | code fullscreen | help',
    menubar: 'file edit view insert format tools table help',
    promotion: false,
    branding: false,
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
    // Image upload settings
    images_upload_url: 'upload_image.php',
    automatic_uploads: true,
    images_reuse_filename: true,
    // Paste settings
    paste_data_images: true,
    // Link settings
    link_default_target: '_blank',
    link_assume_external_targets: true,
    // Table settings
    table_default_attributes: {
        class: 'table table-bordered'
    },
    table_default_styles: {
        width: '100%'
    }
});

// Image preview
if (document.querySelector('input[name="thumbnail"]')) {
    document.querySelector('input[name="thumbnail"]').addEventListener('change', function(e) {
        const preview = document.getElementById('thumbnail-preview');
        preview.innerHTML = '';
        
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(evt) {
                preview.innerHTML = '<img src="' + evt.target.result + '" class="img-thumbnail" style="max-width: 100%;">';
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Auto-generate slug
if (document.querySelector('input[name="title"]')) {
    document.querySelector('input[name="title"]').addEventListener('input', function(e) {
        const slugInput = document.querySelector('input[name="slug"]');
        if (slugInput && !slugInput.dataset.manual) {
            let slug = e.target.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            slugInput.value = slug;
        }
    });
}

if (document.querySelector('input[name="slug"]')) {
    document.querySelector('input[name="slug"]').addEventListener('input', function() {
        this.dataset.manual = 'true';
    });
}

// Media Type change handler - show/hide media content sections
document.querySelector('select[name="content_type"]')?.addEventListener('change', function() {
    const mediaType = this.value;
    const mediaCard = document.getElementById('mediaContentCard');
    const videoContent = document.getElementById('videoContent');
    const reelContent = document.getElementById('reelContent');
    const galleryContent = document.getElementById('galleryContent');
    
    // Hide all sections first
    videoContent.style.display = 'none';
    reelContent.style.display = 'none';
    galleryContent.style.display = 'none';
    mediaCard.style.display = 'none';
    
    // Show relevant section based on media type
    if (mediaType === 'video') {
        mediaCard.style.display = 'block';
        videoContent.style.display = 'block';
    } else if (mediaType === 'reel') {
        mediaCard.style.display = 'block';
        reelContent.style.display = 'block';
    } else if (mediaType === 'gallery') {
        mediaCard.style.display = 'block';
        galleryContent.style.display = 'block';
    }
});

// Video source toggle
document.getElementById('video_url_option')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('video_url_input').style.display = 'block';
        document.getElementById('video_file_input').style.display = 'none';
    }
});

document.getElementById('video_file_option')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('video_url_input').style.display = 'none';
        document.getElementById('video_file_input').style.display = 'block';
    }
});

// Reel source toggle
document.getElementById('reel_url_option')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('reel_url_input').style.display = 'block';
        document.getElementById('reel_file_input').style.display = 'none';
    }
});

document.getElementById('reel_file_option')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('reel_url_input').style.display = 'none';
        document.getElementById('reel_file_input').style.display = 'block';
    }
});

// Gallery images preview
document.querySelector('input[name="media_gallery_images[]"]')?.addEventListener('change', function(e) {
    const preview = document.getElementById('gallery-preview');
    const metadataInputs = document.getElementById('gallery-metadata-inputs');
    preview.innerHTML = '';
    metadataInputs.innerHTML = '';
    
    if (this.files) {
        Array.from(this.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-md-3 col-sm-4 col-6';
                col.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="width: 100%; height: 150px; object-fit: cover;">
                    <small class="d-block text-center mt-1">Image ${index + 1}</small>`;
                preview.appendChild(col);
            }
            reader.readAsDataURL(file);
            
            // Add metadata inputs for detailed gallery
            const metadataCard = document.createElement('div');
            metadataCard.className = 'card mb-3';
            metadataCard.innerHTML = `
                <div class="card-body">
                    <h6 class="card-title">Image ${index + 1}: ${file.name}</h6>
                    <div class="mb-2">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="gallery_meta_title[]" 
                               placeholder="Photo title (optional)">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="gallery_meta_description[]" 
                                  rows="2" placeholder="Photo description (optional)"></textarea>
                    </div>
                </div>
            `;
            metadataInputs.appendChild(metadataCard);
        });
    }
});

// Gallery type toggle
document.getElementById('galleryType')?.addEventListener('change', function() {
    const metadataSection = document.getElementById('galleryMetadataSection');
    if (this.value === 'detailed') {
        metadataSection.style.display = 'block';
    } else {
        metadataSection.style.display = 'none';
    }
});

// Load subcategories based on parent category
const parentCategoryEl = document.getElementById('parentCategory');
if (parentCategoryEl) {
    parentCategoryEl.addEventListener('change', function() {
        const parentId = this.value;
        const subcategoryWrapper = document.getElementById('subcategoryWrapper');
        const subcategorySelect = document.getElementById('subCategory');
        
        if (!subcategoryWrapper || !subcategorySelect) return;
        
        if (!parentId) {
            subcategoryWrapper.style.display = 'none';
            subcategorySelect.innerHTML = '<option value="">-- Select Sub Category --</option>';
            return;
        }
        
        // Fetch subcategories via AJAX
        fetch('get_subcategories.php?parent_id=' + parentId)
            .then(response => response.json())
            .then(data => {
                subcategorySelect.innerHTML = '<option value="">-- Select Sub Category --</option>';
                
                if (data.subcategories && data.subcategories.length > 0) {
                    data.subcategories.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub.id;
                        option.textContent = sub.name;
                        subcategorySelect.appendChild(option);
                    });
                    subcategoryWrapper.style.display = 'block';
                } else {
                    subcategoryWrapper.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error loading subcategories:', error);
                subcategoryWrapper.style.display = 'none';
            });
    });
}

// Initialize TinyMCE
tinymce.init({
    selector: '#articleContent',
    height: 500,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media table | code fullscreen | help',
    menubar: 'file edit view insert format tools table help',
    promotion: false,
    branding: false,
    content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
    // Image upload settings
    images_upload_url: 'upload_image.php',
    automatic_uploads: true,
    images_reuse_filename: true,
    // Paste settings
    paste_data_images: true,
    // Link settings
    link_default_target: '_blank',
    link_assume_external_targets: true,
    // Table settings
    table_default_attributes: {
        class: 'table table-bordered'
    },
    table_default_styles: {
        width: '100%'
    }
});
</script>

<?php include 'includes/footer.php'; ?>
