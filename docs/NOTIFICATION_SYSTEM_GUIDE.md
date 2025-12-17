# Notification System - Complete Setup Guide

## 📋 Overview

This comprehensive notification system sends push notifications to both web and mobile app users when content is published. It includes:

- **Backend**: PHP NotificationManager class with FCM and Web Push support
- **Mobile App**: Flutter integration with FCM
- **Website**: Web Push API with notification bell
- **Admin Panel**: View all sent notifications and analytics

---

## 🗄️ Database Setup

### 1. Import the Database Schema

```bash
mysql -u root news_website < database/notifications_system.sql
```

Or import via phpMyAdmin:
- Go to phpMyAdmin → news_website database
- Click "Import" → Choose `database/notifications_system.sql`
- Click "Go"

### 2. Verify Tables Created

The following 6 tables should be created:
- `notifications` - Stores all sent notifications
- `user_notifications` - Tracks delivery to individual users
- `user_notification_preferences` - User preferences
- `device_tokens` - FCM tokens for mobile devices
- `notification_analytics` - Daily analytics aggregated by tag
- `web_push_subscriptions` - Web push subscriptions

---

## 🔧 Backend Configuration

### 1. FCM Setup (Firebase Cloud Messaging)

**Get your FCM Server Key:**
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select your project
3. Click Settings (⚙️) → Project settings
4. Go to "Cloud Messaging" tab
5. Copy the "Server key"

**Update NotificationManager.php:**
```php
// Line 11 in includes/NotificationManager.php
$this->fcm_server_key = 'YOUR_ACTUAL_FCM_SERVER_KEY_HERE';
```

### 2. Web Push VAPID Keys (Optional but Recommended)

Generate VAPID keys for web push:
```bash
# Install web-push library
composer require minishlink/web-push

# Generate VAPID keys (one-time)
vendor/bin/web-push generate-keys
```

Update in `includes/NotificationManager.php`:
```php
// Lines 14-15
$this->vapid_public_key = 'YOUR_VAPID_PUBLIC_KEY';
$this->vapid_private_key = 'YOUR_VAPID_PRIVATE_KEY';
```

Update in `assets/js/enhanced-notifications.js`:
```javascript
// Line 85
const vapidPublicKey = 'YOUR_VAPID_PUBLIC_KEY';
```

---

## 📱 Mobile App Setup

### 1. Add Dependencies to pubspec.yaml

```yaml
dependencies:
  firebase_messaging: ^14.7.0
  flutter_local_notifications: ^16.3.0
  shared_preferences: ^2.2.0
```

Run:
```bash
flutter pub get
```

### 2. Android Setup

**android/app/build.gradle:**
```gradle
dependencies {
    implementation platform('com.google.firebase:firebase-bom:32.7.0')
    implementation 'com.google.firebase:firebase-messaging'
}
```

**android/app/src/main/AndroidManifest.xml:**
```xml
<manifest>
    <uses-permission android:name="android.permission.INTERNET"/>
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS"/>
    
    <application>
        <!-- FCM -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_channel_id"
            android:value="news_channel" />
            
        <!-- Notification icon -->
        <meta-data
            android:name="com.google.firebase.messaging.default_notification_icon"
            android:resource="@mipmap/ic_launcher" />
    </application>
</manifest>
```

### 3. Initialize in main.dart

```dart
import 'package:firebase_core/firebase_core.dart';
import 'services/enhanced_notification_service.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase
  await Firebase.initializeApp();
  
  // Setup background message handler
  FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
  
  runApp(MyApp());
}

class MyApp extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'News App',
      home: HomeScreen(),
    );
  }
}

class HomeScreen extends StatefulWidget {
  @override
  _HomeScreenState createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final notificationService = EnhancedNotificationService();
  
  @override
  void initState() {
    super.initState();
    // Initialize notifications
    notificationService.initialize(context);
    
    // Setup navigation callback
    notificationService.onNotificationTap = (data) {
      _handleNotificationNavigation(data);
    };
  }
  
  void _handleNotificationNavigation(Map<String, dynamic> data) {
    final type = data['type'];
    final referenceId = data['reference_id'];
    
    switch (type) {
      case 'news':
        Navigator.pushNamed(context, '/article', arguments: referenceId);
        break;
      case 'breaking':
        Navigator.pushNamed(context, '/story', arguments: referenceId);
        break;
      case 'case_study':
      case 'case_study_update':
        Navigator.pushNamed(context, '/case', arguments: referenceId);
        break;
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('News App'),
        actions: [
          // Notification bell icon
          IconButton(
            icon: Icon(Icons.notifications),
            onPressed: () {
              Navigator.pushNamed(context, '/notifications');
            },
          ),
        ],
      ),
      body: Center(child: Text('Home')),
    );
  }
}
```

### 4. Add Routes

```dart
MaterialApp(
  routes: {
    '/notifications': (context) => NotificationsScreen(),
    '/notification-settings': (context) => NotificationSettingsScreen(),
  },
);
```

---

## 🌐 Website Setup

### 1. Update Header to Load Script

Already done! The header.php file includes:
```php
<!-- Notification System -->
<?php if (Security::isLoggedIn('user')): ?>
<script src="<?= ASSETS_URL ?>/js/enhanced-notifications.js"></script>
<?php endif; ?>
```

### 2. Create Service Worker

