<?php
/**
 * Stories - 24-hour disappearing content
 */

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Functions.php';
require_once 'includes/Session.php';

$db = Database::getInstance();

// Get active stories (not expired)
$stories = $db->fetchAll("
    SELECT s.*, c.name as category_name, c.slug as category_slug
    FROM stories s
    LEFT JOIN categories c ON s.category_id = c.id
    WHERE s.status = 'active' 
    AND (s.expires_at IS NULL OR s.expires_at > NOW())
    ORDER BY s.created_at DESC
");

// Get media for each story from story_media table
foreach ($stories as &$story) {
    $story_media = $db->fetchAll("SELECT * FROM story_media WHERE story_id = ? ORDER BY order_id ASC", [$story['id']]);
    
    // If story_media exists, use it; otherwise fall back to stories.media_url (backward compatibility)
    if (!empty($story_media)) {
        $story['media_items'] = $story_media;
        // Use first media as thumbnail
        $story['thumbnail_url'] = $story_media[0]['media_url'];
        $story['thumbnail_type'] = $story_media[0]['media_type'];
    } else {
        // Backward compatibility: single media from stories table
        $story['media_items'] = [[
            'media_url' => $story['media_url'],
            'media_type' => $story['media_type']
        ]];
        $story['thumbnail_url'] = $story['media_url'];
        $story['thumbnail_type'] = $story['media_type'];
    }
}
unset($story);

// Group stories by category
$grouped_stories = [];
foreach ($stories as $story) {
    $cat = $story['category_name'] ?? 'General';
    if (!isset($grouped_stories[$cat])) {
        $grouped_stories[$cat] = [];
    }
    $grouped_stories[$cat][] = $story;
}

$page_title = 'Stories';
$page_description = '24-hour news stories and updates';

include 'includes/header.php';
?>

<main class="container my-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4"><i class="bi bi-camera-reels text-primary"></i> Stories</h1>
            <p class="lead text-muted">24-hour news updates • Tap to view</p>
        </div>
    </div>
    
    <?php if (empty($grouped_stories)): ?>
    <div class="text-center py-5">
        <i class="bi bi-camera-video text-muted" style="font-size: 5rem;"></i>
        <h3 class="mt-4">No Active Stories</h3>
        <p class="text-muted">Check back later for new stories</p>
    </div>
    <?php else: ?>
    
    <!-- Stories Grid -->
    <div class="stories-container">
        <?php foreach ($grouped_stories as $category => $category_stories): ?>
        <div class="story-group mb-4">
            <h5 class="text-muted mb-3"><?= htmlspecialchars($category) ?></h5>
            <div class="d-flex gap-3 overflow-auto pb-3">
                <?php foreach ($category_stories as $story): ?>
                <div class="story-item" onclick="viewStory(<?= $story['id'] ?>)">
                    <div class="story-ring">
                        <div class="story-avatar">
                            <?php if ($story['thumbnail_type'] === 'video'): ?>
                            <video src="<?= htmlspecialchars($story['thumbnail_url']) ?>" 
                                   style="width:100%; height:100%; object-fit:cover; border-radius:50%;"></video>
                            <?php else: ?>
                            <img src="<?= htmlspecialchars($story['thumbnail_url']) ?>" 
                                 alt="<?= htmlspecialchars($story['title']) ?>"
                                 style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                            <?php endif; ?>
                            <div class="story-play-icon">
                                <i class="bi bi-play-fill"></i>
                            </div>
                            <?php if (count($story['media_items']) > 1): ?>
                            <div class="story-media-count"><?= count($story['media_items']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="story-title"><?= htmlspecialchars(substr($story['title'], 0, 20)) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<!-- Story Viewer Modal -->
<div class="modal fade" id="storyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <!-- Progress Bars (Divided) -->
                <div class="story-progress-container" id="progressContainer">
                    <!-- Progress segments will be added dynamically -->
                </div>
                
                <!-- Close Button -->
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" 
                        data-bs-dismiss="modal" onclick="stopStory()"></button>
                
                <!-- Story Content -->
                <div id="storyContent" class="d-flex align-items-center justify-content-center" style="height:100vh;">
                    <!-- Story media will be loaded here -->
                </div>
                
                <!-- Story Info -->
                <div class="position-absolute bottom-0 start-0 end-0 p-4 text-white z-2" 
                     style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                    <h4 id="storyTitle"></h4>
                    <p id="storyLink" class="mb-0"></p>
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">
                                <i class="bi bi-eye"></i> <span id="storyViews">0</span> views
                            </small>
                            <small class="text-white-50 ms-3" id="mediaCounter" style="display:none;">
                                <i class="bi bi-images"></i> <span id="currentMediaIndex">1</span>/<span id="totalMediaCount">1</span>
                            </small>
                        </div>
                        <button class="btn btn-sm btn-light" onclick="shareStory()" id="shareBtn">
                            <i class="bi bi-share"></i> Share
                        </button>
                    </div>
                </div>
                
                <!-- Navigation -->
                <button class="btn btn-link position-absolute start-0 top-50 translate-middle-y text-white" 
                        onclick="previousItem()" id="prevBtn" style="font-size: 2rem; display:none;">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-link position-absolute end-0 top-50 translate-middle-y text-white" 
                        onclick="nextItem()" id="nextBtn" style="font-size: 2rem;">
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
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
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

.story-media-count {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
}

.story-progress-container {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    display: flex;
    gap: 4px;
    z-index: 10;
}

.story-progress-segment {
    flex: 1;
    height: 3px;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
    overflow: hidden;
}

.story-progress-bar {
    height: 100%;
    background: white;
    width: 0%;
    transition: width 0.1s linear;
}

#storyContent {
    transition: opacity 0.3s ease-in-out;
}

#storyContent.fade-out {
    opacity: 0;
}

