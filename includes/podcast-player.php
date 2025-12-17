<!-- Podcast Player -->
<div id="podcastPlayerWrapper" class="podcast-player-sticky" style="display: none;">
    <div class="podcast-player-container">
        <div class="container-fluid">
            <div class="row align-items-center g-3">
                <!-- Album Art & Info -->
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <img id="playerAlbumArt" src="" alt="Podcast" class="player-album-art me-3">
                        <div class="player-info">
                            <h6 id="playerTitle" class="mb-0 text-truncate">Episode Title</h6>
                            <small id="playerAuthor" class="text-muted text-truncate">Podcast Name</small>
                        </div>
                    </div>
                </div>
                
                <!-- Player Controls -->
                <div class="col-md-6">
                    <div class="player-controls">
                        <!-- Control Buttons -->
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                            <button id="prevBtn" class="btn-player" title="Previous">
                                <i class="bi bi-skip-start-fill"></i>
                            </button>
                            <button id="rewind10Btn" class="btn-player-sm" title="Rewind 10s">
                                <i class="bi bi-arrow-counterclockwise"></i>
                                <span class="small">10</span>
                            </button>
                            <button id="playPauseBtn" class="btn-player-main" title="Play/Pause">
                                <i class="bi bi-play-fill" id="playPauseIcon"></i>
                            </button>
                            <button id="forward30Btn" class="btn-player-sm" title="Forward 30s">
                                <i class="bi bi-arrow-clockwise"></i>
                                <span class="small">30</span>
                            </button>
                            <button id="nextBtn" class="btn-player" title="Next">
                                <i class="bi bi-skip-end-fill"></i>
                            </button>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="d-flex align-items-center gap-2">
                            <span id="currentTime" class="time-display">0:00</span>
                            <div class="progress-bar-container flex-grow-1">
                                <input type="range" id="progressBar" class="progress-bar-slider" min="0" max="100" value="0">
                                <div id="progressFill" class="progress-fill"></div>
                            </div>
                            <span id="totalDuration" class="time-display">0:00</span>
                        </div>
                    </div>
                </div>
                
                <!-- Volume & Actions -->
                <div class="col-md-3">
                    <div class="d-flex justify-content-end align-items-center gap-3">
                        <!-- Playback Speed -->
                        <div class="dropdown">
                            <button class="btn-player dropdown-toggle" type="button" id="speedDropdown" data-bs-toggle="dropdown" title="Playback Speed">
                                <span id="speedDisplay">1x</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item speed-option" data-speed="0.5">0.5x</a></li>
                                <li><a class="dropdown-item speed-option" data-speed="0.75">0.75x</a></li>
                                <li><a class="dropdown-item speed-option active" data-speed="1">1x</a></li>
                                <li><a class="dropdown-item speed-option" data-speed="1.25">1.25x</a></li>
                                <li><a class="dropdown-item speed-option" data-speed="1.5">1.5x</a></li>
                                <li><a class="dropdown-item speed-option" data-speed="2">2x</a></li>
                            </ul>
                        </div>
                        
                        <!-- Volume Control -->
                        <div class="volume-control">
                            <button id="volumeBtn" class="btn-player" title="Volume">
                                <i class="bi bi-volume-up-fill" id="volumeIcon"></i>
                            </button>
                            <input type="range" id="volumeSlider" class="volume-slider" min="0" max="100" value="100">
                        </div>
                        
                        <!-- Close Player -->
                        <button id="closePlayerBtn" class="btn-player" title="Close Player">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Hidden Audio Element -->
    <audio id="mainAudioPlayer" preload="metadata"></audio>
</div>

