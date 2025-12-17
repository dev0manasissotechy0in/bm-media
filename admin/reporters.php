<?php
/**
 * Reporters Management
 */

require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Functions.php';
require_once '../includes/Session.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if ($action === 'approve') {
        $db->update('reporters', ['status' => 'active'], 'id = ?', [$id]);
        $success = 'Reporter application approved';
    }
    
    elseif ($action === 'reject') {
        $db->update('reporters', ['status' => 'inactive'], 'id = ?', [$id]);
        $success = 'Reporter status revoked';
    }
    
    elseif ($action === 'make_author') {
        $reporter = $db->fetchOne("SELECT * FROM reporters WHERE id = ?", [$id]);
        
        if ($reporter && $reporter['email_verified'] == 1) {
            // Check if already an author
            $existing_author = $db->fetchOne("SELECT id FROM authors WHERE email = ?", [$reporter['email']]);
            
            if (!$existing_author) {
                // Create author account with same credentials
                $author_id = $db->insert('authors', [
                    'full_name' => $reporter['full_name'],
                    'email' => $reporter['email'],
                    'password' => $reporter['password'], // Use same password
                    'phone' => $reporter['phone'],
                    'profile_photo' => $reporter['profile_photo'],
                    'bio' => $reporter['bio'],
                    'country' => $reporter['country'],
                    'state' => $reporter['state'],
                    'city' => $reporter['city'],
                    'permissions' => json_encode(['create_article', 'edit_own_article', 'upload_media']),
                    'status' => 'active'
                ]);
                
                if ($author_id) {
                    // Update reporter to mark as author
                    $db->update('reporters', ['is_author' => 1], 'id = ?', [$id]);
                    
                    // Send notification email
                    try {
                        require_once __DIR__ . '/../includes/EmailHelper.php';
                        $emailHelper = new EmailHelper('auth');
                        $emailHelper->sendWelcomeEmail($reporter['email'], $reporter['full_name'], 'author');
                    } catch (Exception $e) {
                        // Ignore email errors
                    }
                    
                    $success = 'Reporter converted to Author successfully! Login credentials remain the same.';
                } else {
                    $success = 'Failed to create author account.';
                }
            } else {
                // Just update the is_author flag
                $db->update('reporters', ['is_author' => 1], 'id = ?', [$id]);
                $success = 'Reporter already has an author account.';
            }
        } else {
            $success = 'Reporter email must be verified before making them an author.';
        }
    }
}

// Get all reporters
$reporters = $db->fetchAll(
    "SELECT r.*, COUNT(DISTINCT a.id) as article_count
     FROM reporters r
     LEFT JOIN articles a ON r.id = a.author_id
     WHERE r.status IN ('active', 'pending', 'inactive')
     GROUP BY r.id
     ORDER BY r.created_at DESC"
);

// Get pending applications (users who might have requested reporter status)
// Note: This would require a reporter_applications table in a full implementation
$pending = [];

// Get statistics
$total_reporters = count($reporters);
$active_reporters = count(array_filter($reporters, fn($r) => $r['status'] === 'active'));
$total_articles = array_sum(array_column($reporters, 'article_count'));

$page_title = 'Manage Reporters';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Reporters</h1>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <h3><?= $total_reporters ?></h3>
                    <div>Total Reporters</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <h3><?= $active_reporters ?></h3>
                    <div>Active Reporters</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <h3><?= $total_articles ?></h3>
                    <div>Total Articles by Reporters</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reporters List -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-tie me-1"></i> All Reporters
        </div>
        <div class="card-body">
            <?php if (empty($reporters)): ?>
                <p class="text-muted text-center py-4">
                    No reporters yet. Promote users from the Users page.
                </p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Verified</th>
                            <th>Status</th>
                            <th>Is Author</th>
                            <th>Articles</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reporters as $reporter): ?>
                        <tr>
                            <td>
                                <?php if (!empty($reporter['profile_photo'])): ?>
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($reporter['profile_photo']) ?>" 
                                     class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px;">
                                    <i class="bi bi-person text-white"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($reporter['full_name'] ?? '-') ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($reporter['unique_reporter_id'] ?? '-') ?></small>
                            </td>
                            <td><?= htmlspecialchars($reporter['email']) ?></td>
                            <td>
                                <small>
                                    <?= htmlspecialchars($reporter['city'] ?? '-') ?>,<br>
                                    <?= htmlspecialchars($reporter['state'] ?? '-') ?>,<br>
                                    <?= htmlspecialchars($reporter['country'] ?? 'India') ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($reporter['email_verified'] == 1): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reporter['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif ($reporter['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reporter['is_author'] == 1): ?>
                                    <span class="badge bg-primary"><i class="bi bi-pencil-square"></i> Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $reporter['article_count'] ?></span>
                            </td>
                            <td><?= date('M d, Y', strtotime($reporter['created_at'])) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="reporter-view.php?id=<?= $reporter['id'] ?>" class="btn btn-info" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="reporter-edit.php?id=<?= $reporter['id'] ?>" class="btn btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($reporter['is_author'] == 0 && $reporter['email_verified'] == 1): ?>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Convert <?= htmlspecialchars(addslashes($reporter['full_name'])) ?> to Author? They will be able to login and submit articles.')">
                                        <input type="hidden" name="action" value="make_author">
                                        <input type="hidden" name="id" value="<?= $reporter['id'] ?>">
                                        <button type="submit" class="btn btn-primary" title="Make Author">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Info Card -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-info-circle me-1"></i> About Reporters
        </div>
        <div class="card-body">
            <h5>What are Reporters?</h5>
            <p>Reporters are trusted users who can create and publish articles. They have more privileges than regular users but less than administrators.</p>
            
            <h5 class="mt-3">How to Add Reporters:</h5>
            <ol>
                <li>Go to <a href="users.php">Users Management</a></li>
                <li>Find the user you want to promote</li>
                <li>Click the "Make Reporter" button</li>
                <li>The user will now appear in this list</li>
            </ol>
            
            <a href="users.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Manage All Users
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>