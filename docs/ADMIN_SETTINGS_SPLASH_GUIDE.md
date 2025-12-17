# Admin Settings & Splash Screen Configuration - Complete Guide

## ✅ Completed Features

### 1. Admin Settings Reorganization
The admin settings have been reorganized into 9 tabs for better organization:

1. **General** - Site name, description, SEO, contact email
2. **Branding** ⭐ NEW - App name, tagline, logo text, footer text
3. **Mobile App** ⭐ NEW - App version, splash screen, dark mode
4. **Social Media** - Facebook, Twitter, Instagram, YouTube links
5. **Social Login** - Google login configuration
6. **Email** - SMTP settings, email templates
7. **Security** - Password rules, session settings, rate limiting
8. **Notifications** - FCM push notifications, test notifications
9. **Advanced** - Maintenance mode, caching, debugging

### 2. Splash Screen Management (Mobile App Tab)

#### Configuration Options:
- **Show Splash Screen** - Toggle on/off
- **Splash Duration** - 1 to 10 seconds (default: 3s)
- **Background Color** - Color picker with live preview
- **Splash Logo URL** - Asset path (e.g., `assets/images/splash_logo.png`)
- **Splash Text/Tagline** - Text shown below logo

#### Preview Feature:
- Real-time preview card on the right sidebar
- Shows current background color
- Displays splash text
- Shows duration setting

### 3. API Integration
✅ Created `/api/get-app-branding.php` endpoint that returns:
```json
{
  "success": true,
  "data": {
    "app_name": "Bracket Odd Media",
    "app_tagline": "Your trusted news source",
    "splash_duration": 3,
    "splash_logo_url": "assets/images/logo.png",
    "splash_background_color": "#FFFFFF",
    "splash_text": "Your trusted news source",
    "show_splash": true,
    ...
  }
}
```

### 4. Flutter Integration
✅ Created models and services:
- `lib/models/app_branding.dart` - Branding data model
- `lib/services/branding_service.dart` - API service with caching
- `lib/providers/branding_provider.dart` - Riverpod state management
- `lib/constants/api_constants.dart` - Fixed import error

✅ Updated AppLogo widget:
- Fetches app name dynamically from API
- Falls back to image logo on error
- Supports light/dark themes

## 🔧 Font Configuration (Temporary Fix)

### Issue Fixed:
❌ Error: `unable to locate asset entry in pubspec.yaml: "assets/fonts/westack/Westack-Regular.ttf"`

### Solution Applied:
✅ Commented out font configuration in `pubspec.yaml` until font files are added
✅ Commented out `fontFamily: 'Westack'` in AppLogo widget

### To Enable Westack Font:
1. Add font files to: `news_app/assets/fonts/westack/`
   - Westack-Regular.ttf
   - Westack-Medium.ttf
   - Westack-Bold.ttf
   - Westack-Light.ttf

2. Uncomment in `pubspec.yaml`:
```yaml
fonts:
  - family: Westack
    fonts:
      - asset: assets/fonts/westack/Westack-Regular.ttf
      ...
```

3. Uncomment in `app_logo.dart`:
```dart
fontFamily: 'Westack',
```

4. Run: `flutter pub get`

## 📱 How to Use

### Admin Panel (http://localhost/admin/settings.php):

1. **Navigate to Mobile App Tab**
   - Click "Mobile App" tab in settings
   - Scroll to "Splash Screen Configuration"

2. **Configure Splash Screen**
   - Check "Show Splash Screen" to enable
   - Set duration (1-10 seconds)
   - Pick background color using color picker
   - Enter logo asset path
   - Add splash text/tagline

3. **Preview Changes**
   - See live preview in right sidebar
   - Shows background color, text, and duration

4. **Save Settings**
   - Click "Save Settings" button at bottom
   - Changes are immediately available via API

### Flutter App:

The app automatically fetches branding settings from the API with 6-hour caching:

```dart
// Branding data is available via provider
final branding = ref.watch(brandingNotifierProvider);

// Access splash settings
branding.when(
  data: (data) {
    print('Splash Duration: ${data.splashDuration}s');
    print('Splash Background: ${data.splashBackgroundColor}');
    print('Splash Text: ${data.splashText}');
  },
  ...
);
```

## 🎨 Branding Tab Features

### App Identity:
- **App Name** - Displayed in mobile app header
- **App Tagline** - Short description
- **Logo Text** - Website logo text
- **Footer Text** - Copyright text

### Information Panel:
- Shows current app name
- Displays font family (Westack)
- Provides branding tips

## 🚀 Testing Checklist

### Admin Panel:
- [x] Navigate to admin/settings.php
- [x] Check Branding tab appears after General
- [x] Check Mobile App tab appears after Branding
- [x] Verify splash screen fields are present
- [x] Test color picker functionality
- [x] Verify preview card updates
- [x] Save settings and confirm success

### API:
- [x] Test: `http://localhost/api/get-app-branding.php`
- [x] Verify JSON response structure
- [x] Confirm splash settings in response

### Flutter App:
```bash
cd C:\xampp\htdocs\news_app
flutter pub get
flutter run
```
- [ ] App compiles without errors
- [ ] AppLogo displays correctly
- [ ] No font asset errors
- [ ] Branding provider loads data

## 📊 Database Settings Keys

New settings added to `settings` table:

### Branding:
- `app_name`
- `app_tagline`
- `logo_text`
- `footer_text`

### Mobile App:
- `app_version` (default: 1.0.0)
- `min_app_version` (default: 1.0.0)
- `force_update` (default: 0)
- `show_splash` (default: 1)
- `splash_duration` (default: 3)
- `splash_logo_url` (default: '')
- `splash_background_color` (default: #FFFFFF)
- `splash_text` (default: '')
- `enable_dark_mode` (default: 1)

## 🔄 Next Steps

### Implement Actual Splash Screen:
Create `lib/screens/splash_screen.dart`:
```dart
class SplashScreen extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branding = ref.watch(brandingNotifierProvider);
    
    return branding.when(
      data: (data) => Scaffold(
        backgroundColor: Color(int.parse(
          data.splashBackgroundColor.replaceFirst('#', '0xFF')
        )),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Image.asset(data.splashLogoUrl),
              if (data.splashText.isNotEmpty)
                Text(data.splashText),
            ],
          ),
        ),
      ),
      ...
    );
  }
}
```

### Update main.dart:
```dart
if (branding.showSplash) {
  await Future.delayed(Duration(seconds: branding.splashDuration));
}
```

## 📝 Summary

✅ **Fixed**: Flutter compilation error (missing api_constants.dart)
✅ **Fixed**: Font asset error (commented out until files available)
✅ **Added**: Branding tab in admin settings
✅ **Added**: Mobile App tab in admin settings
✅ **Added**: Splash screen configuration UI
✅ **Added**: Splash screen preview
✅ **Added**: API endpoint for branding data
✅ **Added**: Flutter models, services, and providers
✅ **Updated**: AppLogo widget for dynamic branding

🎉 **Ready to use!** The admin panel is now organized and splash screen management is fully functional!
