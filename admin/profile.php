<?php
/**
 * Admin Profile
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
$admin_id = $_SESSION['admin_id'];
$errors = [];
$success = '';

// Get admin details
$admin = $db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$admin_id]);

if (!$admin) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (empty($username)) {
            $errors[] = 'Username is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email address';
        }
        
        // Check username uniqueness
        $existing = $db->fetchOne("SELECT id FROM admin_users WHERE username = ? AND id != ?", [$username, $admin_id]);
        if ($existing) {
            $errors[] = 'Username already taken';
        }
        
        // Check email uniqueness
        $existing = $db->fetchOne("SELECT id FROM admin_users WHERE email = ? AND id != ?", [$email, $admin_id]);
        if ($existing) {
            $errors[] = 'Email already in use';
        }
        
        if (empty($errors)) {
            $db->update('admin_users', [
                'username' => $username,
                'full_name' => $full_name,
                'email' => $email
            ], 'id = ?', [$admin_id]);
            
            $success = 'Profile updated successfully';
            $admin = $db->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$admin_id]);
        }
    }
    
    elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($current)) {
            $errors[] = 'Current password is required';
        } elseif (!password_verify($current, $admin['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        if (empty($new)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($new !== $confirm) {
            $errors[] = 'Passwords do not match';
        }
        
        if (empty($errors)) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $db->update('admin_users', ['password' => $hashed], 'id = ?', [$admin_id]);
            $success = 'Password changed successfully';
        }
    }
}

$page_title = 'Admin Profile';
include 'includes/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">My Profile</h1>
    
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
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Profile Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i> Profile Information
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($admin['username']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($admin['full_name'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= htmlspecialchars($admin['email']) ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-key me-1"></i> Change Password
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" minlength="6" required>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Account Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-info-circle me-1"></i> Account Information
                </div>
                <div class="card-body">
                    <p><strong>Role:</strong> Administrator</p>
                    <p><strong>Member Since:</strong><br><?= date('F d, Y', strtotime($admin['created_at'])) ?></p>
                    <p><strong>Last Login:</strong><br><?= $admin['last_login'] ? date('M d, Y H:i', strtotime($admin['last_login'])) : 'N/A' ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>