# 🔌 LOCAL TESTING SETUP COMPLETE

## ✅ Configuration Applied

### Flutter App
- **Base URL**: `http://192.168.56.1` (your local IP)
- **API URL**: `http://192.168.56.1/api`

### Backend (config.php)
- **SITE_URL**: `http://192.168.56.1`
- All API responses will now return localhost URLs

## 🚀 How to Test

### 1. Ensure XAMPP is Running
```bash
# Make sure Apache and MySQL are started in XAMPP Control Panel
```

### 2. Restart Flutter App
```bash
cd c:\xampp\htdocs\news_app

# Clean build to clear cache
flutter clean

# Get dependencies
flutter pub get

# Run app (this will also clear cached images)
flutter run
```

### 3. Access Points

#### From Flutter App:
- All API calls will go to: `http://192.168.56.1/api/`
- Images will load from: `http://192.168.56.1/uploads/`

#### From Browser (for debugging):
- Homepage: `http://192.168.56.1/`
- Database Check: `http://192.168.56.1/verify-database-state.php`
- Test APIs:
  - `http://192.168.56.1/api/articles/banner.php?limit=5`
  - `http://192.168.56.1/api/articles/reels.php`
  - `http://192.168.56.1/api/articles/videos.php`
  - `http://192.168.56.1/api/articles/podcasts.php`
  - `http://192.168.56.1/api/articles/breaking.php`

## 🔍 Verify Setup

### 1. Check API Response
Open in browser: `http://192.168.56.1/api/articles/reels.php`

Expected response:
```json
{
  "success": true,
  "count": 4,
  "articles": [
    {
      "id": 125,
      "title": "...",
      "image_url": "http://192.168.56.1/uploads/articles/reel_1764843982_693161ce87994.mp4",
      "video_url": "http://192.168.56.1/uploads/articles/reel_1764843982_693161ce87994.mp4"
    }
  ]
}
```

### 2. Check Database State
Open: `http://192.168.56.1/verify-database-state.php`

Should show:
- ✅ 4 reel articles with correct video files
- ✅ Old thumbnail NOT in database
- ✅ All video files exist on server

## 📱 Testing Checklist

After `flutter run`:

1. **Home Tab**
   - [ ] Latest articles load
   - [ ] Images display correctly (no 404 errors)
   - [ ] Breaking news shows at top

2. **Explore Tab**
   - [ ] Banner articles display
   - [ ] Images load from `http://192.168.56.1/uploads/...`
   - [ ] No old thumbnail errors

3. **Reels Tab** ⭐
   - [ ] 4 reel videos appear
   - [ ] Vertical scrolling works
   - [ ] Videos play correctly
   - [ ] No "old thumbnail" 404 errors

4. **Videos Tab**
   - [ ] 2 video articles show
   - [ ] Videos play
   - [ ] **Comments button appears** ✅

5. **Podcasts Tab**
   - [ ] 1 podcast shows
   - [ ] Thumbnail displays
   - [ ] (Audio won't play until you upload audio file)

6. **Categories Tab**
   - [ ] Categories list loads
   - [ ] Navigation works

7. **Profile Tab**
   - [ ] Login works with MySQL backend
   - [ ] User data displays

## 🐛 Debug Tips

### If images still show 404:
```bash
# In Flutter app, check console for:
🖼️ Loading image: http://192.168.56.1/uploads/...
❌ Image load error: ...

# If you see the OLD thumbnail URL still appearing:
flutter clean
# Delete app from device/emulator
# Reinstall: flutter run
```

### Check Console Output:
```dart
// Images log their URLs automatically
debugPrint('🖼️ Loading image: $imageUrl');
debugPrint('❌ Image load error: $url');
```

### Test API Directly:
```bash
# Use curl or browser to test API responses
curl http://192.168.56.1/api/articles/reels.php

# Should return JSON with correct localhost URLs
```

## 🔄 Switch Back to Production

When ready to deploy:

### 1. Update Flutter App
Edit `news_app/lib/services/api_service.dart`:
```dart
static const String baseUrl = 'https://brackoddmedia.com';
// static const String baseUrl = 'http://192.168.56.1';
```

### 2. Update Backend
Edit `config/config.php`:
```php
define('SITE_URL', 'https://brackoddmedia.com');
// define('SITE_URL', 'http://192.168.56.1');
```

### 3. Rebuild App
```bash
flutter clean
flutter build apk --release  # For Android
# or
flutter build ios --release  # For iOS
```

## 📊 Expected Results

After this setup + `flutter run`:

✅ **Database**: All correct (already fixed)
✅ **Backend APIs**: Returning localhost URLs
✅ **App Configuration**: Pointing to localhost
✅ **Cache**: Cleared with `flutter clean`
✅ **Images**: Loading from local server
✅ **Reels**: 4 videos ready to scroll
✅ **Videos**: 2 videos with comments button
✅ **Podcasts**: 1 podcast ready (no audio yet)
✅ **Breaking News**: 2 articles loading
✅ **Authentication**: Using MySQL backend

## 🎯 Current IP Configuration

- **Your Machine IP**: `192.168.56.1`
- **Port**: `80` (default Apache)
- **Protocol**: `http` (for local testing)

Make sure your device/emulator can reach this IP address on the same network!

---

**Ready to test!** Run `flutter clean && flutter pub get && flutter run` 🚀
