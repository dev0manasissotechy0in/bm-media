# Flutter App: Custom Notifications Setup

## Overview
This guide shows how to handle custom notifications with action buttons and notification channels in your Flutter app.

## 1. Update `pubspec.yaml`

```yaml
dependencies:
  firebase_messaging: ^14.7.0
  flutter_local_notifications: ^16.3.0
```

Run: `flutter pub get`

## 2. Create Notification Channels (Android)

Create file: `lib/services/notification_channels.dart`

```dart
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class NotificationChannels {
  static const String caseStudyChannel = 'case_study_channel';
  static const String liveNewsChannel = 'live_news_channel';
  static const String breakingNewsChannel = 'breaking_news_channel';
  static const String storiesChannel = 'stories_channel';
  static const String articlesChannel = 'articles_channel';
  static const String defaultChannel = 'default_channel';

  static Future<void> createChannels(FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin) async {
    // Case Study Channel
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(
          const AndroidNotificationChannel(
            caseStudyChannel,
            'Case Studies',
            description: 'Notifications for new case studies and updates',
            importance: Importance.high,
            enableVibration: true,
            playSound: true,
          ),
        );

    // Live News Channel
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(
          const AndroidNotificationChannel(
            liveNewsChannel,
            'Live News',
            description: 'Real-time breaking and live news updates',
            importance: Importance.max,
            enableVibration: true,
            enableLights: true,
            playSound: true,
          ),
        );

    // Breaking News Channel (Highest Priority)
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(
          const AndroidNotificationChannel(
            breakingNewsChannel,
            'Breaking News',
            description: 'Critical breaking news alerts',
            importance: Importance.max,
            enableVibration: true,
            enableLights: true,
            playSound: true,
          ),
        );

    // Stories Channel
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(
          const AndroidNotificationChannel(
            storiesChannel,
            'Stories',
            description: 'New stories and updates',
            importance: Importance.defaultImportance,
            enableVibration: true,
            playSound: true,
          ),
        );

    // Articles Channel
    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(
          const AndroidNotificationChannel(
            articlesChannel,
            'Articles',
            description: 'New article notifications',
            importance: Importance.defaultImportance,
            enableVibration: true,
            playSound: true,
          ),
        );
  }
}
```

## 3. Handle Notification Actions

Update your main notification service to handle action buttons:

