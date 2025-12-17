# Podcast Player - Complete Implementation Guide

## Overview
A modern, feature-rich podcast player for the website with automatic duration detection and sticky player functionality.

## Features Implemented

### 1. Series Management through podcast-add.php
- **Single Podcast vs Series**: Radio button toggle to choose podcast type
- **Series Option**: When selected, hides audio file upload (managed through episodes)
- **Single Podcast**: Shows audio file upload with automatic duration detection

### 2. Automatic Duration Detection
Both `podcast-add.php` and `podcast-episode-add.php` now feature:
- **Audio File Preview**: Visual audio player appears when file is selected
- **Auto-detect Duration**: JavaScript automatically reads audio metadata
- **Format Display**: Shows duration in human-readable format (e.g., "1h 25m 30s")
- **Read-only Field**: Duration field is auto-populated and read-only
- **No Manual Entry**: Users no longer need to calculate or enter duration manually

**How it works:**
```javascript
// When audio file is selected:
1. Create object URL from file
2. Load audio metadata
3. Extract duration in seconds
4. Display formatted time (hours, minutes, seconds)
5. Auto-fill hidden duration field
```

### 3. Modern Podcast Player

#### Location
- `includes/podcast-player.php` - Standalone component included in pages

#### Design Features
- **Sticky Bottom Player**: Fixed at bottom of screen, always accessible
- **Gradient Background**: Purple gradient (667eea → 764ba2)
- **Responsive Layout**: Mobile-friendly with breakpoints
- **Smooth Animations**: Slide-up animation on load

#### Player Controls

**Playback Controls:**
- Play/Pause button (center, larger)
- Previous/Next episode navigation
- Rewind 10 seconds
- Forward 30 seconds

**Progress Bar:**
- Visual progress indicator
- Click to seek to specific time
- Current time / Total duration display
- Real-time progress updates

**Volume Control:**
- Volume slider (0-100%)
- Mute/Unmute button
- Dynamic volume icon (mute, low, high)
- Persistent volume setting (localStorage)

**Playback Speed:**
- Dropdown menu with options: 0.5x, 0.75x, 1x, 1.25x, 1.5x, 2x
- Active speed display
- Persistent speed setting (localStorage)

**Additional Features:**
- Album art display
- Episode title and author
- Close player button
- Keyboard shortcuts support

#### Keyboard Shortcuts
- **Space**: Play/Pause
- **Left Arrow**: Rewind 10 seconds
- **Right Arrow**: Forward 30 seconds
- **Up Arrow**: Increase volume
- **Down Arrow**: Decrease volume

#### State Persistence
The player automatically saves:
- Current episode information
- Playback position
- Volume level
- Playback speed
- Playlist data

All saved to `localStorage` and restored on page reload.

### 4. Integration

#### Pages Updated

**podcast.php:**
- Includes podcast player component
- Play single podcast button for standalone podcasts
- Episode list with individual play buttons for series
- "Play All" button to queue entire series
- Automatic playlist management

**podcasts.php:**
- Includes podcast player component
- Player available across all podcast listings

#### JavaScript Functions

**Global Functions:**
```javascript
// Play a single episode
playPodcastEpisode({
    title: "Episode Title",
    author: "Author Name",
    thumbnail: "/path/to/image.jpg",
    audioUrl: "/path/to/audio.mp3"
});

// Play a playlist starting at specific index
playPodcastPlaylist(episodesArray, startIndex);
```

**Example Usage:**
```javascript
// Play single episode
function playSinglePodcast() {
    playPodcastEpisode({
        title: podcastData.title,
        author: podcastData.author,
        thumbnail: podcastData.thumbnail,
        audioUrl: podcastData.audioUrl
    });
}

// Play from episode list
function playEpisodeFromList(index) {
    playPodcastPlaylist(episodesData, index);
}
```

### 5. Backend Updates

#### podcast-add.php
- Radio button toggle: Single Podcast / Series
- Audio file upload (single podcasts only)
- Auto-duration detection JavaScript
- Audio preview player
- Formatted duration display

#### podcast-episode-add.php
- Radio button toggle: File Upload / External URL
- Support for both file upload and URL input
- Auto-duration detection for uploaded files
- Audio preview player
- Conditional required fields based on source type

## File Structure

```
includes/
  └── podcast-player.php          # Standalone player component

admin/
  ├── podcast-add.php              # Create podcast (single or series)
  ├── podcast-episode-add.php      # Add episode to series
  └── podcast-series.php           # Manage series (previously created)

podcast.php                        # Single podcast/series display page
podcasts.php                       # All podcasts listing page
```

