<?php
/**
 * Add Custom Page
 */

require_once 'auth_check.php';

$page_title = 'Add Custom Page';

$db = Database::getInstance();

$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $page_type = $_POST['page_type'] ?? 'text';
    $content = $_POST['content'] ?? '';
    $show_in_footer = isset($_POST['show_in_footer']) ? 1 : 0;
    $status = $_POST['status'] ?? 'draft';
    
    // SEO fields
    $seo_title = trim($_POST['seo_title'] ?? '');
    $seo_description = trim($_POST['seo_description'] ?? '');
    $seo_keywords = trim($_POST['seo_keywords'] ?? '');
    
    // Type-specific data
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $tag_id = !empty($_POST['tag_id']) ? (int)$_POST['tag_id'] : null;
    
    $form_data = $_POST;
    
    if (empty($title)) {
        $errors[] = 'Page title is required';
    }
    
    if (empty($slug)) {
        $slug = strtolower(str_replace(' ', '-', $title));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    }
    
    // Check slug uniqueness
    $existing = $db->fetchOne("SELECT id FROM custom_pages WHERE slug = ?", [$slug]);
    if ($existing) {
        $errors[] = 'Slug already exists. Please use a different slug.';
    }
    
    if (empty($errors)) {
        $data = [
            'title' => $title,
            'slug' => $slug,
            'page_type' => $page_type,
            'content' => $content,
            'category_id' => $category_id,
            'tag_id' => $tag_id,
            'show_in_footer' => $show_in_footer,
            'status' => $status,
            'seo_title' => $seo_title,
            'seo_description' => $seo_description,
            'seo_keywords' => $seo_keywords,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $page_id = $db->insert('custom_pages', $data);
        
        if ($page_id) {
            $_SESSION['success'] = 'Page created successfully';
            header('Location: custom-pages.php');
            exit;
        } else {
            $errors[] = 'Failed to create page';
        }
    }
}

// Get categories and tags for dropdowns
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$tags = $db->fetchAll("SELECT id, name FROM tags ORDER BY name ASC");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle"></i> Add Custom Page
            </h1>
            <a href="custom-pages.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Pages
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

        <form method="POST">
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Page Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required
                                       value="<?= htmlspecialchars($form_data['title'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control"
                                       value="<?= htmlspecialchars($form_data['slug'] ?? '') ?>"
                                       placeholder="Auto-generated if empty">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Page Type</label>
                                <select name="page_type" id="pageType" class="form-select">
                                    <option value="text">Text Content</option>
                                    <option value="category_articles">Category Based Articles</option>
                                    <option value="tag_articles">Tag Based Articles</option>
                                    <option value="live_polls">Live Election Polls</option>
                                    <option value="statistics">Statistics</option>
                                    <option value="graphics">Graphics/Charts</option>
                                </select>
                            </div>

                            <div id="contentSection" class="mb-3">
                                <label class="form-label">Content</label>
                                <textarea name="content" id="pageContent" class="form-control" rows="15"><?= htmlspecialchars($form_data['content'] ?? '') ?></textarea>
                            </div>

                            <div id="categorySection" class="mb-3" style="display: none;">
                                <label class="form-label">Select Category</label>
                                <select name="category_id" class="form-select">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="tagSection" class="mb-3" style="display: none;">
                                <label class="form-label">Select Tag</label>
                                <select name="tag_id" class="form-select">
                                    <option value="">-- Select Tag --</option>
                                    <?php foreach ($tags as $tag): ?>
                                    <option value="<?= $tag['id'] ?>"><?= htmlspecialchars($tag['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
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
                                <input type="text" name="seo_title" class="form-control"
                                       value="<?= htmlspecialchars($form_data['seo_title'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Description</label>
                                <textarea name="seo_description" class="form-control" rows="3"><?= htmlspecialchars($form_data['seo_description'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">SEO Keywords</label>
                                <input type="text" name="seo_keywords" class="form-control"
                                       value="<?= htmlspecialchars($form_data['seo_keywords'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="draft">Draft</option>
                                    <option value="published">Published</option>
                                </select>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="show_in_footer" class="form-check-input" id="showFooter" value="1">
                                <label class="form-check-label" for="showFooter">
                                    Show in Footer
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Create Page
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quill.js Editor -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
// Initialize Quill Editor
const quill = new Quill('#pageContent', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'align': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['blockquote', 'code-block'],
            ['link', 'image'],
            ['clean']
        ]
    }
});

// Show/hide sections based on page type
document.getElementById('pageType').addEventListener('change', function() {
    const type = this.value;
    const contentSection = document.getElementById('contentSection');
    const categorySection = document.getElementById('categorySection');
    const tagSection = document.getElementById('tagSection');
    
    contentSection.style.display = 'block';
    categorySection.style.display = 'none';
    tagSection.style.display = 'none';
    
    if (type === 'category_articles') {
        categorySection.style.display = 'block';
    } else if (type === 'tag_articles') {
        tagSection.style.display = 'block';
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
</script>

<?php include 'includes/footer.php'; ?>