```dart
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:share_plus/share_plus.dart';
import 'notification_channels.dart';

class NotificationService {
  final FlutterLocalNotificationsPlugin _flutterLocalNotificationsPlugin = 
      FlutterLocalNotificationsPlugin();
  
  Future<void> initialize() async {
    // Create notification channels
    await NotificationChannels.createChannels(_flutterLocalNotificationsPlugin);
    
    // Initialize local notifications with action handlers
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    
    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
    );
    
    await _flutterLocalNotificationsPlugin.initialize(
      initializationSettings,
      onDidReceiveNotificationResponse: _onNotificationTap,
    );
    
    // Subscribe to FCM messages
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);
  }
  
  void _handleForegroundMessage(RemoteMessage message) {
    // Show notification when app is in foreground
    final data = message.data;
    final channelId = data['channel_id'] ?? NotificationChannels.defaultChannel;
    
    // Parse actions if present
    List<AndroidNotificationAction> actions = [];
    if (data.containsKey('actions')) {
      final actionsJson = jsonDecode(data['actions']);
      actions = (actionsJson as List).map((action) {
        return AndroidNotificationAction(
          action['action'],
          action['title'],
          icon: _getActionIcon(action['action']),
          showsUserInterface: true,
        );
      }).toList();
    }
    
    _flutterLocalNotificationsPlugin.show(
      message.hashCode,
      message.notification?.title,
      message.notification?.body,
      NotificationDetails(
        android: AndroidNotificationDetails(
          channelId,
          _getChannelName(channelId),
          importance: Importance.high,
          priority: Priority.high,
          actions: actions,
          styleInformation: message.data['image'] != null
              ? BigPictureStyleInformation(
                  FilePathAndroidBitmap(message.data['image']),
                )
              : null,
        ),
      ),
      payload: jsonEncode(data),
    );
  }
  
  void _onNotificationTap(NotificationResponse response) {
    final payload = jsonDecode(response.payload ?? '{}');
    
    // Handle action button clicks
    switch (response.actionId) {
      case 'share':
        _handleShare(payload);
        break;
      case 'save':
        _handleSave(payload);
        break;
      case 'open':
      case 'view':
        _handleOpen(payload);
        break;
      default:
        _handleOpen(payload); // Default tap action
    }
  }
  
  void _handleNotificationTap(RemoteMessage message) {
    _handleOpen(message.data);
  }
  
  void _handleShare(Map<String, dynamic> data) {
    final type = data['type'];
    final slug = data['article_slug'] ?? data['case_slug'];
    final title = data['title'] ?? '';
    
    Share.share(
      'Check this out: $title\n\nhttp://yourdomain.com/$type/$slug',
      subject: title,
    );
  }
  
  Future<void> _handleSave(Map<String, dynamic> data) async {
    // Implement save to bookmarks/favorites
    // Example: await BookmarkService.save(data['article_id']);
    print('Saving: ${data}');
  }
  
  void _handleOpen(Map<String, dynamic> data) {
    final route = data['route'];
    // Navigate to appropriate screen
    // Example: navigatorKey.currentState?.pushNamed(route);
    print('Opening: $route');
  }
  
  DrawableResourceAndroidIcon? _getActionIcon(String action) {
    switch (action) {
      case 'share':
        return const DrawableResourceAndroidIcon('ic_share');
      case 'save':
        return const DrawableResourceAndroidIcon('ic_bookmark');
      case 'open':
      case 'view':
        return const DrawableResourceAndroidIcon('ic_open');
      default:
        return null;
    }
  }
  
  String _getChannelName(String channelId) {
    switch (channelId) {
      case NotificationChannels.caseStudyChannel:
        return 'Case Studies';
      case NotificationChannels.liveNewsChannel:
        return 'Live News';
      case NotificationChannels.breakingNewsChannel:
        return 'Breaking News';
      case NotificationChannels.storiesChannel:
        return 'Stories';
      case NotificationChannels.articlesChannel:
        return 'Articles';
      default:
        return 'Notifications';
    }
  }
}
```

## 4. Add Icons for Action Buttons

Place these icons in `android/app/src/main/res/drawable/`:
- `ic_share.xml`
- `ic_bookmark.xml`
- `ic_open.xml`

Example `ic_share.xml`:
```xml
<vector xmlns:android="http://schemas.android.com/apk/res/android"
    android:width="24dp"
    android:height="24dp"
    android:viewportWidth="24"
    android:viewportHeight="24">
    <path
        android:fillColor="#FFFFFF"
        android:pathData="M18,16.08c-0.76,0 -1.44,0.3 -1.96,0.77L8.91,12.7c0.05,-0.23 0.09,-0.46 0.09,-0.7s-0.04,-0.47 -0.09,-0.7l7.05,-4.11c0.54,0.5 1.25,0.81 2.04,0.81 1.66,0 3,-1.34 3,-3s-1.34,-3 -3,-3 -3,1.34 -3,3c0,0.24 0.04,0.47 0.09,0.7L8.04,9.81C7.5,9.31 6.79,9 6,9c-1.66,0 -3,1.34 -3,3s1.34,3 3,3c0.79,0 1.5,-0.31 2.04,-0.81l7.12,4.16c-0.05,0.21 -0.08,0.43 -0.08,0.65 0,1.61 1.31,2.92 2.92,2.92 1.61,0 2.92,-1.31 2.92,-2.92s-1.31,-2.92 -2.92,-2.92z"/>
</vector>
```

## 5. Initialize in main.dart

```dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  
  final notificationService = NotificationService();
  await notificationService.initialize();
  
  runApp(MyApp());
}
```

## Notification Types & Their Features

| Type | Badge | Channel | Priority | Actions |
|------|-------|---------|----------|---------|
| **Case Study** | 📋 New Case Study | Green | High | View Case, Share |
| **Case Update** | 📝 Case Study Update | Green | High | View Case, Share |
| **Live News** | 🔴 Live News | Red | Max | Read, Save, Share |
| **Breaking News** | 🚨 Breaking News | Dark Red | Max | Read, Save, Share |
| **Story** | 📱 New Latest Story | Purple | Default | View, Share |
| **Article** | 📰 New Article | Blue | Default | Read, Save, Share |
