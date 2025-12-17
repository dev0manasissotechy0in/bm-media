# Deep Linking Setup Guide

## Overview
This app now supports deep linking to enable seamless navigation from website to app and vice versa.

## Supported URL Formats

### 1. Website URLs (HTTP/HTTPS)
- **Article**: `http://192.168.1.3/article.php?id=123`
- **Category**: `http://192.168.1.3/category.php?id=5`
- **Tag**: `http://192.168.1.3/tag.php?id=8`

### 2. Custom Scheme URLs
- **Article**: `newshour://article/123`
- **Category**: `newshour://category/5`
- **Tag**: `newshour://tag/8`
- **Home**: `newshour://home`

## Implementation Details

### Android Configuration
**File**: `android/app/src/main/AndroidManifest.xml`

Deep link intent filters have been added to handle:
- HTTP/HTTPS URLs from website (192.168.1.3, localhost)
- Custom scheme URLs (newshour://)
- Android App Links with `android:autoVerify="true"`

### Flutter Services

#### 1. DeepLinkService
**File**: `lib/services/deep_link_service.dart`

**Features**:
- Initializes on app launch
- Listens for incoming deep links (cold start and warm start)
- Parses URLs and extracts IDs
- Navigates to appropriate screens
- Shows loading indicators during API calls

**Usage**:
```dart
// Initialize in splash screen
DeepLinkService().init(context);

// Generate deep links
final webLink = DeepLinkService.generateArticleWebLink('123');
final customLink = DeepLinkService.generateArticleLink('123');
```

#### 2. InAppUpdateService
**File**: `lib/services/in_app_update_service.dart`

**Features**:
- Checks for app updates on launch
- Supports immediate updates (critical updates, priority >= 5)
- Supports flexible updates (background download, priority < 5)
- Shows user-friendly update dialog

**Usage**:
```dart
// Check for critical updates (on app launch)
InAppUpdateService().checkForCriticalUpdates(context);

// Check for regular updates (manual trigger)
InAppUpdateService().checkForUpdates(context);
```

### Website Integration

#### Article Page
**File**: `article.php`

**Added Features**:
1. **"Open in App" Button**: Direct link to open article in app
2. **"Copy Link" Button**: Copy deep link to clipboard
3. **JavaScript Functions**:
   - `trackAppOpen(type, id)`: Track app open analytics
   - `copyDeepLink(url)`: Copy link with visual feedback

**Implementation**:
```html
<a href="http://192.168.1.3/article.php?id=<?= $article['id'] ?>" 
   class="btn btn-primary btn-sm">
    <i class="bi bi-phone"></i> Open in App
</a>
```

## Testing Deep Links

### On Android Device

#### 1. Test via ADB
```bash
# Test website URL
adb shell am start -W -a android.intent.action.VIEW -d "http://192.168.1.3/article.php?id=1"

# Test custom scheme
adb shell am start -W -a android.intent.action.VIEW -d "newshour://article/1"
```

#### 2. Test via Browser
1. Open Chrome on device
2. Enter URL: `http://192.168.1.3/article.php?id=1`
3. If app is installed, Android will offer to open in app

#### 3. Test via Share
1. Share article link from website
2. Tap link in messaging app
3. App should open to that article

### Expected Behavior

#### Cold Start (App Not Running)
1. User clicks deep link
2. App launches with splash screen
3. Deep link is processed after initialization
4. User navigates directly to article/category/tag

#### Warm Start (App in Background)
1. User clicks deep link
2. App comes to foreground
3. Deep link is processed immediately
4. User navigates directly to article/category/tag

## Troubleshooting

### Issue: Deep link doesn't open app
**Solutions**:
1. Verify app is installed
2. Check intent-filter configuration in AndroidManifest.xml
3. Test with ADB to verify deep link format
4. Ensure hosts match (192.168.1.3 or production domain)

### Issue: App opens but doesn't navigate
**Solutions**:
1. Check logs for deep link parsing errors
2. Verify API endpoint returns article data
3. Ensure context is available when processing link
4. Check that article ID exists in database

### Issue: "Article not found" error
**Solutions**:
1. Verify article ID exists in database
2. Check API endpoint is accessible
3. Verify network connectivity
4. Check article status is 'published'

## Production Deployment

### 1. Update AndroidManifest.xml
Replace `192.168.1.3` with your production domain:
```xml
<data android:host="yourdomain.com" />
```

### 2. Create assetlinks.json
For Android App Links verification, create:
**File**: `https://yourdomain.com/.well-known/assetlinks.json`

```json
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "your.package.name",
    "sha256_cert_fingerprints": [
      "YOUR_APP_SIGNING_KEY_FINGERPRINT"
    ]
  }
}]
```

Get fingerprint:
```bash
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
```

### 3. Update Website URLs
Update all deep links in website to use production domain:
```php
$deepLink = "https://yourdomain.com/article.php?id=" . $article['id'];
```

### 4. Test on Production
1. Deploy app to Play Store (internal testing track)
2. Install app from Play Store
3. Test deep links from production website
4. Verify App Links work without app chooser dialog

## Analytics Integration

Add analytics tracking in `trackAppOpen()` function:

```javascript
function trackAppOpen(type, id) {
    // Google Analytics
    gtag('event', 'app_open_click', {
        'content_type': type,
        'content_id': id,
        'source': 'website'
    });
    
    // Facebook Pixel
    fbq('track', 'AppOpen', {
        content_type: type,
        content_id: id
    });
}
```

## Share Functionality

The app now includes deep links when sharing articles:

**File**: `lib/screens/article_details/post_share_button.dart`

**Share Text Format**:
```
{Article Title}

Read full article: http://192.168.1.3/article.php?id=123

Download {App Name} App: [Play Store URL]
```

This enables recipients to:
1. Open article directly in app (if installed)
2. View article on website (if app not installed)
3. Download app from store

## Future Enhancements

1. **Universal Links (iOS)**: Add iOS deep linking support
2. **Deep Link Analytics**: Track deep link conversion rates
3. **Smart Banners**: Show app install banner on website
4. **Deferred Deep Linking**: Remember deep link during app install
5. **Dynamic Links**: Use Firebase Dynamic Links for cross-platform support

## Support

For issues or questions:
1. Check debug logs: `flutter run --verbose`
2. Verify deep link format matches patterns
3. Test with ADB commands
4. Check AndroidManifest.xml configuration