## Database Schema

### podcasts table
- `id`: Primary key
- `title`: Podcast title
- `slug`: URL-friendly slug
- `description`: Podcast description
- `author_name`: Author name
- `author_bio`: Author biography
- `category`: Category name
- `language`: Language code
- `is_series`: Boolean (0 = single, 1 = series)
- `audio_url`: Audio file URL (for single podcasts)
- `duration`: Duration in seconds (for single podcasts)
- `thumbnail`: Thumbnail image filename
- `cover_image`: Cover image filename
- `status`: published/draft/archived
- `views_count`: View counter
- `likes_count`: Like counter
- `created_at`, `updated_at`: Timestamps

### podcast_episodes table
- `id`: Primary key
- `podcast_id`: Foreign key to podcasts
- `title`: Episode title
- `slug`: URL-friendly slug
- `description`: Episode description
- `audio_url`: Audio file URL
- `duration`: Duration in seconds (auto-detected)
- `episode_number`: Episode number
- `season_number`: Season number
- `thumbnail`: Optional episode thumbnail
- `status`: published/draft
- `published_at`: Publication timestamp
- `views_count`: View counter
- `downloads_count`: Download counter
- `created_at`, `updated_at`: Timestamps

## Usage Instructions

### Creating a Single Podcast
1. Go to Admin → Media → Podcasts → All Podcasts
2. Click "Add New Podcast"
3. Select "Single Podcast" in Type dropdown
4. Fill in podcast details
5. Upload audio file - **duration is detected automatically**
6. Upload thumbnail and cover image
7. Click "Create Podcast"

### Creating a Series with Episodes
1. Go to Admin → Media → Podcasts → All Podcasts
2. Click "Add New Podcast"
3. Select "Series (Multiple Episodes)" in Type dropdown
4. Fill in series details (no audio file needed)
5. Upload thumbnail and cover image
6. Click "Create Podcast"
7. Go to "Manage Episodes" from the podcast card
8. Click "Add New Episode"
9. Choose "Upload File" or "External URL"
10. If uploading file, **duration is detected automatically**
11. Fill in episode details
12. Click "Create Episode"

### Using the Podcast Player

**For Users:**
1. Visit any podcast page
2. Click "Play Episode" or "Play" on any episode
3. Player appears at bottom of screen
4. Use controls to:
   - Play/Pause
   - Skip forward/backward
   - Adjust volume
   - Change playback speed
   - Navigate between episodes
5. Player persists across page navigation
6. Close player with X button when done

**For Developers:**
```javascript
// Include player on any page
<?php include 'includes/podcast-player.php'; ?>

// Play a single episode
playPodcastEpisode({
    title: "My Episode",
    author: "John Doe",
    thumbnail: "/uploads/podcasts/thumb.jpg",
    audioUrl: "/uploads/podcasts/audio.mp3"
});

// Play a playlist
const episodes = [
    { title: "Ep 1", author: "John", thumbnail: "...", audioUrl: "..." },
    { title: "Ep 2", author: "John", thumbnail: "...", audioUrl: "..." }
];
playPodcastPlaylist(episodes, 0); // Start from first episode
```

## Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Features
- Lazy loading of audio files
- Lightweight player (~15KB CSS/JS)
- Efficient state management
- No external dependencies (uses vanilla JS)
- Minimal DOM manipulation

## Future Enhancements (Optional)
- [ ] Download episode button
- [ ] Share episode functionality
- [ ] Add to favorites/bookmarks
- [ ] Sleep timer
- [ ] Chromecast/AirPlay support
- [ ] Equalizer settings
- [ ] Chapter markers
- [ ] Transcripts
- [ ] Comments section

## Troubleshooting

**Duration not detected:**
- Ensure audio file is valid format (MP3, WAV, M4A, OGG)
- Check file size (max 50MB)
- Try different browser
- Check browser console for errors

**Player not appearing:**
- Ensure `podcast-player.php` is included in page
- Check that `playPodcastEpisode()` is called correctly
- Verify audio URL is accessible
- Check browser console for JavaScript errors

**Audio not playing:**
- Verify audio file URL is correct and accessible
- Check audio file format compatibility
- Ensure file is not corrupted
- Test audio file directly in browser

## Credits
- Bootstrap 5.3.0 for UI framework
- Bootstrap Icons for iconography
- HTML5 Audio API for playback
- localStorage API for state persistence

---

**Last Updated:** December 7, 2025
**Version:** 1.0.0
**Status:** Production Ready ✅
