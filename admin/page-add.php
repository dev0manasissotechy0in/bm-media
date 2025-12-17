<?php
/**
 * Add Custom Page
 */

require_once 'auth_check.php';

$page_title = 'Add New Page';

$db = Database::getInstance();

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
    
    // Check slug uniqueness
    $existing = $db->fetchOne("SELECT id FROM custom_pages WHERE slug = ?", [$slug]);
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
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('custom_pages', $data);
        $_SESSION['success'] = 'Page created successfully';
        header('Location: pages.php');
        exit;
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle"></i> Add New Page
            </h1>
            <a href="pages.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Pages
            </a>
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
                                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" 
                                       id="pageTitle" required>
                            </div>

                            <!-- Slug -->
                            <div class="mb-3">
                                <label class="form-label">Slug <span class="text-danger">*</span></label>
                                <input type="text" name="slug" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" 
                                       id="pageSlug" required pattern="[a-z0-9-]+">
                                <small class="text-muted">URL-friendly version (lowercase, hyphens only)</small>
                            </div>

                            <!-- Page Type -->
                            <div class="mb-3">
                                <label class="form-label">Page Type <span class="text-danger">*</span></label>
                                <select name="page_type" class="form-select" id="pageType" required>
                                    <option value="text" <?= ($_POST['page_type'] ?? '') === 'text' ? 'selected' : '' ?>>
                                        Text Page (Static Content)
                                    </option>
                                    <option value="category_articles" <?= ($_POST['page_type'] ?? '') === 'category_articles' ? 'selected' : '' ?>>
                                        Category Articles (Show articles from a category)
                                    </option>
                                </select>
                            </div>

                            <!-- Content (for text pages) -->
                            <div class="mb-3" id="contentSection">
                                <label class="form-label">Content</label>
                                <textarea name="content" class="form-control" rows="15" id="pageContent"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                            </div>

                            <!-- Category (for category articles) -->
                            <div class="mb-3" id="categorySection" style="display:none;">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" class="form-select">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
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
                                       value="<?= htmlspecialchars($_POST['meta_title'] ?? '') ?>" 
                                       maxlength="60">
                                <small class="text-muted">Leave empty to use page title (60 chars max)</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Meta Description</label>
                                <textarea name="meta_description" class="form-control" rows="3" 
                                          maxlength="160"><?= htmlspecialchars($_POST['meta_description'] ?? '') ?></textarea>
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
                                    <option value="draft" <?= ($_POST['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>
                                        Draft
                                    </option>
                                    <option value="published" <?= ($_POST['status'] ?? '') === 'published' ? 'selected' : '' ?>>
                                        Published
                                    </option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Create Page
                            </button>
                        </div>
                    </div>

                    <!-- Display Options -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Display Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="show_in_footer" value="1" 
                                           class="form-check-input" id="showInFooter" 
                                           <?= isset($_POST['show_in_footer']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="showInFooter">
                                        Show in Footer
                                    </label>
                                </div>
                                <small class="text-muted">Display link in website footer</small>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Footer Order</label>
                                <input type="number" name="order_id" class="form-control" 
                                       value="<?= htmlspecialchars($_POST['order_id'] ?? '0') ?>" min="0">
                                <small class="text-muted">Lower numbers appear first</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-generate slug from title
document.getElementById('pageTitle').addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
    document.getElementById('pageSlug').value = slug;
});

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