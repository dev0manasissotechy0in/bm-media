<?php
/**
 * Add New Article
 */

require_once 'auth_check.php';

$page_title = 'Add Article';

$db = Database::getInstance();

$errors = [];
$form_data = [];

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
    
    $content_type_id = !empty($_POST['content_type_id']) ? (int)$_POST['content_type_id'] : null;
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
    
    // Check slug uniqueness
    $existing = $db->fetchOne("SELECT id FROM articles WHERE slug = ?", [$slug]);
    if ($existing) {
        $errors[] = 'Slug already exists. Please use a different slug.';
    }
    
    // Handle thumbnail upload
    $thumbnail = null;
    $thumbnail_alt = trim($_POST['thumbnail_alt'] ?? '');
    
    if (!empty($_FILES['thumbnail']['name'])) {
        $file_info = pathinfo($_FILES['thumbnail']['name']);
        $file_ext = strtolower($file_info['extension']);
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed)) {
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
    $media_video_url = null;
    $media_video_file = null;
    $media_reel_url = null;
    $media_reel_file = null;
    $media_gallery_images = null;
    
    if ($content_type === 'video') {
        $video_source = $_POST['video_source'] ?? 'url';
        
        if ($video_source === 'url') {
            $media_video_url = trim($_POST['media_video_url'] ?? '');
        } else {
            // Handle video file upload
            if (!empty($_FILES['media_video_file']['name'])) {
                $file_info = pathinfo($_FILES['media_video_file']['name']);
                $file_ext = strtolower($file_info['extension']);
                $allowed_video = ['mp4', 'webm', 'mov', 'avi'];
                
                if (in_array($file_ext, $allowed_video)) {
                    $media_video_file = 'video_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = UPLOAD_ARTICLE_PATH . '/' . $media_video_file;
                    
                    if (!move_uploaded_file($_FILES['media_video_file']['tmp_name'], $upload_path)) {
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
        } else {
            // Handle reel file upload
            if (!empty($_FILES['media_reel_file']['name'])) {
                $file_info = pathinfo($_FILES['media_reel_file']['name']);
                $file_ext = strtolower($file_info['extension']);
                $allowed_video = ['mp4', 'webm', 'mov'];
                
                if (in_array($file_ext, $allowed_video)) {
                    $media_reel_file = 'reel_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $upload_path = UPLOAD_ARTICLE_PATH . '/' . $media_reel_file;
                    
                    if (!move_uploaded_file($_FILES['media_reel_file']['tmp_name'], $upload_path)) {
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
        $metadata_to_save = [];
        
        if (!empty($_FILES['media_gallery_images']['name'][0])) {
            $gallery_images = [];
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
            
            if (!empty($gallery_images)) {
                $media_gallery_images = json_encode($gallery_images);
            }
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
            'content_type_id' => $content_type_id,
            'content_type' => $content_type,
            'gallery_type' => $gallery_type ?? 'simple',
            'media_video_url' => $media_video_url,
            'media_video_file' => $media_video_file,
            'media_reel_url' => $media_reel_url,
            'media_reel_file' => $media_reel_file,
            'media_gallery_images' => $media_gallery_images,
            'author_id' => $admin_id,
            'author_type' => 'admin',
            'is_featured' => $is_featured,
            'is_top_news' => $is_top_news,
            'is_breaking' => $is_breaking,
            'is_live' => $is_live,
            'status' => $status,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_keywords' => $seo_keywords,
            'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $article_id = $db->insert('articles', $data);
        
        if ($article_id) {
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
            
            // Insert tags
            if (!empty($tags)) {
                foreach ($tags as $tag_id) {
                    $db->insert('article_tags', [
                        'article_id' => $article_id,
                        'tag_id' => $tag_id
                    ]);
                }
            }
            
            // Send FCM notification if article is published
            if ($status === 'published') {
                try {
                    require_once INCLUDES_PATH . '/Settings.php';
                    require_once INCLUDES_PATH . '/FCMv1HelperLite.php';
                    
                    // Check if push notifications are enabled
                    if (Settings::get('enable_push_notifications', '0') === '1') {
                        $fcm = new FCMv1HelperLite('brackoddmedia-56b89');
                        $imageUrl = !empty($featured_image) ? SITE_URL . '/uploads/articles/' . $featured_image : null;
                        
                        // Send notification based on article type
                        if ($is_breaking) {
                            $fcm->sendBreakingNewsNotification($article_id, $title, $slug, $imageUrl);
                        } elseif ($is_live) {
                            $fcm->sendLiveNewsNotification($article_id, $title, $slug, $imageUrl);
                        } else {
                            // Regular article notification
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
                            $result = $fcm->sendToTopic('all', $notifTitle, $title, $data, $options);
                            
                            // Save notification to database for app to fetch later
                            if ($result) {
                                try {
                                    $db->insert('notification_logs', [
                                        'type' => 'article',
                                        'title' => $notifTitle,
                                        'body' => $title,
                                        'topic' => 'all',
                                        'sent_by' => $_SESSION['admin_id'] ?? null,
                                        'fcm_response' => $result,
                                        'success' => 1,
                                        'created_at' => date('Y-m-d H:i:s')
                                    ]);
                                } catch (Exception $logEx) {
                                    error_log('Failed to log notification: ' . $logEx->getMessage());
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('Notification Error: ' . $e->getMessage());
                }
            }
            
            $_SESSION['success'] = 'Article created successfully';
            header('Location: articles.php');
            exit;
        } else {
            $errors[] = 'Failed to create article';
        }
    }
}

// Get parent categories only for dropdown
$parent_categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' AND parent_id IS NULL ORDER BY name ASC");

// Get tags for selection
$tags = $db->fetchAll("SELECT id, name FROM tags ORDER BY name ASC");

// Get content types for selection
$content_types = $db->fetchAll("SELECT * FROM content_types WHERE status = 'active' ORDER BY display_order, name");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle"></i> Add New Article
            </h1>
            <a href="articles.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Articles
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
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                    <option value="scheduled">Scheduled</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Parent Category <span class="text-danger">*</span></label>
                                <select name="parent_category_id" id="parentCategory" class="form-select" required>
                                    <option value="">-- Select Parent Category --</option>
                                    <?php foreach ($parent_categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="subcategoryWrapper" style="display: none;">
                                <label class="form-label">Sub Category <small class="text-muted">(Optional)</small></label>
                                <select name="category_id" id="subCategory" class="form-select">
                                    <option value="">-- Select Sub Category --</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Content Type <span class="text-danger">*</span></label>
                                <select name="content_type_id" id="contentTypeSelect" class="form-select" required>
                                    <option value="">-- Select Content Type --</option>
                                    <?php foreach ($content_types as $ct): ?>
                                    <?php
                                    $ct_settings = json_decode($ct['settings'], true) ?? [];
                                    ?>
                                    <option value="<?= $ct['id'] ?>" 
                                            data-settings='<?= htmlspecialchars(json_encode($ct_settings)) ?>'
                                            <?= (isset($form_data['content_type_id']) && $form_data['content_type_id'] == $ct['id']) ? 'selected' : '' ?>>
                                        <?php if ($ct['icon']): ?>
                                        <?= htmlspecialchars($ct['name']) ?>
                                        <?php else: ?>
                                        <?= htmlspecialchars($ct['name']) ?>
                                        <?php endif; ?>
                                        <?php if ($ct['description']): ?>
                                        - <?= htmlspecialchars(substr($ct['description'], 0, 40)) ?>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted" id="contentTypeInfo"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Media Type</label>
                                <select name="content_type" class="form-select">
                                    <option value="standard">Standard Article</option>
                                    <option value="video">Video Article</option>
                                    <option value="reel">Reel/Short Video</option>
                                    <option value="photo">Photo Story</option>
                                    <option value="gallery">Gallery</option>
                                </select>
                                <small class="text-muted">How this content should be displayed</small>
                            </div>
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-image"></i> Featured Image (Thumbnail)</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Upload Thumbnail</label>
                                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Image Alt Text</label>
                                <input type="text" name="thumbnail_alt" class="form-control"
                                       value="<?= htmlspecialchars($form_data['thumbnail_alt'] ?? '') ?>"
                                       placeholder="Alt text for SEO">
                            </div>
                            <div id="thumbnail-preview"></div>
                        </div>
                    </div>

                    <!-- Media Content (Dynamic based on Media Type) -->
                    <div class="card mb-4" id="mediaContentCard" style="display: none;">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-collection-play"></i> Media Content</h5>
                        </div>
                        <div class="card-body">
                            <!-- Video Content -->
                            <div id="videoContent" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Video Source</label>
                                    <div class="btn-group w-100 mb-2" role="group">
                                        <input type="radio" class="btn-check" name="video_source" id="video_url_option" value="url" checked>
                                        <label class="btn btn-outline-primary" for="video_url_option">
                                            <i class="bi bi-link-45deg"></i> Video URL
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="video_source" id="video_file_option" value="file">
                                        <label class="btn btn-outline-primary" for="video_file_option">
                                            <i class="bi bi-upload"></i> Upload Video
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="video_url_input">
                                    <label class="form-label">Video URL</label>
                                    <input type="url" class="form-control" name="media_video_url" 
                                           placeholder="https://youtube.com/watch?v=... or direct URL">
                                    <small class="text-muted">YouTube, Vimeo, or direct video URL supported</small>
                                </div>
                                
                                <div class="mb-3" id="video_file_input" style="display: none;">
                                    <label class="form-label">Upload Video File</label>
                                    <input type="file" class="form-control" name="media_video_file" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                                    <small class="text-muted">MP4, WEBM, MOV, AVI (Max: 100MB)</small>
                                </div>
                            </div>

                            <!-- Reel Content -->
                            <div id="reelContent" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Reel Source</label>
                                    <div class="btn-group w-100 mb-2" role="group">
                                        <input type="radio" class="btn-check" name="reel_source" id="reel_url_option" value="url" checked>
                                        <label class="btn btn-outline-primary" for="reel_url_option">
                                            <i class="bi bi-link-45deg"></i> Reel URL
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="reel_source" id="reel_file_option" value="file">
                                        <label class="btn btn-outline-primary" for="reel_file_option">
                                            <i class="bi bi-upload"></i> Upload Reel
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="reel_url_input">
                                    <label class="form-label">Reel URL</label>
                                    <input type="url" class="form-control" name="media_reel_url" 
                                           placeholder="https://youtube.com/shorts/... or direct URL">
                                    <small class="text-muted">Short video URL (YouTube Shorts, Instagram Reels format)</small>
                                </div>
                                
                                <div class="mb-3" id="reel_file_input" style="display: none;">
                                    <label class="form-label">Upload Reel File</label>
                                    <input type="file" class="form-control" name="media_reel_file" accept="video/mp4,video/webm,video/quicktime">
                                    <small class="text-muted">MP4, WEBM, MOV (Max: 50MB, 60 seconds recommended)</small>
                                </div>
                            </div>

                            <!-- Gallery Content -->
                            <div id="galleryContent" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Gallery Type</label>
                                    <select class="form-select" name="gallery_type" id="galleryType">
                                        <option value="simple" selected>
                                            Simple Slider (Photos only - Thumbnail becomes slider)
                                        </option>
                                        <option value="detailed">
                                            Detailed Gallery (Photos with title & description)
                                        </option>
                                    </select>
                                    <small class="text-muted">Simple: Quick photo slider. Detailed: Each photo has title and description.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Upload Gallery Images</label>
                                    <input type="file" class="form-control" name="media_gallery_images[]" 
                                           accept="image/*" multiple id="galleryImagesInput">
                                    <small class="text-muted">Select multiple images for the gallery (JPG, PNG, GIF, WEBP)</small>
                                </div>
                                <div id="gallery-preview" class="row g-2 mt-3"></div>
                                
                                <!-- Metadata for Detailed Gallery -->
                                <div id="galleryMetadataSection" style="display: none;">
                                    <hr class="my-4">
                                    <h6 class="mb-3">Add Titles & Descriptions for Images</h6>
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
                                <?php foreach ($tags as $tag): ?>
                                <option value="<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></option>
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
                                <input type="checkbox" name="is_featured" class="form-check-input" id="isFeatured" value="1">
                                <label class="form-check-label" for="isFeatured">
                                    <i class="bi bi-star-fill text-warning"></i> Featured
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_top_news" class="form-check-input" id="isTopNews" value="1">
                                <label class="form-check-label" for="isTopNews">
                                    <i class="bi bi-arrow-up-circle-fill text-primary"></i> Top News
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_breaking" class="form-check-input" id="isBreaking" value="1">
                                <label class="form-check-label" for="isBreaking">
                                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> Breaking News
                                </label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="is_live" class="form-check-input" id="isLive" value="1">
                                <label class="form-check-label" for="isLive">
                                    <i class="bi bi-broadcast text-danger"></i> Live
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Create Article
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
document.querySelector('input[name="thumbnail"]').addEventListener('change', function(e) {
    const preview = document.getElementById('thumbnail-preview');
    preview.innerHTML = '';
    
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 100%;">`;
        }
        reader.readAsDataURL(this.files[0]);
    }
});

// Auto-generate slug
document.querySelector('input[name="title"]').addEventListener('input', function(e) {
    const slugInput = document.querySelector('input[name="slug"]');
    if (!slugInput.dataset.manual) {
        let slug = e.target.value.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-');
        slugInput.value = slug;
    }
});

document.querySelector('input[name="slug"]').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

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

// Content Type change handler - show requirements
document.getElementById('contentTypeSelect')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const settings = JSON.parse(selectedOption.dataset.settings || '{}');
    const info = document.getElementById('contentTypeInfo');
    
    if (settings.require_featured_image) {
        info.innerHTML = '<i class="bi bi-exclamation-circle text-warning"></i> Featured image is required for this content type.';
    } else if (settings.min_word_count > 0) {
        info.innerHTML = `<i class="bi bi-info-circle"></i> Minimum ${settings.min_word_count} words recommended.`;
    } else {
        info.innerHTML = '';
    }
});

// Load subcategories based on parent category
document.getElementById('parentCategory').addEventListener('change', function() {
    const parentId = this.value;
    const subcategoryWrapper = document.getElementById('subcategoryWrapper');
    const subcategorySelect = document.getElementById('subCategory');
    
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

</script>

<?php include 'includes/footer.php'; ?>
