    </main>

    <!-- Footer -->
    <footer class="footer bg-dark text-white pt-5 pb-3 mt-5">
        <div class="container">
            <div class="row">
                <!-- About Section -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3"><?= SITE_NAME ?></h5>
                    <p><?= getSiteSetting('site_tagline', 'Your Trusted News Source') ?></p>
                    <div class="social-links">
                        <?php
                        // Get social media URLs from settings table
                        $db = Database::getInstance();
                        $social_settings = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('facebook_url', 'twitter_url', 'instagram_url', 'youtube_url')");
                        $social_urls = [];
                        foreach ($social_settings as $setting) {
                            $social_urls[$setting['setting_key']] = $setting['setting_value'];
                        }
                        $facebook_url = $social_urls['facebook_url'] ?? '';
                        $twitter_url = $social_urls['twitter_url'] ?? '';
                        $instagram_url = $social_urls['instagram_url'] ?? '';
                        $youtube_url = $social_urls['youtube_url'] ?? '';
                        ?>
                        <?php if (!empty($facebook_url)): ?>
                            <a href="<?= htmlspecialchars($facebook_url) ?>" target="_blank" rel="noopener" class="text-white me-3"><i class="bi bi-facebook fs-5"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($twitter_url)): ?>
                            <a href="<?= htmlspecialchars($twitter_url) ?>" target="_blank" rel="noopener" class="text-white me-3"><i class="bi bi-twitter fs-5"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($instagram_url)): ?>
                            <a href="<?= htmlspecialchars($instagram_url) ?>" target="_blank" rel="noopener" class="text-white me-3"><i class="bi bi-instagram fs-5"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($youtube_url)): ?>
                            <a href="<?= htmlspecialchars($youtube_url) ?>" target="_blank" rel="noopener" class="text-white me-3"><i class="bi bi-youtube fs-5"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?= BASE_URL ?>" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="<?= BASE_URL ?>/contact.php" class="text-white-50 text-decoration-none">Contact Us</a></li>
                        <?php
                        // Get custom pages for footer
                        try {
                            $footer_pages = $db->fetchAll("SELECT title, slug FROM custom_pages WHERE show_in_footer = 1 AND status = 'published' ORDER BY order_id ASC, title ASC LIMIT 8");
                            foreach ($footer_pages as $page):
                            ?>
                            <li><a href="<?= BASE_URL ?>/page.php?slug=<?= $page['slug'] ?>" class="text-white-50 text-decoration-none"><?= htmlspecialchars($page['title']) ?></a></li>
                            <?php 
                            endforeach;
                        } catch (Exception $e) {
                            // Table doesn't exist yet or no pages - show nothing
                        }
                        ?>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Categories</h5>
                    <ul class="list-unstyled">
                        <?php
                        $footer_categories = getAllCategories();
                        foreach (array_slice($footer_categories, 0, 6) as $category):
                        ?>
                        <li>
                            <a href="<?= BASE_URL ?>/category/<?= $category['slug'] ?>" class="text-white-50 text-decoration-none">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="mb-3">Newsletter</h5>
                    <p>Subscribe to get latest news updates</p>
                    <form id="newsletterForm" class="newsletter-form">
                        <div class="input-group mb-3">
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                        <div id="newsletterMessage"></div>
                    </form>
                </div>
            </div>

            <hr class="bg-secondary">

            <!-- Footer Bottom Ad -->
            <?php 
            if (class_exists('AdsManager')) {
                echo AdsManager::showCustomAd('footer', 'bottom');
            }
            ?>

            <!-- Copyright -->
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">
                        <a href="<?= BASE_URL ?>/sitemap.php" class="text-white-50 text-decoration-none me-3">Sitemap</a>
                        <a href="<?= BASE_URL ?>/rss.php" class="text-white-50 text-decoration-none">RSS Feed</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary back-to-top" title="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Toast Container -->
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11000;" id="toastContainer"></div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>
    
    <!-- Newsletter Subscription -->
    <script>
    $(document).ready(function() {
        $('#newsletterForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '<?= API_URL ?>/newsletter/subscribe.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#newsletterMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                        $('#newsletterForm')[0].reset();
                    } else {
                        $('#newsletterMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },
                error: function() {
                    $('#newsletterMessage').html('<div class="alert alert-danger">An error occurred. Please try again.</div>');
                }
            });
        });
    });
    </script>
    
    <?php if (ENABLE_WEB_NOTIFICATIONS && FIREBASE_SERVER_KEY): ?>
    <!-- Web Notifications -->
    <script src="<?= ASSETS_URL ?>/js/notifications.js"></script>
    <?php endif; ?>
    
</body>
</html>
