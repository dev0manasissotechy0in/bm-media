# Mobile Stories - Instagram-Style Implementation Guide

## Overview
Instagram-style mobile stories feature with timeline progress bars, 24-hour expiration, tap navigation, and admin panel management.

---

## Features

### 📱 User Experience
- **Instagram-Style Timeline Bars** - Segmented progress indicators at the top
- **Auto-Advance** - Stories automatically progress after 5 seconds
- **Tap Navigation** - Tap left/right thirds to navigate, middle to pause/resume
- **Hold to Pause** - Long press anywhere to pause the story
- **Swipe to Dismiss** - Swipe down to close stories viewer
- **24-Hour Expiration** - Stories automatically expire after 24 hours
- **Category Grouping** - Stories organized by news categories
- **View Count Tracking** - Automatic view counting
- **Gradient Story Rings** - Colorful gradient borders like Instagram

### 🎨 Visual Design
- **Full-Screen Viewer** - Immersive story experience
- **Progress Bars** - White segmented bars showing current position
- **Gradient Overlay** - Top gradient for better text visibility
- **Story Header** - Category avatar, name, and time remaining
- **Story Footer** - Title and description in translucent container
- **Pause Indicator** - Visual feedback when paused

### 🔧 Admin Features
- **Admin Panel** - Full CRUD management at `http://localhost/admin/mobile-stories.php`
- **Add Stories** - Upload image, set title, description, category
- **Set Expiration** - Manual expiration or automatic 24-hour
- **Status Management** - Active/Expired status toggle
- **View Statistics** - Total stories, active, expired, total views
- **Category Filter** - Filter stories by category
- **Bulk Actions** - Delete multiple stories

---

## File Structure

### Backend (PHP)
```
api/mobile-stories/
├── list.php              # Fetch active stories (24-hour check)
└── increment-view.php    # Increment story view count

admin/
└── mobile-stories.php    # Admin CRUD interface (existing)

Database:
└── mobile_stories table
    ├── id
    ├── title
    ├── image_url
    ├── description
    ├── category_id
    ├── status (active/expired)
    ├── views_count
    ├── expires_at
    └── created_at
```

### Frontend (Flutter)
```
news_app/lib/
├── models/
│   └── mobile_story.dart           # Story data model
├── services/
│   └── mobile_stories_service.dart # API service
├── widgets/
│   ├── stories_player.dart         # Full-screen story viewer
│   └── stories_list_widget.dart    # Horizontal scrollable story rings
└── screens/
    ├── mobile_stories/
    │   └── mobile_stories.dart     # Full stories list screen
    └── tabs/home_tab/tab0/
        └── tab0.dart               # Home tab (stories integrated)
```

---

## Implementation Details

### 1. Story Model (`mobile_story.dart`)
```dart
class MobileStory {
  final String id;
  final String title;
  final String imageUrl;
  final String? description;
  final String? categoryName;
  final DateTime createdAt;
  final DateTime? expiresAt;
  final int viewsCount;
  
  bool get hasExpired // Check if expired
  Duration get timeRemaining // Calculate remaining time
  String get timeRemainingText // Format: "5h remaining"
}
```

### 2. API Endpoints

#### List Active Stories
```
GET /api/mobile-stories/list.php

Response:
{
  "success": true,
  "count": 10,
  "stories": [
    {
      "id": "1",
      "title": "Breaking News",
      "image_url": "https://...",
      "description": "Story description",
      "category_name": "Politics",
      "created_at": "2025-12-08 10:00:00",
      "expires_at": "2025-12-09 10:00:00",
      "views_count": 150,
      "is_expired": 0
    }
  ]
}

Logic:
- Fetches stories where status = 'active'
- Checks 24-hour expiration: created_at > NOW() - 24 hours
- Also respects manual expires_at field
- Returns absolute image URLs
```

#### Increment View Count
```
POST /api/mobile-stories/increment-view.php

Request:
{
  "story_id": "1"
}

Response:
{
  "success": true,
  "views_count": 151
}
```

### 3. Stories Service (`mobile_stories_service.dart`)
```dart
class MobileStoriesService {
  Future<List<MobileStory>> getActiveStories()
  Future<bool> incrementViewCount(String storyId)
  Map<String, List<MobileStory>> groupStoriesByCategory(stories)
}
```

