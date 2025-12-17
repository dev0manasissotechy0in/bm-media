<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <?php
    // Generate meta tags
    $page_title = $page_title ?? 'Home';
    $page_description = $page_description ?? DEFAULT_META_DESCRIPTION;
    $page_keywords = $page_keywords ?? DEFAULT_META_KEYWORDS;
    $page_image = $page_image ?? DEFAULT_OG_IMAGE;
    
    echo generateMetaTags($page_title, $page_description, $page_keywords, $page_image);
    ?>
    
    <?php
    // Dynamic Favicon - Changes based on category
    $favicon_url = ASSETS_URL . '/images/favicon.ico'; // Default favicon
    $favicon_type = 'image/x-icon';
    
    if (isset($category) && !empty($category['icon'])) {
        // Check if icon is a CSS class or file
        if (strpos($category['icon'], 'class:') === 0) {
            // Icon is a CSS class, use default favicon
            $favicon_url = ASSETS_URL . '/images/favicon.ico';
        } else {
            // Icon is a file, use it as favicon
            $favicon_url = UPLOADS_URL . '/categories/' . $category['icon'];
            
            // Determine MIME type based on file extension
            $icon_ext = strtolower(pathinfo($category['icon'], PATHINFO_EXTENSION));
            switch ($icon_ext) {
                case 'png':
                    $favicon_type = 'image/png';
                    break;
                case 'jpg':
                case 'jpeg':
                    $favicon_type = 'image/jpeg';
                    break;
                case 'gif':
                    $favicon_type = 'image/gif';
                    break;
                case 'svg':
                    $favicon_type = 'image/svg+xml';
                    break;
                case 'webp':
                    $favicon_type = 'image/webp';
                    break;
                case 'ico':
                default:
                    $favicon_type = 'image/x-icon';
                    break;
            }
        }
    }
    ?>
    
    <link rel="icon" href="<?= $favicon_url ?>" type="<?= $favicon_type ?>">
    <link rel="shortcut icon" href="<?= $favicon_url ?>" type="<?= $favicon_type ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Custom CSS -->
    <?php $style_ver = file_exists(__DIR__ . '/../assets/css/style.css') ? filemtime(__DIR__ . '/../assets/css/style.css') : time(); ?>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/style.css?v=<?= $style_ver ?>">
    
    <!-- Base URL Configuration -->
    <script>
        const BASE_URL = '<?= BASE_URL ?>';
        const ASSETS_URL = '<?= ASSETS_URL ?>';
        const UPLOADS_URL = '<?= UPLOADS_URL ?>';
        const API_URL = '<?= API_URL ?>';
    </script>
    
    <?php if (GOOGLE_ANALYTICS_ID): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GOOGLE_ANALYTICS_ID ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?= GOOGLE_ANALYTICS_ID ?>');
    </script>
    <?php endif; ?>
    
    <?php if (FACEBOOK_PIXEL_ID): ?>
    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?= FACEBOOK_PIXEL_ID ?>');
        fbq('track', 'PageView');
    </script>
    <?php endif; ?>
    
    <!-- Google AdSense Script -->
    <?php 
    if (class_exists('AdsManager')) {
        echo AdsManager::getAdsenseScript();
    }
    ?>
