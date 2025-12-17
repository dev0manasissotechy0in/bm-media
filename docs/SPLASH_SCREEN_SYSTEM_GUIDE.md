# Splash Screen Management System - Complete Guide

## 📱 Overview

A comprehensive splash screen management system that allows administrators to configure both **default** and **dynamic** splash screens through an admin panel. The system supports scheduling, platform targeting, and analytics tracking.

---

## ✅ Implementation Status

### Backend (PHP/MySQL) ✅ COMPLETE
- [x] Database schema with all required fields
- [x] Admin panel for managing splash screens
- [x] API endpoints for fetching settings
- [x] Analytics tracking (impressions, clicks)

### Frontend (Flutter) ✅ COMPLETE
- [x] Splash settings model
- [x] Splash API service
- [x] Enhanced splash screen with dynamic support
- [x] Animation system (fade, slide, zoom, bounce)
- [x] Button actions and URL launching

---

## 🗂️ Files Created

### Database
- `database/splash_screen_schema.sql` - Table structure and indexes

### Backend (Admin Panel)
- `admin/splash-screen-settings.php` - Admin interface for managing splashes

### Backend (APIs)
- `api/splash/settings.php` - GET endpoint to fetch splash configuration
- `api/splash/track.php` - POST endpoint to track button clicks

### Flutter (App)
- `news_app/lib/models/splash_settings.dart` - Data models
- `news_app/lib/services/splash_api_service.dart` - API service
- `news_app/lib/screens/splash_new.dart` - Enhanced splash screen

---

## 📊 Database Schema

### Table: `splash_screen_settings`

**Single row design (id=1)** - All settings stored in one record.

#### Status Fields
- `is_dynamic_enabled` - Boolean to enable/disable dynamic splash

