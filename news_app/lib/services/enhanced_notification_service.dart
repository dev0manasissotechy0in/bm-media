import 'dart:convert';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'api_service.dart';

/// Enhanced Notification Service with backend integration
/// Handles FCM, local notifications, and syncs with PHP backend
class EnhancedNotificationService {
  static final EnhancedNotificationService _instance = EnhancedNotificationService._internal();
  factory EnhancedNotificationService() => _instance;
  EnhancedNotificationService._internal();

  final FirebaseMessaging _firebaseMessaging = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  final ApiService _apiService = ApiService();
  
  String? _fcmToken;
  bool _isInitialized = false;
  
  // Callbacks for navigation
  Function(Map<String, dynamic>)? onNotificationTap;

  /// Initialize the notification service
  Future<void> initialize(BuildContext context) async {
    if (_isInitialized) return;

    try {
      // Request permission for iOS
      NotificationSettings settings = await _firebaseMessaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      if (settings.authorizationStatus != AuthorizationStatus.authorized &&
          settings.authorizationStatus != AuthorizationStatus.provisional) {
        debugPrint('⚠️ User declined or has not accepted permission');
        return;
      }

      debugPrint('✅ User granted permission for notifications');

      // Initialize local notifications
      const AndroidInitializationSettings initializationSettingsAndroid =
          AndroidInitializationSettings('@mipmap/ic_launcher');
      
      final DarwinInitializationSettings initializationSettingsIOS =
          DarwinInitializationSettings(
        requestSoundPermission: true,
        requestBadgePermission: true,
        requestAlertPermission: true,
        onDidReceiveLocalNotification: (id, title, body, payload) async {
          // Handle iOS local notification
        },
      );

      final InitializationSettings initializationSettings =
          InitializationSettings(
        android: initializationSettingsAndroid,
        iOS: initializationSettingsIOS,
      );

      await _localNotifications.initialize(
        initializationSettings,
        onDidReceiveNotificationResponse: (NotificationResponse response) {
          _onNotificationTapped(response.payload);
        },
      );

      // Get FCM token
      _fcmToken = await _firebaseMessaging.getToken();
      debugPrint('📱 FCM Token: $_fcmToken');

      // Register device token with backend
      if (_fcmToken != null) {
        await _registerDevice(_fcmToken!);
      }

      // Listen to token refresh
      _firebaseMessaging.onTokenRefresh.listen((newToken) {
        _fcmToken = newToken;
        _registerDevice(newToken);
      });

      // Handle foreground messages
      FirebaseMessaging.onMessage.listen(_handleForegroundMessage);

      // Handle background message click
      FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationClick);

      // Check if app was opened from notification
      RemoteMessage? initialMessage =
          await _firebaseMessaging.getInitialMessage();
      if (initialMessage != null) {
        _handleNotificationClick(initialMessage);
      }

      _isInitialized = true;
      debugPrint('✅ Enhanced Notification service initialized successfully');
    } catch (e) {
      debugPrint('❌ Error initializing notification service: $e');
    }
  }

  /// Register device token with backend
  Future<void> _registerDevice(String token) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getInt('user_id');
      
      final response = await _apiService.post(
        '/api/notifications/index.php?action=register-device',
        {
          'device_token': token,
          'platform': 'android',
          'user_id': userId,
          'device_info': json.encode({
            'model': 'Android Device',
            'os': 'Android'
          }),
        },
      );

      if (response['success'] == true) {
        debugPrint('✅ Device registered with backend');
      }
    } catch (e) {
      debugPrint('❌ Error registering device: $e');
    }
  }

  /// Handle foreground messages
  void _handleForegroundMessage(RemoteMessage message) {
    debugPrint('📩 Foreground message received: ${message.notification?.title}');

    RemoteNotification? notification = message.notification;

    if (notification != null) {
      _showLocalNotification(
        id: message.hashCode,
        title: notification.title ?? 'Notification',
        body: notification.body ?? '',
        imageUrl: notification.android?.imageUrl ?? notification.apple?.imageUrl,
        payload: json.encode(message.data),
      );
    }
  }

  /// Show local notification
  Future<void> _showLocalNotification({
    required int id,
    required String title,
    required String body,
    String? imageUrl,
    String? payload,
  }) async {
    AndroidNotificationDetails androidDetails = AndroidNotificationDetails(
      'news_channel',
      'News Notifications',
      channelDescription: 'Notifications for news updates',
      importance: Importance.high,
      priority: Priority.high,
      showWhen: true,
      icon: '@mipmap/ic_launcher',
      largeIcon: imageUrl != null ? DrawableResourceAndroidBitmap('@mipmap/ic_launcher') : null,
      styleInformation: imageUrl != null
          ? BigPictureStyleInformation(
              FilePathAndroidBitmap(imageUrl),
              contentTitle: title,
              summaryText: body,
            )
          : BigTextStyleInformation(body),
    );

    const DarwinNotificationDetails iosDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    NotificationDetails platformDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _localNotifications.show(
      id,
      title,
      body,
      platformDetails,
      payload: payload,
    );
  }

  /// Handle notification tap
  void _onNotificationTapped(String? payload) {
    if (payload != null) {
      try {
        final data = json.decode(payload);
        _trackNotificationClick(data);
        
        if (onNotificationTap != null) {
          onNotificationTap!(data);
        }
      } catch (e) {
        debugPrint('Error parsing notification payload: $e');
      }
    }
  }

  /// Handle notification click (from background/terminated)
  void _handleNotificationClick(RemoteMessage message) {
    debugPrint('🔔 Notification clicked: ${message.data}');
    _trackNotificationClick(message.data);
    
    if (onNotificationTap != null) {
      onNotificationTap!(message.data);
    }
  }

  /// Track notification click
  Future<void> _trackNotificationClick(Map<String, dynamic> data) async {
    try {
      final notificationId = data['notification_id'];
      if (notificationId != null) {
        await _apiService.post(
          '/api/notifications/index.php?action=track-click',
          {
            'notification_id': notificationId,
            'device_token': _fcmToken,
          },
        );
        debugPrint('✅ Notification click tracked');
      }
    } catch (e) {
      debugPrint('❌ Error tracking click: $e');
    }
  }

  /// Get all notifications for user
  Future<Map<String, dynamic>> getNotifications({
    int limit = 50,
    int offset = 0,
  }) async {
    try {
      final response = await _apiService.get(
        '/api/notifications/index.php?action=get-notifications&device_token=$_fcmToken&limit=$limit&offset=$offset',
      );

      if (response['success'] == true) {
        return {
          'notifications': List<Map<String, dynamic>>.from(response['notifications'] ?? []),
          'unread_count': response['unread_count'] ?? 0,
        };
      }
    } catch (e) {
      debugPrint('❌ Error fetching notifications: $e');
    }
    return {'notifications': [], 'unread_count': 0};
  }

  /// Get unread count
  Future<int> getUnreadCount() async {
    try {
      final response = await _apiService.get(
        '/api/notifications/index.php?action=get-notifications&device_token=$_fcmToken&limit=1',
      );

      if (response['success'] == true) {
        return response['unread_count'] ?? 0;
      }
    } catch (e) {
      debugPrint('❌ Error fetching unread count: $e');
    }
    return 0;
  }

  /// Mark notification as read
  Future<bool> markAsRead(int notificationId) async {
    try {
      final response = await _apiService.post(
        '/api/notifications/index.php?action=mark-read',
        {
          'notification_id': notificationId,
          'device_token': _fcmToken,
        },
      );
      
      return response['success'] == true;
    } catch (e) {
      debugPrint('❌ Error marking as read: $e');
      return false;
    }
  }

  /// Mark all notifications as read
  Future<bool> markAllAsRead() async {
    try {
      final response = await _apiService.post(
        '/api/notifications/index.php?action=mark-all-read',
        {
          'device_token': _fcmToken,
        },
      );
      
      return response['success'] == true;
    } catch (e) {
      debugPrint('❌ Error marking all as read: $e');
      return false;
    }
  }

  /// Get notification preferences
  Future<Map<String, bool>> getPreferences() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getInt('user_id');

      if (userId != null) {
        final response = await _apiService.get(
          '/api/notifications/index.php?action=get-preferences&user_id=$userId&platform=android',
        );

        if (response['success'] == true) {
          final prefs = response['preferences'];
          return {
            'news_enabled': prefs['news_enabled'] == 1 || prefs['news_enabled'] == true,
            'breaking_enabled': prefs['breaking_enabled'] == 1 || prefs['breaking_enabled'] == true,
            'case_study_enabled': prefs['case_study_enabled'] == 1 || prefs['case_study_enabled'] == true,
            'case_study_update_enabled': prefs['case_study_update_enabled'] == 1 || prefs['case_study_update_enabled'] == true,
            'general_enabled': prefs['general_enabled'] == 1 || prefs['general_enabled'] == true,
          };
        }
      }
    } catch (e) {
      debugPrint('❌ Error loading preferences: $e');
    }
    
    // Return default preferences
    return {
      'news_enabled': true,
      'breaking_enabled': true,
      'case_study_enabled': true,
      'case_study_update_enabled': true,
      'general_enabled': true,
    };
  }

  /// Update notification preferences
  Future<bool> updatePreferences(Map<String, bool> preferences) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userId = prefs.getInt('user_id');

      final response = await _apiService.post(
        '/api/notifications/index.php?action=update-preferences',
        {
          'user_id': userId,
          'platform': 'android',
          'preferences': preferences,
        },
      );

      return response['success'] == true;
    } catch (e) {
      debugPrint('❌ Error updating preferences: $e');
      return false;
    }
  }

  /// Unregister device (call on logout)
  Future<void> unregisterDevice() async {
    try {
      if (_fcmToken != null) {
        await _apiService.post(
          '/api/notifications/index.php?action=unregister-device',
          {
            'device_token': _fcmToken,
          },
        );
        debugPrint('✅ Device unregistered');
      }
    } catch (e) {
      debugPrint('❌ Error unregistering device: $e');
    }
  }

  /// Get FCM token
  String? get fcmToken => _fcmToken;
  
  /// Check if initialized
  bool get isInitialized => _isInitialized;
}

/// Background message handler (must be top-level function)
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  debugPrint('📩 Background message: ${message.notification?.title}');
  // Handle background message here if needed
}