Create `c:\xampp\htdocs\service-worker.js`:
```javascript
self.addEventListener('push', function(event) {
    const data = event.data.json();
    
    const options = {
        body: data.body,
        icon: data.icon || '/assets/images/logo.png',
        badge: data.badge || '/assets/images/badge.png',
        image: data.image,
        data: data.data,
        tag: data.tag || 'default',
        requireInteraction: false
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    const url = event.notification.data.url || '/';
    
    event.waitUntil(
        clients.openWindow(url)
    );
});
```

---

## 🎯 Sending Notifications Automatically

### 1. When Article is Published

**In `admin/article-add.php` or `admin/article-edit.php`:**

```php
require_once '../includes/NotificationManager.php';

// After article is inserted/updated
$article_id = $db->insert('articles', $article_data);

if ($article_id) {
    // Send notification
    $notificationManager = new NotificationManager($db);
    $notificationManager->sendNewsNotification(
        $article_id,
        $article_data['title'],
        $category_name
    );
    
    $success = 'Article published and notification sent!';
}
```

### 2. When Mobile Story is Uploaded

**In `admin/mobile-story-add.php`:**

```php
require_once '../includes/NotificationManager.php';

// After story is inserted
$story_id = $db->insert('mobile_stories', $story_data);

if ($story_id) {
    // Send breaking news notification (APP ONLY)
    $notificationManager = new NotificationManager($db);
    $notificationManager->sendBreakingNewsNotification(
        $story_id,
        $story_data['title']
    );
    
    $success = 'Story uploaded and breaking news notification sent!';
}
```

### 3. When Case Study is Uploaded

**In `admin/case-add.php`:**

```php
require_once '../includes/NotificationManager.php';

// After case is inserted
$case_id = $db->insert('cases', $case_data);

if ($case_id) {
    // Send case study notification
    $notificationManager = new NotificationManager($db);
    $notificationManager->sendCaseStudyNotification(
        $case_id,
        $case_data['title']
    );
    
    $success = 'Case study published and notification sent!';
}
```

### 4. When Case Study is Updated (Pinned)

**In `admin/case-edit.php`:**

```php
require_once '../includes/NotificationManager.php';

// Check if case is being pinned or updated
if ($is_pinned_update || $significant_change) {
    $notificationManager = new NotificationManager($db);
    $notificationManager->sendCaseStudyUpdateNotification(
        $case_id,
        $case_data['title']
    );
    
    $success = 'Case study updated and notification sent!';
}
```

---

## 📊 Admin Panel Features

### View All Notifications
- Go to: `http://localhost/admin/notification-manager.php`
- Filter by type, tag, date
- See delivery statistics, click rates

### Analytics Dashboard
- Go to: `http://localhost/admin/notification-analytics.php`
- View daily trends chart
- See performance by type and tag
- Top performing notifications

---

## ✅ Testing the System

### Test 1: Mobile App Registration
1. Run the Flutter app
2. Check backend logs for "Device registered with backend"
3. Verify entry in `device_tokens` table

### Test 2: Send Test Notification (Backend)
```php
require_once 'includes/NotificationManager.php';
$db = new Database();
$nm = new NotificationManager($db);

$result = $nm->sendNotification([
    'title' => 'Test Notification',
    'message' => 'This is a test',
    'type' => 'general',
    'tag' => 'test',
    'target' => 'all'  // or 'app' or 'web'
]);

print_r($result);
```

### Test 3: Check Analytics
1. Go to admin panel
2. View `notification-analytics.php`
3. Check click rates, view counts

---

## 🔍 Troubleshooting

### Notifications not received on mobile
- Check FCM server key is correct
- Verify device token is registered in database
- Check Firebase Console → Cloud Messaging → Usage

### Web notifications not working
- Check browser supports notifications (Chrome, Firefox, Edge)
- Verify user granted permission
- Check service worker is registered
- Verify VAPID keys are set

### Click tracking not working
- Check API endpoint `/api/notifications/index.php` is accessible
- Verify notification ID is passed correctly
- Check database permissions

---

## 📈 Analytics Metrics

The system tracks:
- **Sent**: Number of notifications sent
- **Delivered**: Successfully delivered to devices
- **Viewed**: User viewed the notification
- **Clicked**: User clicked the notification
- **CTR**: Click-through rate (clicked / sent * 100)

---

## 🎨 Customization

### Change Notification Icons
Edit `includes/NotificationManager.php`:
```php
// Line 44-49
'📰' => Your emoji or text
```

### Change Notification Sounds (Mobile)
Edit `news_app/lib/services/enhanced_notification_service.dart`:
```dart
// Line 45 - AndroidNotificationDetails
sound: RawResourceAndroidNotificationSound('notification_sound'),
```

### Change Auto-Refresh Interval (Web)
Edit `assets/js/enhanced-notifications.js`:
```javascript
// Line 347
30000  // Change to desired milliseconds
```

---

## 🚀 Production Checklist

- [ ] Replace FCM server key with production key
- [ ] Generate and set VAPID keys for web push
- [ ] Test on actual devices (not emulators)
- [ ] Enable HTTPS for website (required for Web Push)
- [ ] Set up cron job for analytics aggregation
- [ ] Configure notification rate limiting
- [ ] Set up error logging and monitoring
- [ ] Test notification delivery on iOS and Android
- [ ] Verify web push on Chrome, Firefox, Edge
- [ ] Set up backup FCM keys

---

## 📞 Support

For issues or questions:
1. Check database tables are created
2. Verify FCM credentials
3. Check browser console for errors
4. Review server error logs
5. Test with simple notification first

---

**System Created**: December 12, 2025
**Version**: 1.0.0
**Author**: GitHub Copilot