<style>
.podcast-player-sticky {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
    z-index: 1050;
    padding: 15px 0;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.podcast-player-container {
    color: white;
}

.player-album-art {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.player-info h6 {
    font-weight: 600;
    max-width: 200px;
}

.player-info small {
    display: block;
    max-width: 200px;
}

.btn-player, .btn-player-sm, .btn-player-main {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    border-radius: 50%;
    transition: all 0.3s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-player {
    width: 40px;
    height: 40px;
    font-size: 18px;
}

.btn-player-sm {
    width: 35px;
    height: 35px;
    font-size: 14px;
    position: relative;
}

.btn-player-sm .small {
    position: absolute;
    bottom: 2px;
    right: 2px;
    font-size: 8px;
    font-weight: bold;
}

.btn-player-main {
    width: 50px;
    height: 50px;
    font-size: 24px;
    background: rgba(255,255,255,0.3);
}

.btn-player:hover, .btn-player-sm:hover, .btn-player-main:hover {
    background: rgba(255,255,255,0.4);
    transform: scale(1.1);
}

.progress-bar-container {
    position: relative;
    height: 6px;
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
    cursor: pointer;
}

.progress-bar-slider {
    position: absolute;
    width: 100%;
    height: 6px;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.progress-fill {
    height: 100%;
    background: white;
    border-radius: 3px;
    width: 0%;
    transition: width 0.1s linear;
    pointer-events: none;
}

.time-display {
    font-size: 13px;
    font-weight: 500;
    min-width: 45px;
    text-align: center;
}

.volume-control {
    position: relative;
    display: flex;
    align-items: center;
    gap: 10px;
}

.volume-slider {
    width: 80px;
    height: 4px;
    background: rgba(255,255,255,0.2);
    border-radius: 2px;
    outline: none;
    cursor: pointer;
    -webkit-appearance: none;
}

.volume-slider::-webkit-slider-thumb {
    -webkit-appearance: none;
    width: 12px;
    height: 12px;
    background: white;
    border-radius: 50%;
    cursor: pointer;
}

.volume-slider::-moz-range-thumb {
    width: 12px;
    height: 12px;
    background: white;
    border-radius: 50%;
    border: none;
    cursor: pointer;
}

.dropdown-menu {
    background: rgba(30, 30, 30, 0.95);
    border: none;
    backdrop-filter: blur(10px);
}

.dropdown-item {
    color: white;
    padding: 8px 16px;
}

.dropdown-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.dropdown-item.active {
    background: rgba(102, 126, 234, 0.8);
    color: white;
}

@media (max-width: 768px) {
    .podcast-player-sticky {
        padding: 10px 0;
    }
    
    .player-album-art {
        width: 45px;
        height: 45px;
    }
    
    .player-info h6 {
        font-size: 14px;
    }
    
    .player-info small {
        font-size: 11px;
    }
    
    .btn-player {
        width: 35px;
        height: 35px;
        font-size: 16px;
    }
    
    .btn-player-main {
        width: 45px;
        height: 45px;
        font-size: 20px;
    }
    
    .volume-slider {
        width: 60px;
    }
}
</style>

<script>
class PodcastPlayer {
    constructor() {
        this.audio = document.getElementById('mainAudioPlayer');
        this.playerWrapper = document.getElementById('podcastPlayerWrapper');
        this.currentEpisode = null;
        this.playlist = [];
        this.currentIndex = 0;
        
        this.initializeElements();
        this.attachEventListeners();
        this.loadSavedState();
    }
    
    initializeElements() {
        // Buttons
        this.playPauseBtn = document.getElementById('playPauseBtn');
        this.playPauseIcon = document.getElementById('playPauseIcon');
        this.prevBtn = document.getElementById('prevBtn');
        this.nextBtn = document.getElementById('nextBtn');
        this.rewind10Btn = document.getElementById('rewind10Btn');
        this.forward30Btn = document.getElementById('forward30Btn');
        this.closePlayerBtn = document.getElementById('closePlayerBtn');
        
        // Progress
        this.progressBar = document.getElementById('progressBar');
        this.progressFill = document.getElementById('progressFill');
        this.currentTime = document.getElementById('currentTime');
        this.totalDuration = document.getElementById('totalDuration');
        
        // Volume
        this.volumeBtn = document.getElementById('volumeBtn');
        this.volumeIcon = document.getElementById('volumeIcon');
        this.volumeSlider = document.getElementById('volumeSlider');
        
        // Info
        this.playerTitle = document.getElementById('playerTitle');
        this.playerAuthor = document.getElementById('playerAuthor');
        this.playerAlbumArt = document.getElementById('playerAlbumArt');
        
        // Speed
        this.speedDisplay = document.getElementById('speedDisplay');
    }
    
    attachEventListeners() {
        // Play/Pause
        this.playPauseBtn.addEventListener('click', () => this.togglePlayPause());
        
        // Navigation
        this.prevBtn.addEventListener('click', () => this.playPrevious());
        this.nextBtn.addEventListener('click', () => this.playNext());
        this.rewind10Btn.addEventListener('click', () => this.skip(-10));
        this.forward30Btn.addEventListener('click', () => this.skip(30));
        
        // Progress
        this.progressBar.addEventListener('input', (e) => this.seek(e.target.value));
        
        // Volume
        this.volumeBtn.addEventListener('click', () => this.toggleMute());
        this.volumeSlider.addEventListener('input', (e) => this.setVolume(e.target.value));
        
        // Speed
        document.querySelectorAll('.speed-option').forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                this.setPlaybackSpeed(parseFloat(e.target.dataset.speed));
            });
        });
        
        // Close
        this.closePlayerBtn.addEventListener('click', () => this.closePlayer());
        
        // Audio Events
        this.audio.addEventListener('timeupdate', () => this.updateProgress());
        this.audio.addEventListener('loadedmetadata', () => this.onMetadataLoaded());
        this.audio.addEventListener('ended', () => this.onEnded());
        this.audio.addEventListener('play', () => this.onPlay());
        this.audio.addEventListener('pause', () => this.onPause());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));
    }
    
    loadEpisode(episodeData) {
        this.currentEpisode = episodeData;
        
        // Validate audio URL
        if (!episodeData.audioUrl || episodeData.audioUrl === '') {
            console.error('No audio URL provided for episode:', episodeData);
            this.showError('Audio file not available for this episode.');
            return;
        }
        
        // Check if URL looks like an audio file
        if (!episodeData.audioUrl.match(/\.(mp3|wav|ogg|m4a|aac)(\?.*)?$/i) && 
            !episodeData.audioUrl.includes('/podcasts/')) {
            console.warn('Audio URL does not appear to be a direct audio file:', episodeData.audioUrl);
        }
        
        this.audio.src = episodeData.audioUrl;
        this.playerTitle.textContent = episodeData.title;
        this.playerAuthor.textContent = episodeData.author || 'Unknown';
        this.playerAlbumArt.src = episodeData.thumbnail || 'https://via.placeholder.com/80x80/6366f1/ffffff?text=Podcast';
        this.playerWrapper.style.display = 'block';
        
        // Handle audio load errors with detailed messages
        this.audio.onerror = (e) => {
            console.error('Failed to load audio from:', episodeData.audioUrl);
            console.error('Error details:', e);
            
            let errorMsg = 'Could not load audio file.\n\n';
            
            // Check if it's a CORS issue
            if (episodeData.audioUrl.startsWith('http') && !episodeData.audioUrl.includes(window.location.hostname)) {
                errorMsg += 'Possible causes:\n';
                errorMsg += '• External URL may have CORS restrictions\n';
                errorMsg += '• File may not exist at the provided URL\n';
                errorMsg += '• URL may not be a direct link to an audio file\n\n';
                errorMsg += 'Try uploading the file directly instead of using an external URL.';
            } else {
                errorMsg += 'The audio file may be missing or corrupted.\n';
                errorMsg += 'Please contact the administrator.';
            }
            
            this.showError(errorMsg);
        };
        
        // Save to localStorage
        this.saveState();
    }
    
    showError(message) {
        alert(message);
        // Optionally hide player or show error state
        this.playerTitle.textContent = 'Error Loading Audio';
        this.playPauseBtn.disabled = true;
    }
    
    loadPlaylist(episodes, startIndex = 0) {
        this.playlist = episodes;
        this.currentIndex = startIndex;
        this.loadEpisode(episodes[startIndex]);
    }
    
    togglePlayPause() {
        if (this.audio.paused) {
            this.audio.play();
        } else {
            this.audio.pause();
        }
    }
    
    onPlay() {
        this.playPauseIcon.classList.remove('bi-play-fill');
        this.playPauseIcon.classList.add('bi-pause-fill');
    }
    
    onPause() {
        this.playPauseIcon.classList.add('bi-play-fill');
        this.playPauseIcon.classList.remove('bi-pause-fill');
    }
    
    playPrevious() {
        if (this.playlist.length > 0 && this.currentIndex > 0) {
            this.currentIndex--;
            this.loadEpisode(this.playlist[this.currentIndex]);
            this.audio.play();
        }
    }
    
    playNext() {
        if (this.playlist.length > 0 && this.currentIndex < this.playlist.length - 1) {
            this.currentIndex++;
            this.loadEpisode(this.playlist[this.currentIndex]);
            this.audio.play();
        }
    }
    
    skip(seconds) {
        this.audio.currentTime = Math.max(0, Math.min(this.audio.duration, this.audio.currentTime + seconds));
    }
    
    seek(percentage) {
        const time = (percentage / 100) * this.audio.duration;
        this.audio.currentTime = time;
    }
    
    setVolume(value) {
        this.audio.volume = value / 100;
        this.updateVolumeIcon(value);
        localStorage.setItem('podcastPlayerVolume', value);
    }
    
    toggleMute() {
        if (this.audio.volume > 0) {
            this.audio.volume = 0;
            this.volumeSlider.value = 0;
            this.updateVolumeIcon(0);
        } else {
            const savedVolume = localStorage.getItem('podcastPlayerVolume') || 100;
            this.audio.volume = savedVolume / 100;
            this.volumeSlider.value = savedVolume;
            this.updateVolumeIcon(savedVolume);
        }
    }
    
    updateVolumeIcon(value) {
        if (value == 0) {
            this.volumeIcon.className = 'bi bi-volume-mute-fill';
        } else if (value < 50) {
            this.volumeIcon.className = 'bi bi-volume-down-fill';
        } else {
            this.volumeIcon.className = 'bi bi-volume-up-fill';
        }
    }
    
    setPlaybackSpeed(speed) {
        this.audio.playbackRate = speed;
        this.speedDisplay.textContent = speed + 'x';
        document.querySelectorAll('.speed-option').forEach(opt => {
            opt.classList.toggle('active', parseFloat(opt.dataset.speed) === speed);
        });
        localStorage.setItem('podcastPlayerSpeed', speed);
    }
    
    updateProgress() {
        if (this.audio.duration) {
            const percentage = (this.audio.currentTime / this.audio.duration) * 100;
            this.progressBar.value = percentage;
            this.progressFill.style.width = percentage + '%';
            this.currentTime.textContent = this.formatTime(this.audio.currentTime);
        }
    }
    
    onMetadataLoaded() {
        this.totalDuration.textContent = this.formatTime(this.audio.duration);
    }
    
    onEnded() {
        if (this.playlist.length > 0 && this.currentIndex < this.playlist.length - 1) {
            this.playNext();
        }
    }
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const hours = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }
    
    handleKeyboard(e) {
        if (this.playerWrapper.style.display === 'none') return;
        
        // Ignore if user is typing in an input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.key.toLowerCase()) {
            case ' ':
                e.preventDefault();
                this.togglePlayPause();
                break;
            case 'arrowleft':
                e.preventDefault();
                this.skip(-10);
                break;
            case 'arrowright':
                e.preventDefault();
                this.skip(30);
                break;
            case 'arrowup':
                e.preventDefault();
                this.setVolume(Math.min(100, this.volumeSlider.value + 10));
                break;
            case 'arrowdown':
                e.preventDefault();
                this.setVolume(Math.max(0, this.volumeSlider.value - 10));
                break;
        }
    }
    
    closePlayer() {
        this.audio.pause();
        this.playerWrapper.style.display = 'none';
        localStorage.removeItem('podcastPlayerState');
    }
    
    saveState() {
        const state = {
            episode: this.currentEpisode,
            currentTime: this.audio.currentTime,
            playlist: this.playlist,
            currentIndex: this.currentIndex
        };
        localStorage.setItem('podcastPlayerState', JSON.stringify(state));
    }
    
    loadSavedState() {
        const savedState = localStorage.getItem('podcastPlayerState');
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                if (state.episode) {
                    this.loadEpisode(state.episode);
                    this.audio.currentTime = state.currentTime || 0;
                    if (state.playlist) {
                        this.playlist = state.playlist;
                        this.currentIndex = state.currentIndex || 0;
                    }
                }
            } catch (e) {
                console.error('Error loading saved player state:', e);
            }
        }
        
        // Load saved volume
        const savedVolume = localStorage.getItem('podcastPlayerVolume');
        if (savedVolume) {
            this.setVolume(savedVolume);
        }
        
        // Load saved speed
        const savedSpeed = localStorage.getItem('podcastPlayerSpeed');
        if (savedSpeed) {
            this.setPlaybackSpeed(parseFloat(savedSpeed));
        }
    }
}

// Initialize player
const podcastPlayer = new PodcastPlayer();

// Global function to play episode from anywhere
function playPodcastEpisode(episodeData) {
    podcastPlayer.loadEpisode(episodeData);
    podcastPlayer.audio.play();
}

// Global function to play playlist
function playPodcastPlaylist(episodes, startIndex = 0) {
    podcastPlayer.loadPlaylist(episodes, startIndex);
    podcastPlayer.audio.play();
}
</script>