</head>
<body>
    <!-- Top Bar -->
    <!-- <div class="top-bar bg-dark text-white py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="me-3"><i class="bi bi-calendar3"></i> <?= date('l, F j, Y') ?></span>
                </div>
                <div class="col-md-6 text-end">
                    <a href="#" class="text-white me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="text-white me-3"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-youtube"></i></a>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Header styles have been moved to assets/css/style.css for improved maintainability and responsiveness -->
    
    <header class="header bg-white shadow-sm sticky-top">
        <!-- Mobile Menu (Burger Sidebar) -->
        <div class="mobile-menu-backdrop" id="mobileMenuBackdrop" aria-hidden="true"></div>
        <div class="mobile-menu-overlay" id="mobileMenu" role="dialog" aria-modal="true" aria-hidden="true">
            <div class="mobile-menu-header">
                <div class="d-flex align-items-center gap-2">
                    <a class="navbar-brand fw-bold mb-0" href="<?= BASE_URL ?>">
                        <img src="<?= ASSETS_URL ?>/images/logo.png" alt="<?= SITE_NAME ?>" height="36" onerror="this.style.display='none'">
                        <span class="ms-2 d-inline-block"><?= SITE_NAME ?></span>
                    </a>
                </div>
                <button class="mobile-menu-close" id="mobileMenuClose" aria-label="Close menu">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="mobile-menu-body">
                <div class="mobile-nav-search px-3 py-2">
                    <form action="<?= BASE_URL ?>/search" method="GET">
                        <div class="input-group">
                            <input type="text" name="q" class="form-control" placeholder="Search articles..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="Search articles">
                            <button class="btn btn-primary" type="submit" aria-label="Search"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>

                <nav class="mobile-menu-sections px-2">
                    <div class="mobile-menu-section">
                        <h6 class="px-3 mt-2">Browse</h6>
                        <a href="<?= BASE_URL ?>/stories" class="mobile-menu-link px-3 py-2"><i class="bi bi-image me-2"></i>Stories</a>
                        <a href="<?= BASE_URL ?>/reels" class="mobile-menu-link px-3 py-2"><i class="bi bi-camera-reels me-2"></i>Reels</a>
                        <a href="<?= BASE_URL ?>/podcasts" class="mobile-menu-link px-3 py-2"><i class="bi bi-mic-fill me-2"></i>Podcasts</a>
                        <a href="<?= BASE_URL ?>/case-threads" class="mobile-menu-link px-3 py-2"><i class="bi bi-journal-text me-2"></i>Case Threads</a>
                    </div>

                    <div class="mobile-menu-section">
                        <h6 class="px-3 mt-3">Categories</h6>
                        <?php
                        $mobile_categories = (isset($subcategories) && !empty($subcategories)) ? $subcategories : getTopCategories();
                        foreach ($mobile_categories as $mcat): ?>
                        <a href="<?= BASE_URL ?>/category/<?= $mcat['slug'] ?>" class="mobile-category-item px-3 py-2">
                            <?php if (!empty($mcat['icon']) && strpos($mcat['icon'], 'class:') !== 0): ?>
                                <img src="<?= UPLOADS_URL ?>/categories/<?= $mcat['icon'] ?>" alt="" height="20" width="20" class="me-2">
                            <?php else: ?>
                                <i class="bi bi-folder-fill me-2"></i>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($mcat['name']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="mobile-menu-section mb-4">
                        <h6 class="px-3 mt-3">Account</h6>
                        <?php if (Security::isLoggedIn('user')): ?>
                            <a href="<?= BASE_URL ?>/user/dashboard.php" class="mobile-menu-link px-3 py-2">Dashboard</a>
                            <a href="<?= BASE_URL ?>/user/saved-articles.php" class="mobile-menu-link px-3 py-2">Saved</a>
                            <a href="<?= BASE_URL ?>/logout.php" class="mobile-menu-link px-3 py-2">Logout</a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/login" class="mobile-menu-link px-3 py-2">Login</a>
                            <a href="<?= BASE_URL ?>/register" class="mobile-menu-link px-3 py-2">Register</a>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        </div>
        
        <!-- Top Header: Logo and Main Actions -->
        <div class="top-header border-bottom">
            <div class="container">
                <div class="py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <!-- Logo - Dynamic based on page (Left) -->
                        <a class="navbar-brand fw-bold fs-3 mb-0" href="<?= BASE_URL ?>">
                            <?php if (isset($category) && !empty($category['logo'])): ?>
                                <!-- Category Logo -->
                                <img src="<?= UPLOADS_URL ?>/categories/<?= $category['logo'] ?>" alt="<?= htmlspecialchars($category['name']) ?>" height="50" class="category-logo">
                            <?php else: ?>
                                <!-- Default Site Logo -->
                                <img src="<?= ASSETS_URL ?>/images/logo.png" alt="<?= SITE_NAME ?>" height="40" class="d-none" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                <span><?= SITE_NAME ?></span>
                            <?php endif; ?>
                        </a>

                        <!-- Main Actions - Desktop -->
                        <div class="d-flex align-items-center gap-2 gap-md-3 desktop-menu-items">
                            <a class="btn btn-sm btn-outline-primary d-flex flex-column align-items-center p-2" href="<?= BASE_URL ?>/stories" title="Stories">
                                <i class="bi bi-image fs-5"></i>
                                <small class="d-none d-md-inline">Stories</small>
                            </a>
                            
                            <a class="btn btn-sm btn-outline-primary d-flex flex-column align-items-center p-2" href="<?= BASE_URL ?>/reels" title="Reels">
                                <i class="bi bi-camera-reels fs-5"></i>
                                <small class="d-none d-md-inline">Reels</small>
                            </a>
                            
                            <a class="btn btn-sm btn-outline-primary d-flex flex-column align-items-center p-2" href="<?= BASE_URL ?>/podcasts" title="Podcasts">
                                <i class="bi bi-mic-fill fs-5"></i>
                                <small class="d-none d-md-inline">Podcasts</small>
                            </a>
                            
                            <a class="btn btn-sm btn-outline-primary d-flex flex-column align-items-center p-2" href="<?= BASE_URL ?>/case-threads" title="Case Threads">
                                <i class="bi bi-journal-text fs-5"></i>
                                <small class="d-none d-md-inline">Case Threads</small>
                            </a>
                            
                            <?php if (Security::isLoggedIn('user')): ?>
                            <!-- Notifications Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary d-flex flex-column align-items-center p-2 position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown">
                                    <i class="bi bi-bell fs-5"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display: none; font-size: 0.6rem;">
                                        0
                                    </span>
                                    <small class="d-none d-md-inline">Alerts</small>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 350px; max-height: 500px; overflow-y: auto;">
                                    <li class="dropdown-header d-flex justify-content-between align-items-center">
                                        <strong>Notifications</strong>
                                        <button class="btn btn-sm btn-link text-decoration-none p-0" id="markAllReadBtn" style="display: none;">Mark all read</button>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <div id="notificationList">
                                        <li class="text-center py-3 text-muted">
                                            <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                                            No notifications
                                        </li>
                                    </div>
                                </ul>
                            </div>
                            
                            <!-- User Account Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle d-flex flex-column align-items-center p-2" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle fs-5"></i>
                                    <small class="d-none d-md-inline"><?= htmlspecialchars(substr($_SESSION['user_name'] ?? 'Account', 0, 8)) ?></small>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/saved-articles.php">Saved Articles</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile.php">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">Logout</a></li>
                                </ul>
                            </div>
                            <?php else: ?>
                            <a class="btn btn-sm btn-primary d-flex flex-column align-items-center p-2" href="<?= BASE_URL ?>/login">
                                <i class="bi bi-box-arrow-in-right fs-5"></i>
                                <small class="d-none d-md-inline">Login</small>
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Mobile Buttons (Right) -->
                        <div class="mobile-buttons-wrapper d-flex d-md-none align-items-center gap-2">
                            <!-- Menu Button (Burger) -->
                            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-controls="mobileMenu" aria-expanded="false" aria-label="Open menu">
                                <i class="bi bi-list"></i>
                            </button>
                            
                            <!-- Login/User Button -->
                            <?php if (!Security::isLoggedIn('user')): ?>
                            <a class="mobile-login-btn" href="<?= BASE_URL ?>/login">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </a>
                            <?php else: ?>
                            <!-- User Account for Mobile -->
                            <div class="dropdown">
                                <button class="mobile-user-btn dropdown-toggle" type="button" id="userDropdownMobile" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/dashboard.php">Dashboard</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/saved-articles.php">Saved Articles</a></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/user/profile.php">Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php">Logout</a></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modern category navigation styles moved to assets/css/style.css -->
        
        <?php if (isset($category) && isset($subcategories)): ?>
            <!-- Show subcategories only if they exist on category page -->
            <?php if (!empty($subcategories)): ?>
            <div class="modern-category-nav">
                <div class="container-fluid px-3 py-3">
                    <div class="category-scroll-container">
                        <div class="category-scroll-wrapper">
                            <div class="d-flex gap-2 align-items-center">
                                <!-- Home Button -->
                                <a class="category-nav-item home-btn" href="<?= BASE_URL ?>" title="Home">
                                    <i class="bi bi-house-door-fill"></i>
                                    <span class="d-none d-md-inline">Home</span>
                                </a>
                                
                                <?php foreach ($subcategories as $sub): ?>
                                <a class="category-nav-item" href="<?= BASE_URL ?>/category/<?= $sub['slug'] ?>">
                                    <?php if ($sub['icon']): ?>
                                        <?php if (strpos($sub['icon'], 'class:') === 0): ?>
                                            <i class="<?= htmlspecialchars(substr($sub['icon'], 6)) ?>"></i>
                                        <?php else: ?>
                                            <img src="<?= UPLOADS_URL ?>/categories/<?= $sub['icon'] ?>" alt="" height="20" width="20">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="bi bi-folder-fill"></i>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($sub['name']) ?></span>
                                </a>
                                <?php endforeach; ?>
                                
                                <!-- Search Bar -->
                                <div class="modern-search-wrapper ms-auto">
                                    <form action="<?= BASE_URL ?>/search" method="GET" class="position-relative">
                                        <input type="text" name="q" class="modern-search-input" placeholder="🔍 Search articles..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
                                        <button type="submit" class="modern-search-btn" title="Search">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Show parent categories on home and other pages -->
            <div class="modern-category-nav">
                <div class="container-fluid px-3 py-3">
                    <div class="category-scroll-container">
                        <div class="category-scroll-wrapper">
                            <div class="d-flex gap-2 align-items-center">
                                <!-- Home Button -->
                                <a class="category-nav-item home-btn" href="<?= BASE_URL ?>" title="Home">
                                    <i class="bi bi-house-door-fill"></i>
                                    <span class="d-none d-md-inline">Home</span>
                                </a>
                                
                                <?php
                                $top_categories = getTopCategories();
                                foreach ($top_categories as $cat):
                                ?>
                                <a class="category-nav-item" href="<?= BASE_URL ?>/category/<?= $cat['slug'] ?>">
                                    <?php if ($cat['icon']): ?>
                                        <?php if (strpos($cat['icon'], 'class:') === 0): ?>
                                            <i class="<?= htmlspecialchars(substr($cat['icon'], 6)) ?>"></i>
                                        <?php else: ?>
                                            <img src="<?= UPLOADS_URL ?>/categories/<?= $cat['icon'] ?>" alt="" height="20" width="20">
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <i class="bi bi-folder-fill"></i>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($cat['name']) ?></span>
                                </a>
                                <?php endforeach; ?>
                                
                                <!-- Search Bar -->
                                <div class="modern-search-wrapper ms-auto">
                                    <form action="<?= BASE_URL ?>/search" method="GET" class="position-relative">
                                        <input type="text" name="q" class="modern-search-input" placeholder="🔍 Search articles..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" autocomplete="off">
                                        <button type="submit" class="modern-search-btn" title="Search">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <script>
            // Scroll indicator visibility
            document.addEventListener('DOMContentLoaded', function() {
                const scrollWrappers = document.querySelectorAll('.category-scroll-wrapper');
                
                scrollWrappers.forEach(wrapper => {
                    const container = wrapper.closest('.category-scroll-container');
                    const leftIndicator = container.querySelector('.scroll-indicator.left');
                    const rightIndicator = container.querySelector('.scroll-indicator.right');
                    
                    function updateIndicators() {
                        const scrollLeft = wrapper.scrollLeft;
                        const scrollWidth = wrapper.scrollWidth;
                        const clientWidth = wrapper.clientWidth;
                        
                        if (scrollWidth > clientWidth) {
                            if (leftIndicator) leftIndicator.style.display = scrollLeft > 20 ? 'block' : 'none';
                            if (rightIndicator) rightIndicator.style.display = scrollLeft < scrollWidth - clientWidth - 20 ? 'block' : 'none';
                        }
                    }
                    
                    wrapper.addEventListener('scroll', updateIndicators);
                    window.addEventListener('resize', updateIndicators);
                    updateIndicators();
                });
            });
        </script>
    </header>

    <!-- Breaking News Ticker (if any) -->
    <?php
    $db = Database::getInstance();
    $breaking_news = $db->fetchAll("SELECT * FROM articles WHERE is_breaking = 1 AND status = 'published' ORDER BY published_at DESC LIMIT 5");
    if ($breaking_news):
    ?>
    <div class="breaking-news bg-danger text-white py-2 overflow-hidden">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <span class="badge bg-white text-danger fw-bold px-3 py-2 fs-6">
                        <i class="bi bi-lightning-fill me-1"></i>BREAKING NEWS
                    </span>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="breaking-news-slider d-flex align-items-center">
                        <?php foreach ($breaking_news as $index => $news): ?>
                        <a href="<?= BASE_URL ?>/article/<?= $news['slug'] ?>" class="text-white text-decoration-none text-nowrap me-4 pe-4 border-end border-white border-opacity-50">
                            <i class="bi bi-dot text-warning fs-4 align-middle"></i>
                            <span class="fw-semibold"><?= htmlspecialchars($news['title']) ?></span>
                        </a>
                        <?php endforeach; ?>
                        <!-- Duplicate for seamless loop -->
                        <?php foreach ($breaking_news as $index => $news): ?>
                        <a href="<?= BASE_URL ?>/article/<?= $news['slug'] ?>" class="text-white text-decoration-none text-nowrap me-4 pe-4 border-end border-white border-opacity-50">
                            <i class="bi bi-dot text-warning fs-4 align-middle"></i>
                            <span class="fw-semibold"><?= htmlspecialchars($news['title']) ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .breaking-news-slider {
            animation: scroll-left 30s linear infinite;
            will-change: transform;
        }
        
        .breaking-news-slider:hover {
            animation-play-state: paused;
        }
        
        @keyframes scroll-left {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }
        
        .breaking-news a:hover {
            text-decoration: underline !important;
        }
    </style>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content py-4">

    <!-- Mobile Menu Script -->
    <script>
        // Mobile menu open/close with basic accessibility and Escape support
        document.addEventListener('DOMContentLoaded', function() {
            const menuBtn = document.getElementById('mobileMenuBtn');
            const menuClose = document.getElementById('mobileMenuClose');
            const menuBackdrop = document.getElementById('mobileMenuBackdrop');
            const menu = document.getElementById('mobileMenu');
            let lastFocused = null;

            function openMenu() {
                lastFocused = document.activeElement;
                menu.classList.add('active');
                menuBackdrop.classList.add('active');
                menu.setAttribute('aria-hidden', 'false');
                menuBackdrop.setAttribute('aria-hidden', 'false');
                if (menuBtn) menuBtn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
                // Move focus to menu
                const firstFocusable = menu.querySelector('button, a, input, [tabindex]:not([tabindex="-1"])');
                if (firstFocusable) firstFocusable.focus();

                // Listen for escape key
                document.addEventListener('keydown', handleKeydown);
            }

            function closeMenu() {
                menu.classList.remove('active');
                menuBackdrop.classList.remove('active');
                menu.setAttribute('aria-hidden', 'true');
                menuBackdrop.setAttribute('aria-hidden', 'true');
                if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
                if (lastFocused) lastFocused.focus();
                document.removeEventListener('keydown', handleKeydown);
            }

            function handleKeydown(e) {
                if (e.key === 'Escape') closeMenu();
                // Simple trap: if focus moves outside, bring it back (very small/simple trap)
                if (e.key === 'Tab') {
                    const focusable = menu.querySelectorAll('button, a, input, [tabindex]:not([tabindex="-1"])');
                    if (!focusable.length) return;
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault(); last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault(); first.focus();
                    }
                }
            }

            if (menuBtn) {
                menuBtn.addEventListener('click', function(){
                    const expanded = menuBtn.getAttribute('aria-expanded') === 'true';
                    if (expanded) closeMenu(); else openMenu();
                });
                menuBtn.setAttribute('aria-controls', 'mobileMenu');
                menuBtn.setAttribute('aria-expanded', 'false');
                menuBtn.setAttribute('aria-label', 'Open menu');
            }

            if (menuClose) {
                menuClose.addEventListener('click', closeMenu);
            }

            if (menuBackdrop) {
                menuBackdrop.addEventListener('click', closeMenu);
            }
        });
    </script>
    
    <!-- Cookie Consent & Tracking Scripts -->
    <script src="<?= ASSETS_URL ?>/js/tracking.js"></script>
    
    <!-- Notification System -->
    <?php if (Security::isLoggedIn('user')): ?>
    <script src="<?= ASSETS_URL ?>/js/notifications.js"></script>
    <?php endif; ?>