#storyContent img,
#storyContent video {
    max-width: 100%;
    max-height: 90vh;
    object-fit: contain;
}
</style>

<script>
let currentStoryIndex = 0;
let currentMediaIndex = 0;
let allStories = <?= json_encode(array_values($stories)) ?>;
let storyModal;
let storyTimer;
let progressInterval;

function viewStory(storyId) {
    currentStoryIndex = allStories.findIndex(s => s.id == storyId);
    if (currentStoryIndex === -1) currentStoryIndex = 0;
    currentMediaIndex = 0;
    
    loadStory(currentStoryIndex);
    
    if (!storyModal) {
        storyModal = new bootstrap.Modal(document.getElementById('storyModal'));
    }
    storyModal.show();
}

function loadStory(index) {
    if (index < 0 || index >= allStories.length) return;
    
    const story = allStories[index];
    currentStoryIndex = index;
    currentMediaIndex = 0;
    
    // Update UI
    document.getElementById('storyTitle').textContent = story.title;
    document.getElementById('storyLink').innerHTML = story.link ? 
        `<a href="${story.link}" target="_blank" class="text-white"><i class="bi bi-link-45deg"></i> Read full story</a>` : '';
    document.getElementById('storyViews').textContent = story.views_count;
    
    // Create divided progress bars
    const progressContainer = document.getElementById('progressContainer');
    progressContainer.innerHTML = '';
    story.media_items.forEach((_, idx) => {
        const segment = document.createElement('div');
        segment.className = 'story-progress-segment';
        segment.innerHTML = `<div class="story-progress-bar" id="progress-${idx}"></div>`;
        progressContainer.appendChild(segment);
    });
    
    // Update media counter
    if (story.media_items && story.media_items.length > 1) {
        document.getElementById('mediaCounter').style.display = 'inline-block';
        document.getElementById('totalMediaCount').textContent = story.media_items.length;
    } else {
        document.getElementById('mediaCounter').style.display = 'none';
    }
    
    // Load first media
    loadMedia(currentStoryIndex, currentMediaIndex);
    
    // Track view
    fetch('<?= BASE_URL ?>/api/stories/view.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + story.id
    });
}

function loadMedia(storyIndex, mediaIndex) {
    const story = allStories[storyIndex];
    if (!story.media_items || mediaIndex < 0 || mediaIndex >= story.media_items.length) return;
    
    const media = story.media_items[mediaIndex];
    currentMediaIndex = mediaIndex;
    
    // Smooth transition
    const content = document.getElementById('storyContent');
    content.classList.add('fade-out');
    
    setTimeout(() => {
        // Load media
        if (media.media_type === 'video') {
            content.innerHTML = `<video src="${media.media_url}" autoplay muted style="max-width:100%; max-height:90vh; object-fit:contain;"></video>`;
        } else {
            content.innerHTML = `<img src="${media.media_url}" alt="${story.title}" style="max-width:100%; max-height:90vh; object-fit:contain;">`;
        }
        
        // Fade in
        setTimeout(() => {
            content.classList.remove('fade-out');
        }, 50);
    }, 300);
    
    // Update media counter
    if (story.media_items.length > 1) {
        document.getElementById('currentMediaIndex').textContent = mediaIndex + 1;
    }
    
    // Update navigation buttons
    updateNavigationButtons();
    
    // Start progress and auto-advance
    startProgress(storyIndex, mediaIndex);
}

