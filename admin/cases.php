<?php
/**
 * Case Threads Management
 */

require_once 'auth_check.php';

$page_title = 'Case Threads';

$db = Database::getInstance();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Delete related data
    $db->query("DELETE FROM case_follows WHERE case_id = ?", [$id]);
    $db->query("DELETE FROM case_timeline_events WHERE case_id = ?", [$id]);
    $db->query("DELETE FROM case_documents WHERE case_id = ?", [$id]);
    $db->query("DELETE FROM case_media WHERE case_id = ?", [$id]);
    $db->query("DELETE FROM case_article_map WHERE case_id = ?", [$id]);
    $db->query("DELETE FROM case_reviews WHERE case_id = ?", [$id]);
    $db->query("DELETE FROM case_threads WHERE id = ?", [$id]);
    
    $_SESSION['success'] = 'Case deleted successfully';
    header('Location: cases.php');
    exit;
}

// Handle Status Change
if (isset($_GET['status_change'])) {
    $id = (int)$_GET['status_change'];
    $new_status = $_GET['new_status'] ?? 'ongoing';
    
    $db->query("UPDATE case_threads SET status = ? WHERE id = ?", [$new_status, $id]);
    $_SESSION['success'] = 'Case status updated';
    header('Location: cases.php');
    exit;
}

// Filters
$status_filter = $_GET['status'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];

if ($status_filter !== 'all') {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if ($category_filter !== 'all') {
    $where[] = "category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $where[] = "(title LIKE ? OR short_description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$total = $db->fetchOne("SELECT COUNT(*) as count FROM case_threads {$where_sql}", $params);
$total_cases = $total['count'];
$total_pages = ceil($total_cases / $per_page);

// Get cases
$params[] = $per_page;
$params[] = $offset;
$cases = $db->fetchAll("
    SELECT * FROM case_threads 
    {$where_sql}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
", $params);

// Get categories for filter
$categories = $db->fetchAll("SELECT DISTINCT category FROM case_threads WHERE category IS NOT NULL ORDER BY category");

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="page-header">
        <h1>Case Threads Management</h1>
        <a href="case-add.php" class="btn btn-primary">+ Add New Case</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="filter-form">
                <div class="row">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="ongoing" <?php echo $status_filter === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="historic" <?php echo $status_filter === 'historic' ? 'selected' : ''; ?>>Historic</option>
                            <option value="verdict_pending" <?php echo $status_filter === 'verdict_pending' ? 'selected' : ''; ?>>Verdict Pending</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                    <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by title or description..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Cases Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Articles</th>
                            <th>Followers</th>
                            <th>Views</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cases)): ?>
                            <tr>
                                <td colspan="9" class="text-center">No cases found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($cases as $case): ?>
                                <tr>
                                    <td><?php echo $case['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($case['title']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars(substr($case['short_description'] ?? '', 0, 80)); ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst($case['category'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_colors = [
                                            'ongoing' => 'success',
                                            'closed' => 'secondary',
                                            'historic' => 'info',
                                            'verdict_pending' => 'warning'
                                        ];
                                        $color = $status_colors[$case['status']] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?php echo $color; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $case['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($case['total_articles']); ?></td>
                                    <td><?php echo number_format($case['total_followers']); ?></td>
                                    <td><?php echo number_format($case['total_views']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($case['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="case-edit.php?id=<?php echo $case['id']; ?>" 
                                               class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="case-timeline.php?case_id=<?php echo $case['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Timeline">
                                                <i class="fas fa-stream"></i>
                                            </a>
                                            <a href="case-documents.php?case_id=<?php echo $case['id']; ?>" 
                                               class="btn btn-sm btn-secondary" title="Documents">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            <a href="case-media.php?case_id=<?php echo $case['id']; ?>" 
                                               class="btn btn-sm btn-warning" title="Media">
                                                <i class="fas fa-images"></i>
                                            </a>
                                            <a href="case-articles.php?case_id=<?php echo $case['id']; ?>" 
                                               class="btn btn-sm btn-success" title="Articles">
                                                <i class="fas fa-newspaper"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/case/<?php echo $case['slug']; ?>" 
                                               class="btn btn-sm btn-info" title="View" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?delete=<?php echo $case['id']; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Are you sure? This will delete all related data (timeline, documents, media, articles).');"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?><?php echo $category_filter !== 'all' ? '&category=' . $category_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?><?php echo $category_filter !== 'all' ? '&category=' . $category_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status_filter !== 'all' ? '&status=' . $status_filter : ''; ?><?php echo $category_filter !== 'all' ? '&category=' . $category_filter : ''; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
