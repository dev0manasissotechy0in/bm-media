# Flutter App API Configuration Guide

## ⚠️ Current Issue: Tabs Not Showing Content

The Video Tab, Podcast Tab, and Case Thread Tab are not showing content because of **API URL configuration**.

## 🔧 Quick Fix

### Step 1: Determine Your Testing Environment

**Android Emulator:**
- Use `http://10.0.2.2` (special alias for host machine's localhost)

**iOS Simulator:**
- Use `http://localhost`

**Real Device (Wi-Fi):**
- Use your computer's local IP (e.g., `http://192.168.1.3`)
- Find your IP: `ipconfig` (Windows) or `ifconfig` (Mac/Linux)

**Web (Flutter Web):**
- Use `http://localhost`

### Step 2: Update API URL

**File:** `news_app/lib/services/api_service.dart`

**Lines 13-19:**

```dart
// UNCOMMENT THE LINE THAT MATCHES YOUR SETUP:

// For Android Emulator (MOST COMMON FOR DEVELOPMENT):
static const String baseUrl = 'http://10.0.2.2'; // ← UNCOMMENT THIS

// For Real Android/iOS Device on same Wi-Fi:
// static const String baseUrl = 'http://192.168.1.3'; // ← Replace with YOUR computer's IP

// For iOS Simulator or Flutter Web:
// static const String baseUrl = 'http://localhost';

// For Production:
// static const String baseUrl = 'https://brackoddmedia.com';
```

### Step 3: Restart the App

After changing the URL:
```bash
# Stop the app (Ctrl+C in terminal)
flutter clean
flutter pub get
flutter run
```

## 📊 Verify Data Exists

Before testing, confirm data exists in database:

```sql
-- Check video articles
SELECT COUNT(*) as count FROM articles WHERE content_type='video' AND status='published';

-- Check podcasts
SELECT COUNT(*) as count FROM podcasts;

-- Check case threads
SELECT COUNT(*) as count FROM case_threads;
```

**Current Status:**
- ✅ 2 video articles exist
- ✅ 1 podcast exists
- ✅ 1 case thread exists

## 🐛 Debug Steps

### 1. Check API Responses in Terminal

When you run the app, look for these debug messages:

**Video Tab:**
```
🎬 VideosTab initState called
🎬 VideosTab calling getData...
🎬 VideoArticlesData.getData() called - currentPage: 1
🎬 Fetching video articles from: http://10.0.2.2/api/articles/videos.php?limit=20&page=1
🎬 Video API Response Status: 200
🎬 Video API Success: true, Count: 2
🎬 Parsed 2 video articles
```

**Podcast Tab:**
```
🎙️ PodcastTab initState called
🎙️ PodcastTab calling getData...
🎙️ PodcastData.getData() called - currentPage: 1
🎙️ Fetching podcasts from: http://10.0.2.2/api/podcasts/all.php?limit=20&page=1
🎙️ Podcast API Response Status: 200
🎙️ Podcast API Success: true, Count: 1
🎙️ Parsed 1 podcasts
```

**Case Threads Tab:**
```
📋 Fetching cases from: http://10.0.2.2/api/cases/list.php?page=1&limit=20
📋 Case API Response Status: 200
📋 Case API Success: true, Cases: 1
```

### 2. Common Error Messages

**❌ Connection Refused:**
```
❌ Error fetching video articles: SocketException: Connection refused
```
**Solution:** XAMPP/Apache is not running OR wrong API URL

**❌ 404 Not Found:**
```
🎬 Video API Response Status: 404
```
**Solution:** API endpoint file doesn't exist or wrong path

**❌ No Data (Empty Array):**
```
🎬 Video API Success: true, Count: 0
🎬 Parsed 0 video articles
```
**Solution:** No data in database OR query filtering out all results

**❌ Network Error:**
```
❌ Error: Failed host lookup: '192.168.1.3'
```
**Solution:** Device not on same network OR IP address changed

### 3. Test APIs Directly in Browser

Before testing in app, verify APIs work:

**Video API:**
```
http://localhost/api/articles/videos.php?limit=20&page=1
```

**Podcast API:**
```
http://localhost/api/podcasts/all.php?limit=20&page=1
```

**Case Threads API:**
```
http://localhost/api/cases/list.php?page=1&limit=20
```

**Expected Response:**
```json
{
  "success": true,
  "count": 2,
  "articles": [...],
  "pagination": {...}
}
```

### 4. Check XAMPP Services

Ensure these are running:
- ✅ Apache (Port 80)
- ✅ MySQL (Port 3306)

## 📱 Testing on Different Platforms

### Android Emulator
1. Set `baseUrl = 'http://10.0.2.2'`
2. Ensure XAMPP is running
3. Restart app

### Real Android Device (Wi-Fi)
1. Find computer IP: `ipconfig` → Look for IPv4 Address (e.g., 192.168.1.3)
2. Set `baseUrl = 'http://YOUR_IP'` (e.g., `http://192.168.1.3`)
3. Ensure device on same Wi-Fi network
4. Disable Windows Firewall or add exception for port 80
5. Restart app

### iOS Simulator
1. Set `baseUrl = 'http://localhost'`
2. Ensure XAMPP is running
3. Restart app

### Flutter Web
1. Set `baseUrl = 'http://localhost'`
2. Run: `flutter run -d chrome`

## 🔒 Firewall Configuration (Real Device Testing)

If using real device, you may need to allow Apache through firewall:

**Windows Firewall:**
1. Open Windows Defender Firewall
2. Click "Allow an app through firewall"
3. Find "Apache HTTP Server" or add port 80
4. Check both Private and Public networks
5. Click OK

**Alternative:** Temporarily disable firewall for testing

## 📋 Troubleshooting Checklist

- [ ] XAMPP Apache is running (green indicator)
- [ ] XAMPP MySQL is running (green indicator)
- [ ] API URL matches testing environment
- [ ] API endpoints return data in browser
- [ ] Database has content (videos, podcasts, cases)
- [ ] Firewall allows connections (if using real device)
- [ ] Device and computer on same network (if using real device)
- [ ] App restarted after changing API URL
- [ ] Flutter clean and rebuild performed

## 🎯 Quick Test Commands

```bash
# Clean and rebuild app
flutter clean
flutter pub get
flutter run

# Test on specific device
flutter run -d emulator-5554  # Android Emulator
flutter run -d chrome         # Chrome Web
flutter run -d iPhone         # iOS Simulator

# View detailed logs
flutter run -v
```

## 📊 Verify Tab Content

Once configured correctly, you should see:

**Video Tab:**
- Shows 2 video articles
- Each with thumbnail and play icon
- Tap to play video

**Podcast Tab:**
- Shows 1 podcast
- With cover image and description
- Tap to view episodes

**Case Threads Tab:**
- Shows 1 case thread
- With title, category, status
- Tap to view full details

## 🔄 If Still Not Working

1. **Check Terminal Output:** Look for the debug messages (🎬, 🎙️, 📋)
2. **Copy Error Message:** Share the exact error from terminal
3. **Test API in Browser:** Verify APIs return data
4. **Check Network:** Ensure connectivity between app and server
5. **Verify Data:** Confirm database has content

## 💡 Development Tips

**Hot Reload Won't Work:** After changing API URL, you MUST restart the app completely (not hot reload).

**Use Android Emulator:** Easiest for development as `10.0.2.2` always works.

**Test on Real Device Later:** Once everything works on emulator, then test on real device with IP address.

**Keep XAMPP Running:** Don't forget to start XAMPP before testing app!

## 🎓 Understanding API URLs

**localhost vs 10.0.2.2 vs IP Address:**

- **`localhost`**: Works only for web/iOS simulator (same machine)
- **`10.0.2.2`**: Special Android emulator address for host machine
- **`192.168.1.x`**: Computer's actual IP, works for real devices on Wi-Fi
- **Domain (https://...)`**: Production server

---

**Quick Fix Summary:**
1. Open `news_app/lib/services/api_service.dart`
2. Change line 16 to: `static const String baseUrl = 'http://10.0.2.2';`
3. Save file
4. Stop app and run: `flutter run`
5. Check terminal for debug messages

If you see "Connection refused" - start XAMPP!
If you see "404" - check API files exist!
If you see "Empty array" - add data to database!

**Status After Fix:** All three tabs should show content! 🎉