function updateNavigationButtons() {
    const story = allStories[currentStoryIndex];
    const hasPrevMedia = currentMediaIndex > 0;
    const hasNextMedia = currentMediaIndex < story.media_items.length - 1;
    const hasPrevStory = currentStoryIndex > 0;
    const hasNextStory = currentStoryIndex < allStories.length - 1;
    
    document.getElementById('prevBtn').style.display = (hasPrevMedia || hasPrevStory) ? 'block' : 'none';
    document.getElementById('nextBtn').style.display = (hasNextMedia || hasNextStory) ? 'block' : 'none';
}

function startProgress(storyIndex, mediaIndex) {
    stopStory();
    
    const story = allStories[storyIndex];
    const duration = story.duration || 5;
    
    // Fill completed progress bars
    for (let i = 0; i < mediaIndex; i++) {
        const bar = document.getElementById(`progress-${i}`);
        if (bar) bar.style.width = '100%';
    }
    
    // Reset future progress bars
    for (let i = mediaIndex + 1; i < story.media_items.length; i++) {
        const bar = document.getElementById(`progress-${i}`);
        if (bar) bar.style.width = '0%';
    }
    
    // Animate current progress bar
    let progress = 0;
    const currentBar = document.getElementById(`progress-${mediaIndex}`);
    if (!currentBar) return;
    
    const increment = 100 / (duration * 10);
    
    progressInterval = setInterval(() => {
        progress += increment;
        currentBar.style.width = progress + '%';
        
        if (progress >= 100) {
            nextItem();
        }
    }, 100);
}

function stopStory() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
    if (storyTimer) {
        clearTimeout(storyTimer);
        storyTimer = null;
    }
}

function nextItem() {
    const story = allStories[currentStoryIndex];
    
    // Check if there's next media in current story
    if (currentMediaIndex < story.media_items.length - 1) {
        loadMedia(currentStoryIndex, currentMediaIndex + 1);
    }
    // Move to next story
    else if (currentStoryIndex < allStories.length - 1) {
        loadStory(currentStoryIndex + 1);
    }
    // Close modal if last item
    else {
        storyModal.hide();
    }
}

function previousItem() {
    // Check if there's previous media in current story
    if (currentMediaIndex > 0) {
        loadMedia(currentStoryIndex, currentMediaIndex - 1);
    }
    // Move to previous story
    else if (currentStoryIndex > 0) {
        currentStoryIndex--;
        const prevStory = allStories[currentStoryIndex];
        currentMediaIndex = prevStory.media_items.length - 1; // Start from last media
        loadStory(currentStoryIndex);
        currentMediaIndex = prevStory.media_items.length - 1;
        loadMedia(currentStoryIndex, currentMediaIndex);
    }
}

// Share function
function shareStory() {
    stopStory();
    const story = allStories[currentStoryIndex];
    const storyUrl = `<?= BASE_URL ?>/story.php?id=${story.id}`;
    const storyTitle = story.title;
    
    if (navigator.share) {
        // Use Web Share API if available
        navigator.share({
            title: storyTitle,
            text: 'Check out this story!',
            url: storyUrl
        }).then(() => {
            startProgress(currentStoryIndex, currentMediaIndex);
        }).catch(() => {
            startProgress(currentStoryIndex, currentMediaIndex);
        });
    } else {
        // Fallback: Copy to clipboard
        navigator.clipboard.writeText(storyUrl).then(() => {
            alert('Story link copied to clipboard!');
            startProgress(currentStoryIndex, currentMediaIndex);
        });
    }
}

// Clean up on modal close
document.getElementById('storyModal').addEventListener('hidden.bs.modal', function () {
    stopStory();
    document.getElementById('storyContent').innerHTML = '';
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (storyModal && storyModal._isShown) {
        if (e.key === 'ArrowLeft') previousItem();
        if (e.key === 'ArrowRight') nextItem();
        if (e.key === 'Escape') storyModal.hide();
    }
});
</script>

<?php include 'includes/footer.php'; ?>