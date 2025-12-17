# Splash Screen System - Quick Start

## 🚀 Installation (5 Minutes)

### Step 1: Import Database
```bash
# Via Command Line
mysql -u root news_website < database/splash_screen_schema.sql

# Or via phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select 'news_website' database
# 3. Import > Choose file > splash_screen_schema.sql
```

### Step 2: Create Upload Directory
```bash
# Create directory
mkdir c:\xampp\htdocs\uploads\splash

# Verify it exists
dir c:\xampp\htdocs\uploads\splash
```

### Step 3: Access Admin Panel
```
URL: http://localhost/admin/splash-screen-settings.php
Login: Your admin credentials
```

### Step 4: Configure Default Splash
1. Upload your app logo (PNG, 512x512px)
2. Set background color: #FFFFFF (white)
3. Set text color: #000000 (black)
4. Set tagline: "Your Trusted News Source"
5. Select animation: fade
6. Click **Save Default Settings**

### Step 5: Test in Flutter App
```bash
# Update main route to use new splash
cd news_app

# Add url_launcher dependency (if missing)
flutter pub add url_launcher
flutter pub get

# Run app
flutter run
```

---

## ✅ Verification

### Database Check
```sql
SELECT * FROM splash_screen_settings WHERE id = 1;
```
**Expected:** One row with your configured settings

### API Check
Open in browser:
```
http://localhost/api/splash/settings.php?platform=all&first_time=true
```
**Expected:** JSON with default_splash and possibly dynamic_splash

### App Check
1. Launch Flutter app
2. Should see splash screen with your logo
3. Should display your tagline
4. Should animate (fade effect)
5. Should proceed to app after 2 seconds

---

## 🎯 Quick Configuration Examples

### Example 1: Simple Branding
```
Default Splash:
- Logo: company-logo.png
- Background: #1E40AF (blue)
- Text: #FFFFFF (white)
- Tagline: "News That Matters"
- Animation: fade

Dynamic Splash: OFF
```

### Example 2: Promotional Campaign
```
Default Splash: (same as above)

Dynamic Splash: ON
- Image: holiday-promo.jpg
- Title: "Special Holiday Sale!"
- Subtitle: "Get 50% off premium subscription"
- Button: "Subscribe Now"
- Action: https://yoursite.com/subscribe
- Duration: 5 seconds
- Schedule: Dec 20 - Dec 26
- Platform: all
```

### Example 3: iOS App Store Launch
```
Default Splash: (same as Example 1)

Dynamic Splash: ON
- Image: app-store-banner.png
- Title: "Now on iOS!"
- Subtitle: "Download from App Store"
- Button: "Get it Now"
- Action: https://apps.apple.com/your-app
- Duration: 4 seconds
- Schedule: OFF (always show)
- Platform: ios
- New Users Only: YES
```

---

## 📊 Files Summary

### Backend Files
| File | Lines | Purpose |
|------|-------|---------|
| `database/splash_screen_schema.sql` | 66 | Database table structure |
| `admin/splash-screen-settings.php` | 479 | Admin management interface |
| `api/splash/settings.php` | 135 | Fetch splash configuration |
| `api/splash/track.php` | 50 | Track button clicks |

### Flutter Files
| File | Lines | Purpose |
|------|-------|---------|
| `lib/models/splash_settings.dart` | 80 | Data models |
| `lib/services/splash_api_service.dart` | 95 | API service |
| `lib/screens/splash_new.dart` | 485 | Enhanced splash screen |

**Total:** 1,390 lines of code

---

## 🎨 Admin Panel Overview

