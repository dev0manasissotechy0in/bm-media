import 'dart:convert';
import 'package:http/http.dart' as http;
import '../configs/api_config.dart';
import '../models/case_notification.dart';

class CaseNotificationService {
  static const String baseUrl = '${ApiConfig.baseUrl}/notifications';

  /// Fetch notifications
  static Future<Map<String, dynamic>> getNotifications({
    required int userId,
    int page = 1,
    int perPage = 20,
    bool unreadOnly = false,
    String? type,
  }) async {
    try {
      final queryParams = {
        'user_id': userId.toString(),
        'page': page.toString(),
        'per_page': perPage.toString(),
        if (unreadOnly) 'unread_only': 'true',
        if (type != null) 'type': type,
      };

      final uri = Uri.parse('$baseUrl/list.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          final notificationList = (data['data']['notifications'] as List)
              .map((json) => CaseNotification.fromJson(json))
              .toList();

          return {
            'success': true,
            'notifications': notificationList,
            'unread_count': data['data']['unread_count'] ?? 0,
            'pagination': data['data']['pagination'],
          };
        }
      }

      return {
        'success': false,
        'error': 'Failed to fetch notifications',
        'notifications': <CaseNotification>[],
        'unread_count': 0,
      };
    } catch (e) {
      print('Error fetching notifications: $e');
      return {
        'success': false,
        'error': e.toString(),
        'notifications': <CaseNotification>[],
        'unread_count': 0,
      };
    }
  }

  /// Mark notification as read
  static Future<bool> markAsRead({
    required int userId,
    required int notificationId,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/mark-read.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'user_id': userId,
          'notification_id': notificationId,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }

      return false;
    } catch (e) {
      print('Error marking notification as read: $e');
      return false;
    }
  }

  /// Mark all notifications as read
  static Future<bool> markAllAsRead({required int userId}) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/mark-read.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'user_id': userId,
          'mark_all': true,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }

      return false;
    } catch (e) {
      print('Error marking all notifications as read: $e');
      return false;
    }
  }

  /// Get notification preferences for a case
  static Future<NotificationPreferences?> getPreferences({
    required int userId,
    required int caseId,
  }) async {
    try {
      final uri = Uri.parse('$baseUrl/preferences.php').replace(
        queryParameters: {
          'user_id': userId.toString(),
          'case_id': caseId.toString(),
        },
      );

      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          return NotificationPreferences.fromJson(data['data']);
        }
      }

      return null;
    } catch (e) {
      print('Error fetching preferences: $e');
      return null;
    }
  }

  /// Update notification preferences for a case
  static Future<bool> updatePreferences({
    required int userId,
    required int caseId,
    required NotificationPreferences preferences,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/preferences.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'user_id': userId,
          'case_id': caseId,
          ...preferences.toJson(),
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }

      return false;
    } catch (e) {
      print('Error updating preferences: $e');
      return false;
    }
  }

  /// Get unread count only
  static Future<int> getUnreadCount({required int userId}) async {
    try {
      final result = await getNotifications(
        userId: userId,
        page: 1,
        perPage: 1,
        unreadOnly: true,
      );

      return result['unread_count'] ?? 0;
    } catch (e) {
      print('Error fetching unread count: $e');
      return 0;
    }
  }
}