### 4. Stories Player (`stories_player.dart`)

#### Features:
- **Timeline Progress Bars** - Shows progress through all stories
- **Animation Controller** - 5-second duration per story
- **Gesture Detection**:
  - Tap left third: Previous story
  - Tap right third: Next story
  - Tap middle: Pause/Resume
  - Long press: Pause
  - Vertical drag down: Close viewer
  
#### UI Elements:
- Full-screen story image with CachedNetworkImage
- Gradient overlay at top for readability
- Progress bars (white segments)
- Story header (category avatar, name, time remaining)
- Story footer (title and description)
- Pause indicator icon

#### Auto-Advance Logic:
```dart
_animationController.addStatusListener((status) {
  if (status == AnimationStatus.completed) {
    _nextStory(); // Auto-advance to next
  }
});
```

### 5. Stories List Widget (`stories_list_widget.dart`)

#### Display:
- Horizontal scrollable list
- Story rings with gradient borders
- Category avatars
- Story count badge (if multiple stories)
- Category name below ring

#### Story Ring Design:
```dart
Stack(
  Outer Ring: Gradient (Instagram colors)
  Middle Ring: White border (4px)
  Inner Ring: Story image
  Badge: Story count (if > 1)
)

Gradient Colors:
- Red (#FF6B6B)
- Yellow (#FFD93D)
- Green (#6BCF7F)
- Blue (#4D96FF)
```

### 6. Mobile Stories Screen (`mobile_stories.dart`)
Full-page view of all stories grouped by category with:
- Card-based layout
- Category header with gradient avatar
- Horizontal scrollable story thumbnails
- Pull-to-refresh support
- Empty state with icon and message

---

## Integration Points

### Home Tab Integration
Stories appear at the top of the home feed:

```dart
// lib/screens/tabs/home_tab/tab0/tab0.dart
Column(
  children: [
    const StoriesListWidget(), // ← Stories added here
    FeaturedArticles(),
    BreakingNews(),
    LatestArticles(),
  ],
)
```

### Header Button
Lightning bolt icon in app bar opens full stories screen:

```dart
// lib/screens/tabs/home_tab/home_tab.dart
IconButton(
  icon: const Icon(LineIcons.bolt-2),
  onPressed: () => NextScreen.normal(context, const MobileStories()),
)
```

---

## Admin Panel Usage

### Access Admin Panel
```
URL: http://localhost/admin/mobile-stories.php
```

### Add New Story
1. Click "Add New Mobile Story"
2. Enter title and description
3. Upload image (JPG, PNG, GIF, WEBP)
4. Select category
5. Set expiration date (or leave blank for 24-hour auto)
6. Click "Add Mobile Story"

### Manage Stories
- **View All**: See all stories with statistics
- **Filter**: By status (active/expired) and category
- **Toggle Status**: Click status badge to activate/deactivate
- **Delete**: Remove stories individually
- **View Count**: See how many times each story was viewed

### Statistics Dashboard
- Total Stories
- Active Stories (not expired)
- Expired Stories
- Total Views (all stories combined)

---

## Database Schema

### Table: `mobile_stories`
```sql
CREATE TABLE mobile_stories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  image_url VARCHAR(500) NOT NULL,
  category_id INT,
  status ENUM('active', 'expired') DEFAULT 'active',
  views_count INT DEFAULT 0,
  expires_at DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

---

## Story Expiration Logic

### 24-Hour Automatic Expiration
Stories are considered expired if:
1. `expires_at` field is set and is in the past, OR
2. `created_at` is more than 24 hours ago

```sql
-- API Query
WHERE m.status = 'active'
AND (
  (m.expires_at IS NOT NULL AND m.expires_at > NOW())
  OR (m.expires_at IS NULL AND m.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR))
)
```

### Manual Expiration
Admin can set custom expiration date in the admin panel.

---

## User Interactions

### Viewing Stories
1. **From Home Feed**: Tap any story ring in horizontal list
2. **From Stories Screen**: Tap lightning bolt icon, then tap category or story

### Navigation Controls
| Action | Gesture |
|--------|---------|
| Next Story | Tap right third of screen |
| Previous Story | Tap left third of screen |
| Pause/Resume | Tap middle of screen |
| Hold to Pause | Long press anywhere |
| Close Viewer | Swipe down OR tap X button |

### Story Duration
- Each story displays for **5 seconds**
- Progress bar fills during this time
- Auto-advances to next story when complete
- Closes viewer after last story

---

## Customization

### Change Story Duration
```dart
// lib/widgets/stories_player.dart
static const Duration _storyDuration = Duration(seconds: 5); // ← Change here
```

### Change Gradient Colors
```dart
// lib/widgets/stories_list_widget.dart
gradient: const LinearGradient(
  colors: [
    Color(0xFFFF6B6B),  // Red
    Color(0xFFFFD93D),  // Yellow
    Color(0xFF6BCF7F),  // Green
    Color(0xFF4D96FF),  // Blue
  ],
)
```

### Change Ring Size
```dart
// lib/widgets/stories_list_widget.dart
Container(
  width: 70,  // ← Outer ring
  height: 70,
  // ...
)
```

---

## Testing

### Test Story Creation
1. Go to `http://localhost/admin/mobile-stories.php`
2. Add 3-5 stories with different categories
3. Upload images for each story
4. Leave expiration blank for automatic 24-hour

