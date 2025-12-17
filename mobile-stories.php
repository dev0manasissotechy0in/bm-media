<?php
/**
 * Mobile Stories - Single image stories optimized for mobile sharing
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Initialize ads manager for mobile
require_once 'includes/AdsManager.php';
AdsManager::init($db);

// Get active mobile stories (not expired)
$stories = $db->fetchAll("
    SELECT m.*, c.name as category_name, c.slug as category_slug
    FROM mobile_stories m
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE m.status = 'active' 
    AND (m.expires_at IS NULL OR m.expires_at > NOW())
    ORDER BY m.created_at DESC
");

// Group stories by category
$grouped_stories = [];
foreach ($stories as $story) {
    $cat = $story['category_name'] ?? 'General';
    if (!isset($grouped_stories[$cat])) {
        $grouped_stories[$cat] = [];
    }
    $grouped_stories[$cat][] = $story;
}

$page_title = 'Mobile Stories';
$page_description = 'Quick mobile stories - one image, one story';

include 'includes/header.php';
?>

<!-- Mobile Banner Ad (Top) -->
<?php echo AdsManager::showBannerAd(); ?>
<?php echo AdsManager::showCustomAd('mobile', 'top'); ?>

<main class="container my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4"><i class="bi bi-phone text-primary"></i> Mobile Stories</h1>
            <p class="lead text-muted">Quick updates • One image per story • Swipe to view</p>
        </div>
    </div>
    
    <?php if (empty($grouped_stories)): ?>
    <div class="text-center py-5">
        <i class="bi bi-phone text-muted" style="font-size: 5rem;"></i>
        <h3 class="mt-4">No Active Mobile Stories</h3>
        <p class="text-muted">Check back later for new updates</p>
    </div>
    <?php else: ?>
    
    <!-- Mobile Stories Grid -->
    <div class="stories-container">
        <?php foreach ($grouped_stories as $category => $category_stories): ?>
        <div class="story-group mb-4">
            <h5 class="text-muted mb-3"><?= htmlspecialchars($category) ?></h5>
            <div class="d-flex gap-3 overflow-auto pb-3">
                <?php foreach ($category_stories as $story): ?>
                <div class="story-item" onclick="viewMobileStory(<?= $story['id'] ?>)">
                    <div class="story-ring">
                        <div class="story-avatar">
                            <img src="<?= htmlspecialchars($story['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($story['title']) ?>"
                                 style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <div class="story-play-icon">
                                <i class="bi bi-phone"></i>
                            </div>
                        </div>
                    </div>
                    <p class="story-title"><?= htmlspecialchars(substr($story['title'], 0, 20)) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Mobile Ad (Middle) -->
    <?php echo AdsManager::showArticleAd(); ?>
    <?php echo AdsManager::showCustomAd('mobile', 'middle'); ?>
    
    <?php endif; ?>
</main>

<!-- Mobile Story Viewer Modal -->
<div class="modal fade" id="mobileStoryModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <!-- Progress Bar -->
                <div class="story-progress-container">
                    <div class="story-progress-bar" id="mobileStoryProgress"></div>
                </div>
                
                <!-- Close Button -->
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" 
                        data-bs-dismiss="modal" onclick="stopMobileStory()"></button>
                
                <!-- Story Content -->
                <div id="mobileStoryContent" class="d-flex align-items-center justify-content-center" style="height:100vh;">
                    <!-- Story image will be loaded here -->
                </div>
                
                <!-- Story Info -->
                <div class="position-absolute bottom-0 start-0 end-0 p-4 text-white z-2" 
                     style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                    <h4 id="mobileStoryTitle"></h4>
                    <p id="mobileStoryLink" class="mb-0"></p>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <small class="text-white-50">
                            <i class="bi bi-eye"></i> <span id="mobileStoryViews">0</span> views
                        </small>
                        <button class="btn btn-sm btn-light" onclick="shareMobileStory()" id="mobileShareBtn">
                            <i class="bi bi-share"></i> Share
                        </button>
                    </div>
                </div>
                
                <!-- Navigation -->
                <button class="btn btn-link position-absolute start-0 top-50 translate-middle-y text-white" 
                        onclick="previousMobileStory()" id="mobilePrevBtn" style="font-size: 2rem; display:none;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-white" 
                        onclick="nextMobileStory()" id="mobileNextBtn" style="font-size: 2rem;">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.stories-container {
    max-width: 100%;
}

.story-group::-webkit-scrollbar {
    height: 8px;
}

.story-group::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.story-group::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.story-item {
    cursor: pointer;
    text-align: center;
    flex-shrink: 0;
}

.story-ring {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    display: inline-block;
    position: relative;
}

.story-avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    border: 3px solid white;
    overflow: hidden;
    background: #f0f0f0;
    position: relative;
}

.story-play-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0,0,0,0.6);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.story-title {
    margin-top: 8px;
    font-size: 12px;
    color: #333;
    max-width: 80px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.story-progress-container {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    height: 3px;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
    z-index: 10;
}

.story-progress-bar {
    height: 100%;
    background: white;
    border-radius: 2px;
    width: 0%;
    transition: width 0.1s linear;
}

#mobileStoryContent {
    transition: opacity 0.3s ease-in-out;
}

#mobileStoryContent.fade-out {
    opacity: 0;
}

#mobileStoryContent img {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
}
</style>

<script>
let currentMobileStoryIndex = 0;
let allMobileStories = <?= json_encode(array_values($stories)) ?>;
let mobileStoryModal;
let mobileStoryTimer;
let mobileProgressInterval;

function viewMobileStory(storyId) {
    currentMobileStoryIndex = allMobileStories.findIndex(s => s.id == storyId);
    if (currentMobileStoryIndex === -1) currentMobileStoryIndex = 0;
    
    loadMobileStory(currentMobileStoryIndex);
    
    if (!mobileStoryModal) {
        mobileStoryModal = new bootstrap.Modal(document.getElementById('mobileStoryModal'));
    }
    mobileStoryModal.show();
}

function loadMobileStory(index) {
    if (index < 0 || index >= allMobileStories.length) return;
    
    const story = allMobileStories[index];
    currentMobileStoryIndex = index;
    
    // Update UI with fade transition
    const content = document.getElementById('mobileStoryContent');
    content.classList.add('fade-out');
    
    setTimeout(() => {
        document.getElementById('mobileStoryTitle').textContent = story.title;
        document.getElementById('mobileStoryLink').innerHTML = story.link ? 
            `<a href="${story.link}" target="_blank" class="text-white"><i class="bi bi-link-45deg"></i> Read more</a>` : '';
        document.getElementById('mobileStoryViews').textContent = story.views_count;
        
        // Load image
        content.innerHTML = `<img src="${story.image_url}" alt="${story.title}">`;
        
        // Fade in
        setTimeout(() => {
            content.classList.remove('fade-out');
        }, 50);
    }, 300);
    
    // Update navigation buttons
    document.getElementById('mobilePrevBtn').style.display = index > 0 ? 'block' : 'none';
    document.getElementById('mobileNextBtn').style.display = index < allMobileStories.length - 1 ? 'block' : 'none';
    
    // Start progress and auto-advance
    startMobileProgress(story.duration || 5);
    
    // Track view
    fetch('<?= BASE_URL ?>/api/mobile-stories/view.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + story.id
    });
}

function startMobileProgress(duration) {
    stopMobileStory();
    
    let progress = 0;
    const progressBar = document.getElementById('mobileStoryProgress');
    const increment = 100 / (duration * 10);
    
    mobileProgressInterval = setInterval(() => {
        progress += increment;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            nextMobileStory();
        }
    }, 100);
}

function stopMobileStory() {
    if (mobileProgressInterval) {
        clearInterval(mobileProgressInterval);
        mobileProgressInterval = null;
    }
    if (mobileStoryTimer) {
        clearTimeout(mobileStoryTimer);
        mobileStoryTimer = null;
    }
    document.getElementById('mobileStoryProgress').style.width = '0%';
}

function nextMobileStory() {
    if (currentMobileStoryIndex < allMobileStories.length - 1) {
        loadMobileStory(currentMobileStoryIndex + 1);
    } else {
        mobileStoryModal.hide();
    }
}

function previousMobileStory() {
    if (currentMobileStoryIndex > 0) {
        loadMobileStory(currentMobileStoryIndex - 1);
    }
}

function shareMobileStory() {
    stopMobileStory();
    const story = allMobileStories[currentMobileStoryIndex];
    const storyUrl = `<?= BASE_URL ?>/mobile-story.php?id=${story.id}`;
    const storyTitle = story.title;
    
    if (navigator.share) {
        navigator.share({
            title: storyTitle,
            text: 'Check out this mobile story!',
            url: storyUrl
        }).then(() => {
            startMobileProgress(story.duration || 5);
        }).catch(() => {
            startMobileProgress(story.duration || 5);
        });
    } else {
        navigator.clipboard.writeText(storyUrl).then(() => {
            alert('Story link copied to clipboard!');
            startMobileProgress(story.duration || 5);
        });
    }
}

// Clean up on modal close
document.getElementById('mobileStoryModal').addEventListener('hidden.bs.modal', function () {
    stopMobileStory();
    document.getElementById('mobileStoryContent').innerHTML = '';
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (mobileStoryModal && mobileStoryModal._isShown) {
        if (e.key === 'ArrowLeft') previousMobileStory();
        if (e.key === 'ArrowRight') nextMobileStory();
        if (e.key === 'Escape') mobileStoryModal.hide();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