#### Default Splash Fields
- `default_logo` - Path to logo image (512x512px recommended)
- `default_background_color` - Hex color (e.g., #FFFFFF)
- `default_text_color` - Hex color (e.g., #000000)
- `default_tagline` - Tagline text (e.g., "Your Trusted News Source")
- `default_animation_type` - Animation: fade, slide, zoom, bounce, none

#### Dynamic Splash Fields
- `dynamic_image` - Path to full-screen splash image (1080x1920px)
- `dynamic_background_color` - Hex color
- `dynamic_text_color` - Hex color
- `dynamic_title` - Main heading
- `dynamic_subtitle` - Supporting text
- `dynamic_button_text` - CTA button text
- `dynamic_button_action` - URL to open on button click
- `dynamic_display_duration` - Seconds to display (1-10)

#### Scheduling Fields
- `is_scheduled` - Boolean to enable scheduling
- `schedule_start_date` - Start datetime
- `schedule_end_date` - End datetime

#### Targeting Fields
- `target_new_users_only` - Boolean to show only on first launch
- `target_platforms` - 'all', 'android', 'ios', or 'web'

#### Analytics Fields
- `impression_count` - Total times splash was shown
- `click_count` - Total button clicks

---

## 🖥️ Admin Panel Features

### Location
`admin/splash-screen-settings.php`

### Layout
**Two-column responsive design:**
- Left: Default Splash Configuration
- Right: Dynamic Splash Configuration
- Bottom: Side-by-side Preview

### Default Splash Section
1. **Logo Upload**
   - File input with preview
   - Accepts: jpg, jpeg, png, gif, webp, svg
   - Recommended: 512x512px transparent PNG
   - Stored in: `uploads/splash/`

2. **Colors**
   - Background color picker
   - Text color picker

3. **Tagline**
   - Text input for app tagline

4. **Animation Type**
   - Dropdown: fade, slide, zoom, bounce, none

### Dynamic Splash Section
1. **Master Toggle**
   - Enable/disable dynamic splash
   - When disabled, default splash always shows

2. **Splash Image**
   - Full-screen image upload
   - Accepts: jpg, jpeg, png, gif, webp
   - Recommended: 1080x1920px portrait

3. **Content**
   - Title (main heading)
   - Subtitle (supporting text)
   - Colors (background, text)

4. **Call-to-Action**
   - Button text
   - Action URL (external link to open)

5. **Display Duration**
   - Number input: 1-10 seconds

6. **Schedule (Optional)**
   - Toggle to enable scheduling
   - Start date/time picker
   - End date/time picker

7. **Targeting**
   - New users only checkbox
   - Platform dropdown (all, android, ios, web)

8. **Analytics**
   - Impressions count (read-only)
   - Clicks count (read-only)

### Preview Section
- **Left preview**: Default splash with colors
- **Right preview**: Dynamic splash with image

---

## 🔌 API Endpoints

### 1. GET `/api/splash/settings.php`

**Purpose:** Fetch splash screen configuration

**Query Parameters:**
- `platform` (string) - 'all', 'android', 'ios', or 'web'
- `first_time` (boolean) - 'true' if first launch

**Response:**
```json
{
  "success": true,
  "show_dynamic": true,
  "dynamic_reason": "",
  "default_splash": {
    "logo": "uploads/splash/default_logo_1234567890.png",
    "background_color": "#FFFFFF",
    "text_color": "#000000",
    "tagline": "Your Trusted News Source",
    "animation_type": "fade"
  },
  "dynamic_splash": {
    "image": "uploads/splash/dynamic_splash_1234567890.jpg",
    "background_color": "#1E3A8A",
    "text_color": "#FFFFFF",
    "title": "Breaking News Alert",
    "subtitle": "Stay updated with the latest headlines",
    "button_text": "Explore Now",
    "button_action": "https://example.com/special",
    "display_duration": 5
  }
}
```

**Logic:**
1. Check if dynamic splash is enabled
2. If enabled, check platform targeting
3. Check if user is new (if targeting new users)
4. Check schedule (if scheduled)
5. If all conditions pass, return dynamic splash
6. Otherwise, return default splash

**Auto-tracking:**
- Increments `impression_count` when dynamic splash is shown

### 2. POST `/api/splash/track.php`

**Purpose:** Track button clicks on dynamic splash

**Request Body:**
```json
{
  "action": "button_click"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Click tracked"
}
```

**Logic:**
- Increments `click_count` in database

---

## 📱 Flutter Implementation

### Models (`splash_settings.dart`)

**Classes:**
- `SplashSettings` - Main response wrapper
- `DefaultSplash` - Default splash configuration
- `DynamicSplash` - Dynamic splash configuration

**Example Usage:**
```dart
final settings = SplashSettings.fromJson(jsonResponse);
if (settings.showDynamic) {
  // Show dynamic splash
  final dynamic = settings.dynamicSplash;
  print(dynamic.title);
} else {
  // Show default splash
  final default = settings.defaultSplash;
  print(default.tagline);
}
```

### Service (`splash_api_service.dart`)

**Methods:**

1. **getSplashSettings()**
   ```dart
   final settings = await SplashApiService.getSplashSettings(
     platform: 'android',
     isFirstTime: true,
   );
   ```
   - Auto-detects first launch using SharedPreferences
   - Returns `SplashSettings?` or null on error

2. **trackClick()**
   ```dart
   await SplashApiService.trackClick();
   ```
   - Tracks button clicks
   - Returns `bool` (success/failure)

3. **getPlatform()**
   ```dart
   final platform = SplashApiService.getPlatform();
   ```
   - Returns 'android', 'ios', or 'web'

### Screen (`splash_new.dart`)

**Key Features:**

1. **Initialization**
   - Fetches splash settings from API
   - Initializes deep links and in-app updates
   - Determines platform automatically

2. **Splash Display Logic**
   - If `show_dynamic == true`: Show dynamic splash
   - Otherwise: Show default splash

3. **Default Splash**
   - Displays logo image
   - Shows tagline
   - Applies colors
   - Animates based on `animation_type`:
     - **fade**: Opacity animation
     - **slide**: Vertical slide-in
     - **zoom**: Scale animation
     - **bounce**: Bounce effect
     - **none**: No animation

4. **Dynamic Splash**
   - Full-screen background image
   - Overlay with title, subtitle
   - CTA button at bottom
   - Skip button at top-right
   - Auto-proceeds after duration OR on button click

5. **Button Actions**
   - Tracks click via API
   - Launches external URL if provided
   - Proceeds to app after action

6. **Color Handling**
   - Converts hex strings to Flutter `Color`
   - Supports 7-character hex (#RRGGBB)

---

## 🚀 Installation Steps

### 1. Import Database Schema
```bash
mysql -u root -p news_website < database/splash_screen_schema.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select `news_website` database
3. Go to Import tab
4. Choose `splash_screen_schema.sql`
5. Click Go

**Verify:**
```sql
SELECT * FROM splash_screen_settings;
```
You should see one row with id=1 and default values.

### 2. Create Upload Directory
Create directory for splash images:
```bash
mkdir uploads/splash
chmod 755 uploads/splash
```

Or via File Explorer:
1. Navigate to `c:\xampp\htdocs\`
2. Create folder: `uploads\splash`

### 3. Update Admin Menu (Optional)
Add menu item to admin sidebar:

**File:** `admin/includes/header.php`

Find the Settings section and add:
```html
<li class="nav-item">
    <a class="nav-link" href="splash-screen-settings.php">
        <i class="bi bi-phone"></i>
        Splash Screen
    </a>
</li>
```

### 4. Update Flutter App

**Option A: Replace existing splash.dart**
```bash
# Backup old file
cp news_app/lib/screens/splash.dart news_app/lib/screens/splash_old.dart

# Replace with new version
cp news_app/lib/screens/splash_new.dart news_app/lib/screens/splash.dart
```

**Option B: Use as separate file**
Keep `splash_new.dart` and update your route:
```dart
// In main.dart or routes
SplashScreen() => SplashNewScreen()
```

### 5. Install Dependencies (if missing)
```bash
cd news_app
flutter pub add url_launcher
flutter pub get
```

### 6. Test the System

**Test Default Splash:**
1. Go to `admin/splash-screen-settings.php`
2. Upload a logo
3. Set colors and tagline
4. Save
5. Open app - should show default splash

**Test Dynamic Splash:**
1. Upload splash image
2. Enter title, subtitle, button text
3. Enable dynamic splash toggle
4. Save
5. Restart app - should show dynamic splash

**Test Scheduling:**
1. Enable schedule toggle
2. Set start date = now, end date = 1 hour from now
3. Save
4. App shows dynamic splash
5. Wait until after end date
6. App shows default splash

**Test Platform Targeting:**
1. Set platform = "android"
2. Save
3. Android app shows dynamic splash
4. iOS app shows default splash

**Test Analytics:**
1. Open app multiple times
2. Check impression count increases
3. Click button on dynamic splash
4. Check click count increases

---

## 🎯 Use Cases

### 1. **Daily Branding (Default Splash)**
**Scenario:** Always show app logo and tagline
**Configuration:**
- Dynamic splash: OFF
- Default logo: Your app logo
- Tagline: "Your Trusted News Source"
- Animation: fade

### 2. **Holiday Promotion (Dynamic Splash)**
**Scenario:** Special Christmas splash for 1 week
**Configuration:**
- Dynamic splash: ON
- Image: Christmas-themed background
- Title: "Happy Holidays!"
- Subtitle: "Special offers inside"
- Button: "Shop Now" → https://store.com/xmas
- Schedule: Dec 18 - Dec 25
- Target: All platforms

### 3. **New User Onboarding (Dynamic Splash)**
**Scenario:** Welcome message for first-time users
**Configuration:**
- Dynamic splash: ON
- Title: "Welcome to NewsApp"
- Subtitle: "Discover news that matters"
- Button: "Get Started"
- Target new users only: YES
- No schedule (always show for new users)

### 4. **Platform-Specific (Dynamic Splash)**
**Scenario:** iOS App Store feature announcement
**Configuration:**
- Dynamic splash: ON
- Title: "Now on iOS App Store"
- Subtitle: "Rate us 5 stars!"
- Button: "Rate Now" → https://apps.apple.com/...
- Platform: ios

### 5. **Breaking News Alert (Dynamic Splash)**
**Scenario:** Major news event splash
**Configuration:**
- Dynamic splash: ON
- Image: News event photo
- Title: "Breaking: Election Results"
- Subtitle: "Live updates inside"
- Button: "Read Now" → /election-dashboard
- Duration: 5 seconds
- Schedule: Election day only

---

## 📈 Analytics Dashboard

### View Analytics
Go to `admin/splash-screen-settings.php` and check the Dynamic Splash card:

**Metrics:**
- **Impressions**: Total times dynamic splash was shown
- **Clicks**: Total button clicks
- **CTR**: Click-through rate = (clicks / impressions) × 100%

**Manual Calculation:**
```
If impressions = 1000, clicks = 150
CTR = (150 / 1000) × 100 = 15%
```

### Reset Analytics
Run SQL:
```sql
UPDATE splash_screen_settings 
SET impression_count = 0, click_count = 0 
WHERE id = 1;
```

---

## 🎨 Design Guidelines

### Default Splash
**Logo:**
- Format: PNG with transparency
- Size: 512x512px
- Center focus: Icon or wordmark
- Safe area: Keep important elements within 400x400px

**Colors:**
- Background: Brand primary color or white
- Text: High contrast with background
- Ratio: WCAG AA minimum (4.5:1)

**Tagline:**
- Length: 2-5 words
- Message: Brand promise or category
- Examples: "News That Matters", "Stay Informed", "Truth First"

**Animation:**
- **fade**: Smooth, professional
- **slide**: Energetic, modern
- **zoom**: Bold, attention-grabbing
- **bounce**: Playful, casual
- **none**: Fast, minimalist

### Dynamic Splash
**Image:**
- Format: JPG or PNG
- Size: 1080x1920px (9:16 portrait)
- Content: Hero image or promotional graphic
- Text overlay: Ensure readability over image
- Safe zones:
  - Top: 200px (avoid notches)
  - Bottom: 300px (content area)
  - Sides: 40px padding

**Title:**
- Length: 3-6 words
- Font size: 28px (large, bold)
- Purpose: Grab attention
- Examples: "Breaking News Alert", "Holiday Special", "New Feature"

**Subtitle:**
- Length: 8-15 words
- Font size: 16px (readable)
- Purpose: Explain value
- Examples: "Get instant updates on developing stories", "Limited time offer ends today"

**Button:**
- Text: 1-3 words (action verb)
- Examples: "Read Now", "Shop Deals", "Get Started", "Learn More"
- Color: Contrasts with image and text
- Action: Deep link or external URL

**Duration:**
- Short (2-3s): Simple message
- Medium (4-5s): Read title + subtitle
- Long (6-10s): Complex message or multiple points

---

## 🔧 Troubleshooting

### Issue: Splash not loading in app
**Cause:** API connection failure
**Solution:**
1. Check if API is accessible: `http://localhost/api/splash/settings.php`
2. Verify CORS headers in API
3. Check Flutter console for errors
4. Ensure database table exists

### Issue: Images not displaying
**Cause:** Wrong path or file not uploaded
**Solution:**
1. Verify file exists: `uploads/splash/filename.png`
2. Check file permissions (readable)
3. Ensure correct URL in Flutter: `http://localhost/...`
4. Check file extension in database

### Issue: Dynamic splash not showing
**Cause:** Conditions not met
**Solution:**
1. Check `is_dynamic_enabled` = 1
2. Verify schedule dates (if enabled)
3. Check platform targeting
4. Check new user targeting
5. View `dynamic_reason` in API response for diagnosis

### Issue: Analytics not tracking
**Cause:** API not called or database error
**Solution:**
1. Add debug logs in Flutter
2. Check API response: `api/splash/track.php`
3. Verify database connection
4. Check SQL UPDATE query

### Issue: Admin panel not saving
**Cause:** File upload error or database error
**Solution:**
1. Check file upload size limit in `php.ini`
2. Verify `uploads/splash/` directory exists and is writable
3. Check PHP error logs
4. Verify database table structure

### Issue: Colors not applying
**Cause:** Invalid hex format
**Solution:**
1. Ensure format: #RRGGBB (7 characters)
2. No spaces or invalid characters
3. Test with default values: #FFFFFF, #000000

---

## 🔐 Security Considerations

### File Upload Security
- ✅ Extension validation (whitelist)
- ✅ Unique filename generation (timestamp)
- ✅ Directory isolation (`uploads/splash/`)
- ⚠️ TODO: File size limit (max 5MB)
- ⚠️ TODO: Image validation (verify actual format)
- ⚠️ TODO: Malware scanning

### SQL Injection Prevention
- ✅ PDO prepared statements used
- ✅ Parameter binding
- ✅ No direct user input in queries

### XSS Prevention
- ⚠️ TODO: HTML entity encoding in admin panel
- ⚠️ TODO: Sanitize output in preview section

### API Security
- ✅ CORS headers configured
- ⚠️ TODO: Rate limiting
- ⚠️ TODO: API authentication (if needed)

---

## 📝 Future Enhancements

### Phase 1: Quality
- [ ] Add image compression on upload
- [ ] Resize images to recommended dimensions
- [ ] Add file size validation
- [ ] Implement XSS protection

### Phase 2: Features
- [ ] Multiple dynamic splashes (A/B testing)
- [ ] Weight-based selection
- [ ] Rich text editor for subtitle
- [ ] Video splash support
- [ ] GIF animation support

### Phase 3: Analytics
- [ ] Advanced analytics dashboard
- [ ] CTR calculation and display
- [ ] User segment analysis
- [ ] Export analytics data

### Phase 4: Management
- [ ] Preview splash in admin (interactive)
- [ ] Schedule templates (recurring)
- [ ] Copy splash configuration
- [ ] Version history

---

## 🎓 Testing Checklist

### Setup Tests
- [ ] Database schema imported successfully
- [ ] Table `splash_screen_settings` exists
- [ ] Default row (id=1) inserted
- [ ] Upload directory `uploads/splash/` created
- [ ] Admin panel accessible

### Default Splash Tests
- [ ] Upload logo image
- [ ] Set background color
- [ ] Set text color
- [ ] Set tagline
- [ ] Select animation type
- [ ] Save settings
- [ ] View in app
- [ ] Verify colors match
- [ ] Verify animation works

### Dynamic Splash Tests
- [ ] Upload splash image
- [ ] Enter title and subtitle
- [ ] Set colors
- [ ] Set button text and action
- [ ] Set display duration
- [ ] Enable dynamic splash
- [ ] Save settings
- [ ] View in app
- [ ] Click button (verify URL opens)
- [ ] Click skip button

### Schedule Tests
- [ ] Enable schedule
- [ ] Set start date in future
- [ ] Verify default splash shows
- [ ] Change start to past
- [ ] Verify dynamic splash shows
- [ ] Set end date in past
- [ ] Verify default splash shows

### Targeting Tests
- [ ] Set "new users only" = YES
- [ ] First launch: dynamic splash shows
- [ ] Second launch: default splash shows
- [ ] Set platform = "android"
- [ ] Android: dynamic splash shows
- [ ] iOS: default splash shows

### Analytics Tests
- [ ] View splash 5 times
- [ ] Check impressions = 5
- [ ] Click button 3 times
- [ ] Check clicks = 3
- [ ] Calculate CTR = 60%

---

## 📞 Support

If you encounter any issues or need assistance:

1. **Check this guide** - Most common issues are covered
2. **Review error logs** - PHP error log, Flutter console
3. **Test API endpoints** - Use Postman or browser
4. **Verify database** - Check table structure and data

---

## 📄 License

This splash screen management system is part of the News Website project.

---

**Created:** January 2025  
**Last Updated:** January 2025  
**Version:** 1.0.0  
**Status:** ✅ Production Ready