```
┌─────────────────────────────────────────────────────────────┐
│  Splash Screen Settings                                      │
├─────────────────────┬───────────────────────────────────────┤
│ DEFAULT SPLASH      │ DYNAMIC SPLASH                        │
│                     │                                       │
│ [Upload Logo]       │ ☑ Enable Dynamic Splash              │
│ 📷 Preview          │                                       │
│                     │ [Upload Full Image]                   │
│ Tagline:            │ 🖼️ Preview                            │
│ [____________]      │                                       │
│                     │ Title: [____________]                 │
│ Colors:             │ Subtitle: [____________]              │
│ Background: [🎨]    │                                       │
│ Text: [🎨]          │ Colors:                               │
│                     │ Background: [🎨]                      │
│ Animation:          │ Text: [🎨]                            │
│ [fade ▼]            │                                       │
│                     │ Button: [____________]                │
│ [Save ✓]            │ Action URL: [____________]            │
│                     │                                       │
│                     │ Duration: [5] seconds                 │
│                     │                                       │
│                     │ ☐ Enable Schedule                     │
│                     │   Start: [Date Time]                  │
│                     │   End: [Date Time]                    │
│                     │                                       │
│                     │ Targeting:                            │
│                     │ ☐ New Users Only                      │
│                     │ Platform: [all ▼]                     │
│                     │                                       │
│                     │ Analytics:                            │
│                     │ 👁️ Impressions: 1,234                 │
│                     │ 👆 Clicks: 567                        │
│                     │                                       │
│                     │ [Save ✓]                              │
└─────────────────────┴───────────────────────────────────────┘
```

---

## 🔄 Workflow

### Admin Workflow
```
1. Login to Admin Panel
   ↓
2. Go to Splash Screen Settings
   ↓
3. Configure Default Splash (always available)
   ↓
4. [Optional] Configure Dynamic Splash
   ↓
5. [Optional] Set Schedule
   ↓
6. [Optional] Set Targeting
   ↓
7. Save Settings
   ↓
8. Preview in Admin Panel
   ↓
9. Test in Mobile App
```

### App Launch Flow
```
User Opens App
   ↓
Flutter Calls API: /api/splash/settings.php
   ↓
API Checks Conditions:
   ├─ Is Dynamic Enabled? ──NO──┐
   ├─ Is Scheduled? ──────────YES├─> Show Default Splash
   ├─ Is Platform Match? ─────YES│
   └─ Is User Targeted? ──────YES│
                                  │
                                  ├─> Show Dynamic Splash
                                  │   (Track Impression)
                                  │
                              User Sees Splash
                                  │
                        ┌─────────┴─────────┐
                        │                   │
                  Auto-proceed        Click Button
                   (after duration)    (Track Click)
                        │                   │
                        └─────────┬─────────┘
                                  │
                           Navigate to App
```

---

## 📈 Analytics Example

### Sample Data
| Metric | Value | Calculation |
|--------|-------|-------------|
| Impressions | 10,000 | Auto-tracked on display |
| Clicks | 1,500 | Tracked on button click |
| CTR | 15% | (1,500 / 10,000) × 100 |

### Interpretation
- **High CTR (>10%)**: Effective splash, good engagement
- **Medium CTR (5-10%)**: Average performance
- **Low CTR (<5%)**: Consider redesigning splash

---

## 🐛 Common Issues

### Issue: "Settings not found"
**Solution:** Import database schema

### Issue: "Image not displaying"
**Solution:** Check uploads/splash/ directory exists and file is uploaded

### Issue: "Dynamic splash not showing"
**Solution:** Check is_dynamic_enabled = 1 in database

### Issue: "Schedule not working"
**Solution:** Verify dates are in correct format (YYYY-MM-DD HH:MM:SS)

### Issue: "Platform targeting not working"
**Solution:** Ensure Flutter app sends correct platform parameter

---

## 📞 Next Steps

1. ✅ Install the system (5 minutes)
2. ✅ Configure default splash
3. ✅ Test in app
4. ⏭️ Create promotional dynamic splash
5. ⏭️ Monitor analytics
6. ⏭️ Optimize based on data

---

## 📚 Documentation

- **Full Guide:** `SPLASH_SCREEN_SYSTEM_GUIDE.md` (comprehensive 500+ line guide)
- **This File:** Quick start and reference
- **Admin Panel:** Built-in help text and tooltips

---

**Status:** ✅ Ready to Use  
**Setup Time:** ~5 minutes  
**Difficulty:** Easy  
**Maintained:** Yes