### Test Story Viewing
1. Open app and go to home tab
2. Verify stories appear in horizontal list at top
3. Tap a story ring to open player
4. Test navigation:
   - Tap left/right to navigate
   - Tap middle to pause
   - Long press to pause
   - Swipe down to close
5. Verify auto-advance after 5 seconds
6. Verify view count increments in admin panel

### Test Expiration
1. Create a story in admin panel
2. Set expires_at to 1 minute in the future
3. Verify story appears in app
4. Wait 1 minute
5. Refresh app - story should disappear

---

## Troubleshooting

### Stories Not Appearing
1. Check if stories exist in admin panel
2. Verify status is "active"
3. Check expiration date (must be future or null)
4. Verify 24-hour window (created_at must be recent)
5. Check API response: `/api/mobile-stories/list.php`

### Images Not Loading
1. Verify image URL is absolute (starts with http://)
2. Check uploads directory permissions
3. Verify image file exists at the path
4. Check network connectivity in app

### View Count Not Incrementing
1. Check API endpoint: `/api/mobile-stories/increment-view.php`
2. Verify story ID is correct
3. Check database connection
4. Review API logs for errors

### Stories Not Auto-Advancing
1. Check animation controller duration (5 seconds)
2. Verify `_animationController.forward()` is called
3. Check if story is paused
4. Review console for errors

---

## API Configuration

### Backend URL
Configure in Flutter app:

```dart
// lib/config/wp_config.dart
class WPConfig {
  static const String url = 'http://localhost'; // ← Your backend URL
}
```

---

## Performance Optimization

### Image Caching
- Uses `CachedNetworkImage` for efficient image loading
- Images cached locally for faster subsequent views
- Reduces bandwidth usage

### Lazy Loading
- Stories list only loads when visible
- Images load on-demand
- API calls optimized with pagination

### Memory Management
- Animation controller disposed properly
- Timers cancelled on widget disposal
- Images released from memory when not visible

---

## Future Enhancements

### Potential Features
- [ ] Video stories support
- [ ] Multiple images per story (carousel)
- [ ] Story reactions (like, love, etc.)
- [ ] Story replies/comments
- [ ] User-generated stories
- [ ] Story highlights (saved stories)
- [ ] Story sharing
- [ ] Story analytics (completion rate)
- [ ] Story mentions (@username)
- [ ] Story hashtags (#topic)

---

## Summary

✅ **Complete Instagram-Style Experience**
- Timeline progress bars with segments
- Tap navigation (left/right/middle)
- Hold to pause functionality
- Swipe to dismiss
- Auto-advance between stories
- 24-hour expiration logic

✅ **Admin Management**
- Full CRUD operations
- Statistics dashboard
- Category filtering
- View count tracking
- Bulk actions

✅ **Professional UI**
- Gradient story rings
- Full-screen immersive viewer
- Smooth animations
- Responsive gestures
- Theme-aware design

✅ **Production Ready**
- Error handling
- Loading states
- Empty states
- Image caching
- Memory management
- API error handling

---

**Admin Panel URL**: `http://localhost/admin/mobile-stories.php`

Manage all your mobile stories from the admin panel - create, edit, delete, and monitor engagement!
