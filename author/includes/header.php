<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Author Dashboard' ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 250px;
            padding-top: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-y: auto;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .topbar {
            position: fixed;
            top: 0;
            left: 250px;
            right: 0;
            height: 60px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 20px;
        }
        .content-wrapper {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }
        .sidebar-brand {
            padding: 20px;
            color: white;
            font-size: 1.2rem;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .user-dropdown img {
            width: 35px;
            height: 35px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-pencil-square"></i> Author Portal
        </div>
        <nav class="nav flex-column">
            <a href="<?= BASE_URL ?>/author/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <?php if (hasPermission('create_article')): ?>
            <a href="<?= BASE_URL ?>/author/article-add.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'article-add.php' ? 'active' : '' ?>">
                <i class="bi bi-plus-circle"></i> New Article
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/author/articles.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'articles.php' ? 'active' : '' ?>">
                <i class="bi bi-file-text"></i> My Articles
            </a>
            <a href="<?= BASE_URL ?>/author/profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Profile
            </a>
            <hr style="border-color: rgba(255, 255, 255, 0.2);">
            <a href="<?= BASE_URL ?>" class="nav-link" target="_blank">
                <i class="bi bi-globe"></i> View Website
            </a>
            <a href="<?= BASE_URL ?>/author/logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </div>
    
    <!-- Top Bar -->
    <div class="topbar">
        <div class="flex-grow-1">
            <h5 class="mb-0"><?= $page_title ?? 'Dashboard' ?></h5>
        </div>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" 
               id="userDropdown" data-bs-toggle="dropdown">
                <?php if (!empty(Session::get('author_photo'))): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars(Session::get('author_photo')) ?>" 
                     alt="<?= htmlspecialchars(Session::get('author_name')) ?>" 
                     class="rounded-circle me-2">
                <?php else: ?>
                <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center me-2" 
                     style="width: 35px; height: 35px;">
                    <i class="bi bi-person text-white"></i>
                </div>
                <?php endif; ?>
                <span><?= htmlspecialchars(Session::get('author_name')) ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="profile.php">
                    <i class="bi bi-person"></i> Profile
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a></li>
            </ul>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php if (Session::hasFlash('success') || Session::hasFlash('error') || Session::hasFlash('warning') || Session::hasFlash('info')): ?>
    <div class="content-wrapper">
        <?php foreach (['success', 'error', 'warning', 'info'] as $type): ?>
            <?php if (Session::hasFlash($type)): ?>
        <div class="alert alert-<?= $type ?> alert-dismissible fade show">
            <?= Session::getFlash($type) ?>e) ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
