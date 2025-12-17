<?php
/**
 * Edit Custom Page
 */

require_once 'auth_check.php';

$page_title = 'Edit Page';

$db = Database::getInstance();

// Get page ID
$page_id = (int)($_GET['id'] ?? 0);

// Get page data
$page = $db->fetchOne("SELECT * FROM custom_pages WHERE id = ?", [$page_id]);

if (!$page) {
    $_SESSION['error'] = 'Page not found';
    header('Location: pages.php');
    exit;
}

// Get categories for category articles type
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    $page_type = $_POST['page_type'];
    $content = $_POST['content'] ?? '';
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $status = $_POST['status'];
    $show_in_footer = isset($_POST['show_in_footer']) ? 1 : 0;
    $order_id = (int)($_POST['order_id'] ?? 0);
    $meta_title = trim($_POST['meta_title'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($slug)) {
        $errors[] = 'Slug is required';
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = 'Slug can only contain lowercase letters, numbers, and hyphens';
    }
    
    // Check slug uniqueness (exclude current page)
    $existing = $db->fetchOne("SELECT id FROM custom_pages WHERE slug = ? AND id != ?", [$slug, $page_id]);
    if ($existing) {
        $errors[] = 'A page with this slug already exists';
    }
    
    if ($page_type === 'text' && empty($content)) {
        $errors[] = 'Content is required for text pages';
    }
    
    if ($page_type === 'category_articles' && !$category_id) {
        $errors[] = 'Category is required for category articles pages';
    }
    
    if (empty($errors)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'page_type' => $page_type,
            'content' => $content,
            'category_id' => $category_id,
            'status' => $status,
            'show_in_footer' => $show_in_footer,
            'order_id' => $order_id,
            'meta_title' => $meta_title ?: $title,
            'meta_description' => $meta_description,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->update('custom_pages', $data, 'id = ?', [$page_id]);
        $_SESSION['success'] = 'Page updated successfully';
        header('Location: pages.php');
        exit;
    }
    
    // Preserve POST data for re-display
    $page = array_merge($page, $_POST);
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil"></i> Edit Page
            </h1>
            <div>
                <a href="<?= BASE_URL ?>/page/<?= $page['slug'] ?>" class="btn btn-info me-2" target="_blank">
                    <i class="bi bi-eye"></i> View Page
                </a>
                <a href="pages.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Pages
                </a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" class="needs-validation" novalidate>
            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Page Details</h5>
                        </div>
                        <div class="card-body">
                            <!-- Title -->
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" 
                                       value="<?= htmlspecialchars($page['title']) ?>" required>
                            </div>

                            <!-- Slug -->
                            <div class="mb-3">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" class="form-control" 
                                       value="<?= htmlspecialchars($page['slug']) ?>" 
                                       required pattern="[a-z0-9-]+">
                                <small class="text-muted">URL-friendly version (lowercase, hyphens only)</small>
                            </div>

                            <!-- Page Type -->
                            <div class="mb-3">
                                <label class="form-label">Page Type <span class="text-danger">*</span></label>
                                <select name="page_type" class="form-select" id="pageType" required>
                                    <option value="text" <?= $page['page_type'] === 'text' ? 'selected' : '' ?>>
                                        Text Page (Static Content)
                                    </option>
                                    <option value="category_articles" <?= $page['page_type'] === 'category_articles' ? 'selected' : '' ?>>
                                        Category Articles (Show articles from a category)
                                    </option>
                                </select>
                            </div>

                            <!-- Content (for text pages) -->
                            <div class="mb-3" id="contentSection">
                                <label class="form-label">Content</label>
                                <textarea name="content" class="form-control" rows="15" id="pageContent"><?= htmlspecialchars($page['content']) ?></textarea>
                            </div>

                            <!-- Category (for category articles) -->
                            <div class="mb-3" id="categorySection" style="display:none;">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $page['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Meta -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">SEO Meta Tags</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Meta Title</label>
                                <input type="text" name="meta_title" class="form-control" 
                                       value="<?= htmlspecialchars($page['meta_title']) ?>" 
                                       maxlength="60">
                                <small class="text-muted">Leave empty to use page title (60 chars max)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3" 
                                          maxlength="160"><?= htmlspecialchars($page['meta_description']) ?></textarea>
                                <small class="text-muted">Brief description for search engines (160 chars max)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Publish -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Publish</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="draft" <?= $page['status'] === 'draft' ? 'selected' : '' ?>>
                                        Draft
                                    </option>
                                    <option value="published" <?= $page['status'] === 'published' ? 'selected' : '' ?>>
                                        Published
                                    </option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Update Page
                            </button>
                        </div>
                    </div>

                    <!-- Display Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Display Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="show_in_footer" value="1" 
                                           class="form-check-input" id="showInFooter" 
                                           <?= $page['show_in_footer'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="showInFooter">
                                        Show in Footer
                                    </label>
                                </div>
                                <small class="text-muted">Display link in website footer</small>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Footer Order</label>
                                <input type="number" name="order_id" class="form-control" 
                                       value="<?= htmlspecialchars($page['order_id']) ?>" min="0">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>
                        </div>
                    </div>

                    <!-- Page Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Page Info</h5>
                        </div>
                        <div class="card-body">
                            <small class="text-muted d-block mb-2">
                                <strong>Views:</strong> <?= number_format($page['views_count']) ?>
                            </small>
                            <small class="text-muted d-block mb-2">
                                <strong>Created:</strong> <?= date('M d, Y H:i', strtotime($page['created_at'])) ?>
                            </small>
                            <small class="text-muted d-block">
                                <strong>Updated:</strong> <?= date('M d, Y H:i', strtotime($page['updated_at'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle content/category based on page type
document.getElementById('pageType').addEventListener('change', function() {
    const contentSection = document.getElementById('contentSection');
    const categorySection = document.getElementById('categorySection');
    
    if (this.value === 'text') {
        contentSection.style.display = 'block';
        categorySection.style.display = 'none';
    } else {
        contentSection.style.display = 'none';
        categorySection.style.display = 'block';
    }
});

// Trigger change event on page load
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('pageType').dispatchEvent(new Event('change'));
});
</script>

<?php include 'includes/footer.php'; ?>