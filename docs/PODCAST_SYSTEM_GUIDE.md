# 🎙️ Podcast System - Complete Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Database Setup](#database-setup)
5. [Admin Panel Usage](#admin-panel-usage)
6. [Website Player](#website-player)
7. [Flutter App Integration](#flutter-app-integration)
8. [API Documentation](#api-documentation)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## 🎯 Overview

The Podcast System is a complete solution for managing and playing podcast episodes across web and mobile platforms. It features:

- **Admin Panel** - Full podcast management with categories
- **Website Player** - Stunning player with cover background
- **Flutter App** - Native mobile experience
- **Progress Tracking** - Resume from where users left off
- **Guest Support** - Works for both logged-in and guest users

---

## ✨ Features

### 🎨 Design Features
- **Cover Background** - Blurred podcast cover as background
- **Bottom Controls** - Spotify-style player controls
- **Timeline Slider** - Precise seeking with visual feedback
- **Responsive** - Works on all screen sizes

### 🔧 Functional Features
- **Resume Playback** - Automatically saves progress every 5 seconds
- **Playback Speed** - 7 speed options (0.5x - 2.0x)
- **Volume Control** - Independent volume settings
- **Like/Unlike** - Favorite podcast episodes
- **Download** - Download episodes for offline listening
- **Share** - Share episodes with others
- **Statistics** - Track plays, downloads, and likes

### 👥 User Features
- **Guest Mode** - Works without login (uses cookies/localStorage)
- **User Mode** - Full features for logged-in users
- **History** - View recent listening activity
- **Cross-Device** - Continue on any device (for logged-in users)

---

## 📦 Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- Flutter SDK (for mobile app)

### Step 1: Import Database

```bash
# Navigate to your MySQL
mysql -u root -p

# Create database if needed
CREATE DATABASE news_website;
USE news_website;

# Import schema
SOURCE database/podcast_schema.sql;
```

Or using phpMyAdmin:
1. Open phpMyAdmin
2. Select `news_website` database
3. Go to Import tab
4. Choose `database/podcast_schema.sql`
5. Click "Go"

### Step 2: Create Upload Directories

```bash
# Windows
mkdir c:\xampp\htdocs\uploads\podcasts\covers
mkdir c:\xampp\htdocs\uploads\podcasts\audio

# Linux/Mac
mkdir -p uploads/podcasts/covers
mkdir -p uploads/podcasts/audio
chmod -R 755 uploads/podcasts
```

### Step 3: Configure PHP Upload Limits

Edit `php.ini`:

```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 600
memory_limit = 256M
```

Restart Apache:

```bash
# Windows (XAMPP)
# Use XAMPP Control Panel to restart Apache

# Linux
sudo systemctl restart apache2

# Mac (MAMP)
# Use MAMP interface to restart
```

### Step 4: Verify Installation

1. Access admin panel: `http://localhost/admin/podcasts.php`
2. You should see the podcast management page
3. Try uploading a test podcast

---

## 🗄️ Database Setup

### Tables Created

#### 1. `podcast_categories`
Stores podcast categories (News, Technology, Sports, etc.)

**Key Fields:**
- `id` - Primary key
- `name` - Category name
- `slug` - URL-friendly name
- `color` - Category color (hex)
- `display_order` - Sort order
- `is_active` - Active status

#### 2. `podcasts`
Main podcast episodes table

**Key Fields:**
- `id` - Primary key
- `title` - Episode title
- `slug` - URL-friendly title
- `description` - Full description
- `category_id` - Foreign key to categories
- `audio_file` - Path to audio file
- `cover_image` - Path to cover image
- `duration` - Duration in seconds
- `host` - Host name
- `episode_number` - Episode number
- `is_featured` - Featured status
- `is_active` - Active status
- `play_count`, `download_count`, `like_count` - Statistics

#### 3. `podcast_progress`
Tracks user playback progress

**Key Fields:**
- `id` - Primary key
- `user_id` - User ID (NULL for guests)
- `guest_token` - Token for guest users
- `podcast_id` - Foreign key to podcasts
- `current_time` - Current position in seconds
- `duration` - Total duration
- `progress_percentage` - Progress as percentage
- `playback_speed` - Playback speed (0.5 - 2.0)
- `volume` - Volume level (0 - 1)
- `is_completed` - Marked complete at 95%+

#### 4. `podcast_likes`
Stores user likes

#### 5. `podcast_downloads`
Tracks download statistics

---

## 👨‍💼 Admin Panel Usage

### Accessing Admin Panel

1. Navigate to: `http://localhost/admin/podcasts.php`
2. Login with admin credentials
3. You'll see the podcast dashboard

### Adding a New Podcast

1. Click **"Add New Podcast"** button
2. Fill in required fields:
   - **Title** - Episode title (required)
   - **Description** - Full description (required)
   - **Category** - Select category (required)
   - **Audio File** - Upload MP3/M4A (required)
   - **Cover Image** - Upload square image (required)
   - **Duration** - Enter duration in seconds (required)

3. Optional fields:
   - **Host Name** - Name of the host
   - **Guest** - Guest names (comma-separated)
   - **Episode Number** - Episode number
   - **Season Number** - Season number
   - **Tags** - Tags for discoverability
   - **Meta Description** - SEO description

4. Publishing options:
   - **Active** - Make visible to users
   - **Featured** - Show in featured section
   - **Publish Date** - Schedule publication

5. Click **"Publish Podcast"**

### Managing Podcasts

**Bulk Actions:**
1. Select podcasts using checkboxes
2. Choose action from dropdown:
   - Activate
   - Deactivate
   - Feature
   - Unfeature
   - Delete
3. Click "Apply"

**Individual Actions:**
- **View** (Eye icon) - Preview on website
- **Edit** (Pencil icon) - Edit podcast
- **Delete** (Trash icon) - Delete podcast

### Statistics Dashboard

The dashboard shows:
- **Total Podcasts** - Total number of episodes
- **Active** - Currently active episodes
- **Featured** - Featured episodes
- **Total Plays** - Sum of all plays

---

## 🌐 Website Player

### Accessing the Player

**URL Format:**
```
http://localhost/podcast-player.php?id={PODCAST_ID}
```

**Example:**
```
http://localhost/podcast-player.php?id=1
```

### Player Features

#### Visual Design
- **Blurred Background** - Podcast cover as blurred backdrop
- **Center Cover** - Large cover image (400x400px)
- **Gradient Overlay** - Smooth dark gradient
- **Responsive** - Adapts to all screen sizes

#### Controls

**Timeline:**
- Click anywhere to seek
- Hover to see thumb indicator
- Shows current time / total duration

**Playback Controls:**
- **Play/Pause** - Space bar or button
- **Skip Back** - Go to previous position
- **Rewind 15s** - Go back 15 seconds
- **Forward 15s** - Go forward 15 seconds
- **Skip Forward** - Go to next position

**Speed Control:**
- Click speed button to cycle through speeds
- Options: 0.5x, 0.75x, 1.0x, 1.25x, 1.5x, 1.75x, 2.0x
- Speed is saved with progress

**Volume Control:**
- Click volume icon to mute/unmute
- Drag slider to adjust (0-100%)
- Volume is saved with progress

#### Action Buttons

**Like:**
- Click heart to like/unlike
- Shows like count
- Works for guests (uses cookie)

**Share:**
- Copy link or use native share
- Share on social media

**Download:**
- Download episode for offline
- Tracks download count

#### Keyboard Shortcuts
- `Space` - Play/Pause
- `←` - Seek backward 5s
- `→` - Seek forward 5s
- `↑` - Volume up
- `↓` - Volume down

### Progress Saving

**Automatic Saving:**
- Saves every 5 seconds while playing
- Saves on page unload
- Saves on seek/speed/volume change

**What's Saved:**
- Current playback position
- Playback speed
- Volume level
- Last played timestamp
- Completion status (95%+)

**Guest Users:**
- Progress saved via guest token
- Token stored in cookie (1 year)
- Works without login

**Logged-in Users:**
- Progress saved to user account
- Available on all devices
- Syncs automatically

---

## 📱 Flutter App Integration

### Required Dependencies

Add to `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  audioplayers: ^5.0.0
  http: ^1.1.0
  shared_preferences: ^2.2.0
  cached_network_image: ^3.2.0
  share_plus: ^7.0.0
```

Install:
```bash
flutter pub get
```

### Files Created

1. **Models:**
   - `lib/models/podcast_model.dart` - Data models

2. **Services:**
   - `lib/services/podcast_api_service.dart` - API service

3. **Screens:**
   - `lib/screens/podcasts_screen.dart` - Podcast list
   - `lib/screens/podcast_player_screen.dart` - Player screen

### Integration Steps

#### 1. Update API Base URL

In `podcast_api_service.dart`, change:

```dart
static const String baseUrl = 'http://YOUR_IP/api/podcasts';
```

Replace `YOUR_IP` with:
- `localhost` - For emulator
- `10.0.2.2` - For Android emulator
- `192.168.x.x` - Your local IP for physical device

#### 2. Add to Navigation

In your main app file:

```dart
import 'screens/podcasts_screen.dart';

// In your drawer/navigation:
ListTile(
  leading: Icon(Icons.podcast),
  title: Text('Podcasts'),
  onTap: () {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PodcastsScreen(
          userId: currentUserId, // Pass null for guest
        ),
      ),
    );
  },
),
```

#### 3. Test the App

```bash
flutter run
```

### Flutter Features

**Podcasts Screen:**
- 3 tabs: Featured, All, History
- Category filtering
- Search functionality
- Resume from history

**Player Screen:**
- Full-screen immersive player
- Cover background with blur
- Bottom controls
- Timeline with seeking
- Speed control (0.5x - 2.0x)
- Volume control
- Like/Unlike
- Share functionality
- Auto-save progress

**Progress Tracking:**
- Saves every 5 seconds
- Saves on app pause/resume
- Saves on back navigation
- Syncs with website

---

## 🔌 API Documentation

### Base URL
```
http://localhost/api/podcasts/
```

### Endpoints

#### 1. List Podcasts
```http
GET /list.php
```

**Parameters:**
- `category_id` (int, optional) - Filter by category
- `featured` (bool, optional) - Only featured podcasts
- `limit` (int, default: 20) - Results per page
- `offset` (int, default: 0) - Pagination offset
- `search` (string, optional) - Search query

**Response:**
```json
{
  "success": true,
  "total": 50,
  "limit": 20,
  "offset": 0,
  "podcasts": [...]
}
```

#### 2. Podcast Detail
```http
GET /detail.php?id={id}
```

**Parameters:**
- `id` (int, required) - Podcast ID
- `user_id` (int, optional) - User ID
- `guest_token` (string, optional) - Guest token

**Response:**
```json
{
  "success": true,
  "podcast": {...},
  "progress": {...},
  "is_liked": false
}
```

#### 3. Save Progress
```http
POST /save-progress.php
```

**Body:**
```json
{
  "podcast_id": 1,
  "user_id": 123,
  "guest_token": null,
  "current_time": 450,
  "duration": 1800,
  "playback_speed": 1.5,
  "volume": 0.8
}
```

**Response:**
```json
{
  "success": true,
  "message": "Progress saved",
  "progress_percentage": 25.0,
  "is_completed": false
}
```

#### 4. Toggle Like
```http
POST /toggle-like.php
```

**Body:**
```json
{
  "podcast_id": 1,
  "user_id": 123,
  "guest_token": null
}
```

**Response:**
```json
{
  "success": true,
  "liked": true,
  "like_count": "245"
}
```

#### 5. Categories
```http
GET /categories.php
```

**Response:**
```json
{
  "success": true,
  "categories": [...]
}
```

#### 6. Listening History
```http
GET /history.php
```

**Parameters:**
- `user_id` (int, optional) - User ID
- `guest_token` (string, optional) - Guest token
- `limit` (int, default: 10) - Results limit

**Response:**
```json
{
  "success": true,
  "history": [...]
}
```

#### 7. Download
```http
GET /download.php?id={id}
```

Downloads the audio file and tracks statistics.

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Audio File Not Playing

**Symptoms:** Player loads but audio doesn't play

**Solutions:**
- Check file path is correct
- Verify file format (MP3, M4A supported)
- Check PHP upload succeeded
- Ensure audio file has correct permissions (755)
- Test audio file in browser directly

**Test:**
```
http://localhost/uploads/podcasts/audio/YOUR_FILE.mp3
```

#### 2. Progress Not Saving

**Symptoms:** Playback position resets on reload

**Solutions:**
- Check browser console for errors
- Verify guest token cookie is set
- Check database connection
- Ensure `podcast_progress` table exists
- Verify API endpoint is accessible

**Debug:**
```javascript
// In browser console
console.log(document.cookie); // Should show guest_token
```

#### 3. Cover Image Not Showing

**Symptoms:** Broken image or gray box

**Solutions:**
- Verify image was uploaded
- Check file path in database
- Ensure correct permissions (755)
- Test image URL directly in browser
- Check image format (JPG, PNG, WebP)

#### 4. Upload Fails

**Symptoms:** Error when uploading audio/image

**Solutions:**
- Check PHP upload limits
- Verify directory permissions
- Ensure enough disk space
- Check file size
- Review PHP error log

**PHP Settings:**
```ini
upload_max_filesize = 500M
post_max_size = 500M
max_execution_time = 600
```

#### 5. Flutter App Can't Connect

**Symptoms:** App shows loading forever

**Solutions:**
- Check API base URL
- Use correct IP for device type:
  - Emulator: `10.0.2.2`
  - Physical device: Your local IP
- Disable firewall temporarily
- Test API in browser on device
- Check CORS headers

**Test API:**
```bash
# Test from command line
curl http://YOUR_IP/api/podcasts/list.php
```

---

## 📖 Best Practices

### Audio Files

1. **Format:** Use MP3 for best compatibility
2. **Bitrate:** 128kbps for voice, 192kbps for music
3. **Sample Rate:** 44.1kHz standard
4. **Compression:** Use proper audio compression
5. **File Size:** Keep under 100MB when possible

### Cover Images

1. **Size:** 1400x1400px (square)
2. **Format:** JPG or WebP for smaller size
3. **File Size:** Keep under 500KB
4. **Quality:** 85% quality is sufficient
5. **Design:** High contrast, readable text

### Content Guidelines

1. **Title:** Clear, descriptive, under 100 characters
2. **Description:** Detailed, 200-500 words
3. **Tags:** 5-10 relevant tags
4. **Host Info:** Always include host name
5. **Episode Numbers:** Use consistent numbering

### Performance

1. **Caching:** Enable browser caching for static files
2. **CDN:** Use CDN for cover images
3. **Compression:** Enable Gzip compression
4. **Database:** Index frequently queried fields
5. **Pagination:** Always paginate large lists

### SEO

1. **Meta Descriptions:** Write unique descriptions
2. **Structured Data:** Add podcast schema markup
3. **Sitemap:** Include podcasts in sitemap
4. **URLs:** Use descriptive slugs
5. **Alt Text:** Add alt text to images

### Security

1. **File Validation:** Validate file types on upload
2. **SQL Injection:** Use prepared statements (already implemented)
3. **XSS:** Sanitize user input (already implemented)
4. **File Permissions:** Set correct permissions (755 folders, 644 files)
5. **Admin Access:** Protect admin panel with authentication

---

## 📊 Statistics & Analytics

### Track Engagement

The system automatically tracks:

1. **Play Count** - Number of times played
2. **Download Count** - Number of downloads
3. **Like Count** - Number of likes
4. **Completion Rate** - % of users who finish
5. **Average Listen Time** - Average duration listened

### View Statistics

**In Admin Panel:**
- Dashboard shows overall stats
- Each podcast shows individual stats
- Filter by date range (coming soon)

**In Database:**
```sql
-- Top podcasts by plays
SELECT title, play_count 
FROM podcasts 
ORDER BY play_count DESC 
LIMIT 10;

-- Completion rate
SELECT 
  p.title,
  COUNT(CASE WHEN pp.is_completed = 1 THEN 1 END) as completions,
  COUNT(*) as total_listens,
  (COUNT(CASE WHEN pp.is_completed = 1 THEN 1 END) * 100.0 / COUNT(*)) as completion_rate
FROM podcast_progress pp
JOIN podcasts p ON pp.podcast_id = p.id
GROUP BY p.id
ORDER BY completion_rate DESC;
```

---

## 🚀 Advanced Features

### Coming Soon

These features are planned for future releases:

1. **Playlists** - Create custom playlists
2. **Autoplay** - Play next episode automatically
3. **Sleep Timer** - Auto-stop after time
4. **Bookmarks** - Mark favorite moments
5. **Comments** - User comments on episodes
6. **Transcripts** - Full episode transcripts
7. **Chapters** - Episode chapters with timestamps
8. **RSS Feed** - Generate podcast RSS feed
9. **Analytics Dashboard** - Detailed analytics
10. **Email Notifications** - Notify on new episodes

### Customization

**Change Player Colors:**

In `podcast-player.php`, find:

```css
.btn-play-pause {
    background: #1db954; /* Spotify green */
}
```

Change to your brand color:

```css
.btn-play-pause {
    background: #FF0000; /* Your color */
}
```

**Change Speed Options:**

In `podcast-player.php`, find:

```javascript
const speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];
```

Modify as needed:

```javascript
const speeds = [1.0, 1.25, 1.5, 2.0]; // Simplified
```

---

## 📝 Quick Reference

### File Structure

```
├── database/
│   └── podcast_schema.sql
├── admin/
│   ├── podcasts.php
│   ├── podcast-add.php
│   └── podcast-edit.php
├── api/
│   └── podcasts/
│       ├── list.php
│       ├── detail.php
│       ├── save-progress.php
│       ├── toggle-like.php
│       ├── categories.php
│       ├── history.php
│       └── download.php
├── uploads/
│   └── podcasts/
│       ├── covers/
│       └── audio/
├── podcast-player.php
└── news_app/
    └── lib/
        ├── models/
        │   └── podcast_model.dart
        ├── services/
        │   └── podcast_api_service.dart
        └── screens/
            ├── podcasts_screen.dart
            └── podcast_player_screen.dart
```

### Key URLs

- Admin Panel: `http://localhost/admin/podcasts.php`
- Player: `http://localhost/podcast-player.php?id=1`
- API Base: `http://localhost/api/podcasts/`

### Database Tables

- `podcast_categories` - Categories
- `podcasts` - Episodes
- `podcast_progress` - User progress
- `podcast_likes` - Likes
- `podcast_downloads` - Downloads

---

## 💡 Tips & Tricks

1. **Faster Uploads:** Use FTP for large audio files
2. **Better Quality:** Record in quiet environment
3. **Engagement:** Add show notes in description
4. **Discovery:** Use relevant tags
5. **Promotion:** Share episode links on social media
6. **Consistency:** Publish on regular schedule
7. **Mobile First:** Test on mobile devices
8. **Backup:** Regularly backup audio files
9. **Analytics:** Monitor completion rates
10. **Feedback:** Encourage user reviews

---

## 🆘 Support

For issues or questions:

1. Check this documentation
2. Review troubleshooting section
3. Check browser console for errors
4. Review PHP error logs
5. Test APIs with browser/Postman

---

## 🎉 Congratulations!

You now have a fully functional podcast system with:
- ✅ Admin panel for management
- ✅ Beautiful website player
- ✅ Native mobile app support
- ✅ Progress tracking across devices
- ✅ Guest user support
- ✅ Complete API system

Start uploading your podcasts and enjoy! 🎙️
