<?php
// Get admin info from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_role = $_SESSION['admin_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin' ?> - <?= SITE_NAME ?> Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        .sidebar {
            min-height: 100vh;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 20px;
        }
        
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s;
            display: block;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        /* Dropdown menu styles */
        .sidebar-dropdown {
            position: relative;
        }
        
        .sidebar-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
        }
        
        .sidebar-dropdown-toggle::after {
            content: '\f282';
            font-family: 'bootstrap-icons';
            font-size: 12px;
            transition: transform 0.3s;
        }
        
        .sidebar-dropdown-toggle.collapsed::after {
            transform: rotate(-90deg);
        }
        
        .sidebar-dropdown-menu {
            padding-left: 15px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        
        .sidebar-dropdown-menu.show {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }
        
        .sidebar-dropdown-menu .nav-link {
            padding: 8px 20px;
            font-size: 0.9rem;
        }
        
        .section-header {
            color: rgba(255,255,255,0.5);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 20px 5px;
            margin-top: 15px;
        }
        
        .section-header:first-child {
            margin-top: 0;
        }
        
        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            width: calc(100% - 250px);
            padding: 0;
        }
        
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 0;
        }
        
        .content-wrapper {
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }
        
        .table thead {
            background-color: #f8f9fa;
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-250px);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 border-bottom border-white border-opacity-25">
            <h4 class="mb-0">
                <i class="bi bi-newspaper"></i> <?= SITE_NAME ?>
            </h4>
            <small class="opacity-75">Admin Panel</small>
        </div>
        
        <nav class="nav flex-column py-3">
            <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            
            <!-- ========== WEB CONTENT MANAGEMENT ========== -->
            <div class="section-header"><i class="bi bi-globe"></i> WEB CONTENT</div>
            
            <!-- Articles & Content -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle" data-bs-toggle="collapse" href="#contentMenu" role="button" aria-expanded="true">
                    <span><i class="bi bi-newspaper"></i> Articles & Content</span>
                </a>
                <div class="collapse show sidebar-dropdown-menu" id="contentMenu">
                    <a href="articles.php" class="nav-link">
                        <i class="bi bi-file-earmark-text"></i> All Articles
                    </a>
                    <a href="categories.php" class="nav-link">
                        <i class="bi bi-tags"></i> Categories
                    </a>
                    <a href="tags.php" class="nav-link">
                        <i class="bi bi-hash"></i> Tags
                    </a>
                    <a href="comments.php" class="nav-link">
                        <i class="bi bi-chat-dots"></i> Comments
                    </a>
                    <a href="article-approvals.php" class="nav-link">
                        <i class="bi bi-check-circle"></i> Article Approvals
                        <?php
                        // Get pending approvals count
                        $pending_count = 0;
                        try {
                            $db = Database::getInstance();
                            $result = $db->fetchOne("SELECT COUNT(*) as count FROM articles WHERE approval_status = 'pending'");
                            $pending_count = $result['count'] ?? 0;
                        } catch (Exception $e) {
                            // Ignore errors
                        }
                        if ($pending_count > 0): ?>
                        <span class="badge bg-warning text-dark rounded-pill float-end"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            
            <!-- Case Threads -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#caseThreadsMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-layers"></i> Case Threads</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="caseThreadsMenu">
                    <a href="cases.php" class="nav-link">
                        <i class="bi bi-folder"></i> All Cases
                    </a>
                    <a href="case-add.php" class="nav-link">
                        <i class="bi bi-plus-circle"></i> Add New Case
                    </a>
                </div>
            </div>
            
            <!-- Special Sections (Web) -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#specialMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-star"></i> Special Sections</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="specialMenu">
                    <a href="election.php" class="nav-link">
                        <i class="bi bi-ballot"></i> Election Dashboard
                    </a>
                    <a href="cricket.php" class="nav-link">
                        <i class="bi bi-trophy"></i> Cricket Dashboard
                    </a>
                    <a href="market.php" class="nav-link">
                        <i class="bi bi-graph-up"></i> Market Dashboard
                    </a>
                </div>
            </div>
            
            <!-- Web Media -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#webMediaMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-film"></i> Web Media</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="webMediaMenu">
                    <a href="stories.php" class="nav-link">
                        <i class="bi bi-collection-play"></i> Web Stories
                    </a>
                    <a href="reels.php" class="nav-link">
                        <i class="bi bi-film"></i> Reels
                    </a>
                    <a href="podcasts.php" class="nav-link">
                        <i class="bi bi-mic-fill"></i> Podcasts
                    </a>
                </div>
            </div>
            
            <!-- Web Pages & Contact -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#pagesMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-file-text"></i> Pages & Contact</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="pagesMenu">
                    <a href="pages.php" class="nav-link">
                        <i class="bi bi-file-earmark"></i> Custom Pages
                    </a>
                    <a href="contact-queries.php" class="nav-link">
                        <i class="bi bi-envelope-open"></i> Contact Queries
                    </a>
                </div>
            </div>
            
            <!-- ========== MOBILE APP MANAGEMENT ========== -->
            <div class="section-header"><i class="bi bi-phone"></i> MOBILE APP</div>
            
            <!-- App Content -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#appContentMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-app-indicator"></i> App Content</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="appContentMenu">
                    <a href="mobile-stories.php" class="nav-link">
                        <i class="bi bi-phone"></i> Mobile Stories
                    </a>
                    <a href="notifications.php" class="nav-link">
                        <i class="bi bi-bell"></i> Push Notifications
                    </a>
                    <a href="test-notification.php" class="nav-link">
                        <i class="bi bi-bug"></i> Test Notifications
                    </a>
                </div>
            </div>
            
            <!-- ========== USERS & TEAM ========== -->
            <div class="section-header"><i class="bi bi-people"></i> USERS & TEAM</div>
            
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#usersMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-people"></i> User Management</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="usersMenu">
                    <a href="users.php" class="nav-link">
                        <i class="bi bi-person"></i> All Users
                    </a>
                </div>
            </div>
            
            <!-- Reporter & Author Management -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#contentTeamMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-person-workspace"></i> Content Team</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="contentTeamMenu">
                    <a href="reporters.php" class="nav-link">
                        <i class="bi bi-person-badge"></i> Reporters
                    </a>
                    <a href="reporter-add.php" class="nav-link">
                        <i class="bi bi-person-plus"></i> Add Reporter
                    </a>
                    <a href="authors.php" class="nav-link">
                        <i class="bi bi-pencil-square"></i> Authors
                    </a>
                    <a href="author-add.php" class="nav-link">
                        <i class="bi bi-person-plus-fill"></i> Add Author
                    </a>
                </div>
            </div>
            
            <!-- ========== MARKETING ========== -->
            <div class="section-header"><i class="bi bi-megaphone"></i> MARKETING</div>
            
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#marketingMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-badge-ad"></i> Ads & Newsletter</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="marketingMenu">
                    <a href="ads.php" class="nav-link">
                        <i class="bi bi-badge-ad"></i> Ads Management
                    </a>
                    <a href="newsletter.php" class="nav-link">
                        <i class="bi bi-envelope"></i> Newsletter
                    </a>
                </div>
            </div>
            
            <!-- ========== CONFIGURATION ========== -->
            <div class="section-header"><i class="bi bi-gear"></i> CONFIGURATION</div>
            
            <!-- Email Configuration -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#emailMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-envelope-at"></i> Email Settings</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="emailMenu">
                    <a href="smtp-multi-config.php" class="nav-link">
                        <i class="bi bi-envelope-at-fill"></i> SMTP Configuration
                    </a>
                    <a href="smtp-diagnose.php" class="nav-link">
                        <i class="bi bi-question-circle-fill"></i> SMTP Diagnostics
                    </a>
                    <a href="smtp-test.php" class="nav-link">
                        <i class="bi bi-envelope-check-fill"></i> Test SMTP
                    </a>
                </div>
            </div>
            
            <!-- System Settings -->
            <div class="sidebar-dropdown">
                <a class="nav-link sidebar-dropdown-toggle collapsed" data-bs-toggle="collapse" href="#settingsMenu" role="button" aria-expanded="false">
                    <span><i class="bi bi-sliders"></i> System Settings</span>
                </a>
                <div class="collapse sidebar-dropdown-menu" id="settingsMenu">
                    <a href="settings.php" class="nav-link">
                        <i class="bi bi-sliders"></i> Site Settings
                    </a>
                    <!-- <a href="advanced-settings.php" class="nav-link">
                        <i class="bi bi-gear-wide-connected"></i> Advanced Settings
                    </a> -->
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-admin sticky-top">
            <div class="container-fluid">
                <button class="btn btn-link text-secondary" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                
                <div class="ms-auto d-flex align-items-center gap-3">
                    <!-- Search Bar -->
                    <form action="search.php" method="GET" class="d-none d-md-block">
                        <div class="input-group input-group-sm" style="width: 300px;">
                            <input type="text" class="form-control" name="q" 
                                   placeholder="Search articles, categories..." 
                                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right"></i> View Site
                    </a>
                    
                    <!-- Notifications Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-link text-secondary position-relative p-0" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell fs-5" id="notificationBell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none; font-size: 0.7rem;">
                                0
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
                            <li>
                                <h6 class="dropdown-header d-flex justify-content-between align-items-center">
                                    <span>Notifications</span>
                                    <span class="badge bg-primary" id="notificationTotal">0</span>
                                </h6>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                            <li id="notificationsList">
                                <div class="dropdown-item-text text-center text-muted py-3">
                                    <i class="bi bi-bell-slash"></i>
                                    <p class="mb-0 mt-2 small">No new notifications</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-link text-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle fs-5"></i>
                            <span class="ms-2"><?= htmlspecialchars($admin_name) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="bi bi-person"></i> Profile
                                </a>
                            </li>
                            <!-- <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="bi bi-gear"></i> Settings
                                </a>
                            </li> -->
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->        
        <style>
            /* Notification Bell Animation */
            @keyframes bellRing {
                0%, 100% { transform: rotate(0); }
                10%, 30%, 50%, 70%, 90% { transform: rotate(-15deg); }
                20%, 40%, 60%, 80% { transform: rotate(15deg); }
            }
            
            .bell-ring {
                animation: bellRing 1s ease-in-out;
            }
            
            #notificationBell.has-notifications {
                color: #dc3545 !important;
            }
            
            .notification-item {
                padding: 12px 20px;
                transition: background-color 0.2s;
                cursor: pointer;
                border-left: 3px solid transparent;
            }
            
            .notification-item:hover {
                background-color: #f8f9fa;
            }
            
            .notification-item.unread {
                background-color: #e7f3ff;
                border-left-color: #0d6efd;
            }
            
            .notification-icon {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }
            
            .notification-icon.article {
                background-color: #e7f3ff;
                color: #0d6efd;
            }
            
            .notification-icon.contact {
                background-color: #fff3cd;
                color: #ffc107;
            }
        </style>
        
        <script>
            let lastNotificationCount = 0;
            
            // Fetch notifications
            async function fetchNotifications() {
                try {
                    const response = await fetch('<?= BASE_URL ?>/api/admin/get-notifications-count.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        updateNotificationUI(data);
                        
                        // Trigger bell ring animation if count increased
                        if (data.total > lastNotificationCount && lastNotificationCount > 0) {
                            const bell = document.getElementById('notificationBell');
                            bell.classList.add('bell-ring');
                            setTimeout(() => bell.classList.remove('bell-ring'), 1000);
                        }
                        
                        lastNotificationCount = data.total;
                    }
                } catch (error) {
                    console.error('Failed to fetch notifications:', error);
                }
            }
            
            function updateNotificationUI(data) {
                const badge = document.getElementById('notificationBadge');
                const total = document.getElementById('notificationTotal');
                const bell = document.getElementById('notificationBell');
                const list = document.getElementById('notificationsList');
                
                // Update badge
                if (data.total > 0) {
                    badge.textContent = data.total > 99 ? '99+' : data.total;
                    badge.style.display = 'inline-block';
                    bell.classList.add('has-notifications');
                } else {
                    badge.style.display = 'none';
                    bell.classList.remove('has-notifications');
                }
                
                // Update total in dropdown
                total.textContent = data.total;
                
                // Update notification list
                let html = '';
                
                if (data.pendingArticles > 0) {
                    html += `
                        <li>
                            <a href="article-approvals.php" class="dropdown-item notification-item unread">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon article me-3">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Article Approvals</div>
                                        <small class="text-muted">${data.pendingArticles} article${data.pendingArticles > 1 ? 's' : ''} waiting for approval</small>
                                        <div class="mt-1">
                                            <span class="badge bg-primary">${data.pendingArticles}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `;
                }
                
                if (data.unreadContacts > 0) {
                    html += `
                        <li>
                            <a href="contact-queries.php" class="dropdown-item notification-item unread">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon contact me-3">
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">Contact Queries</div>
                                        <small class="text-muted">${data.unreadContacts} new message${data.unreadContacts > 1 ? 's' : ''} received</small>
                                        <div class="mt-1">
                                            <span class="badge bg-warning text-dark">${data.unreadContacts}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `;
                }
                
                if (data.newStories > 0) {
                    html += `
                        <li>
                            <a href="mobile-stories.php" class="dropdown-item notification-item unread">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon" style="background-color: #f3e5f5; color: #9c27b0;">
                                        <i class="bi bi-image"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-semibold">New Stories</div>
                                        <small class="text-muted">${data.newStories} new stor${data.newStories > 1 ? 'ies' : 'y'} uploaded (24h)</small>
                                        <div class="mt-1">
                                            <span class="badge bg-purple" style="background-color: #9c27b0;">${data.newStories}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `;
                }
                
                if (data.liveArticles > 0) {
                    html += `
                        <li>
                            <a href="articles.php?filter=live" class="dropdown-item notification-item unread">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon" style="background-color: #ffebee; color: #dc3545;">
                                        <i class="bi bi-broadcast"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-semibold">Live News</div>
                                        <small class="text-muted">${data.liveArticles} live article${data.liveArticles > 1 ? 's' : ''} broadcasting</small>
                                        <div class="mt-1">
                                            <span class="badge bg-danger">${data.liveArticles}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `;
                }
                
                if (data.newCases > 0) {
                    html += `
                        <li>
                            <a href="cases.php" class="dropdown-item notification-item unread">
                                <div class="d-flex align-items-start">
                                    <div class="notification-icon" style="background-color: #e8f5e9; color: #4caf50;">
                                        <i class="bi bi-briefcase"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-semibold">New Case Studies</div>
                                        <small class="text-muted">${data.newCases} new case${data.newCases > 1 ? 's' : ''} added (24h)</small>
                                        <div class="mt-1">
                                            <span class="badge bg-success">${data.newCases}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </li>
                    `;
                }
                
                if (data.total === 0) {
                    html = `
                        <li>
                            <div class="dropdown-item-text text-center text-muted py-3">
                                <i class="bi bi-bell-slash"></i>
                                <p class="mb-0 mt-2 small">No new notifications</p>
                            </div>
                        </li>
                    `;
                } else {
                    html += `
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a href="article-approvals.php" class="dropdown-item text-center text-primary small">
                                <i class="bi bi-eye"></i> View All Notifications
                            </a>
                        </li>
                    `;
                }
                
                list.innerHTML = html;
            }
            
            // Initial fetch
            document.addEventListener('DOMContentLoaded', function() {
                fetchNotifications();
                
                // Refresh every 30 seconds
                setInterval(fetchNotifications, 30000);
                
                // Fetch on dropdown open
                const dropdown = document.getElementById('notificationsDropdown');
                if (dropdown) {
                    dropdown.addEventListener('click', fetchNotifications);
                }
            });
        </script>