import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/screens/notifications/article_notification_tile.dart';
import 'package:news_app/utils/empty_animation.dart';
import '../../constants/app_constants.dart';
import '../../constants/api_constants.dart';
import '../../models/notification_model.dart';
import '../../services/api_service.dart';
import 'clear_notifications_dialog.dart';
import 'custom_notification_tile.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class Notifications extends StatefulWidget {
  const Notifications({super.key});

  @override
  State<Notifications> createState() => _NotificationsState();
}

class _NotificationsState extends State<Notifications> {
  bool _isLoading = false;
  bool _hasServerNotifications = false;
  
  @override
  void initState() {
    super.initState();
    _fetchServerNotifications();
  }
  
  Future<void> _fetchServerNotifications() async {
    setState(() {
      _isLoading = true;
    });
    
    try {
      final response = await http.get(
        Uri.parse('${ApiConstants.baseUrl}/api/get-notifications.php?limit=50&offset=0'),
      ).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['notifications'] != null) {
          final notificationList = Hive.box(notificationTag);
          final List notifications = data['notifications'];
          
          for (var notifData in notifications) {
            // Create notification ID from server ID
            final String notifId = 'server_${notifData['id']}';
            
            // Check if already exists in Hive
            if (!notificationList.containsKey(notifId)) {
              // Add to Hive storage
              final notificationModel = NotificationModel(
                id: notifId,
                title: notifData['title'] ?? '',
                body: notifData['description'] ?? notifData['body'] ?? '',
                notificationType: notifData['notification_type'] ?? 'article',
                articleId: notifData['article_id'],
                image: notifData['image'],
                recievedAt: notifData['timestamp'] != null 
                  ? DateTime.fromMillisecondsSinceEpoch(notifData['timestamp'])
                  : DateTime.now(),
                read: false,
              );
              
              final Map<String, dynamic> notifMap = NotificationModel.getMap(notificationModel);
              await notificationList.put(notifId, notifMap);
            }
          }
          
          setState(() {
            _hasServerNotifications = notifications.isNotEmpty;
          });
        }
      }
    } catch (e) {
      debugPrint('Error fetching server notifications: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final notificationList = Hive.box(notificationTag);
    return Scaffold(
      appBar: AppBar(
        title: const Text('notifications').tr(),
        titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        leading: IconButton(onPressed: () => Navigator.pop(context), icon: const Icon(FeatherIcons.chevronLeft)),
        actions: [
          Visibility(
            visible: notificationList.isNotEmpty,
            child: IconButton(
              onPressed: () => openClearAllDialog(context),
              icon: const Icon(Icons.delete_sweep_outlined),
            ),
          ),
        ],
      ),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          if (_isLoading)
            const LinearProgressIndicator(minHeight: 2),
          ValueListenableBuilder(
            valueListenable: notificationList.listenable(),
            builder: (BuildContext context, dynamic value, Widget? child) {
              List items = notificationList.values.toList();
              List<NotificationModel> notifications = items.map((e) => NotificationModel.fromHive(e)).toList();
              notifications.sort((a, b) => b.recievedAt.compareTo(a.recievedAt));
              if (notifications.isEmpty && !_isLoading) {
                return EmptyAnimation(animationString: notificationAnimation, title: 'no-notification'.tr());
              }
              return Expanded(
                child: ListView.separated(
                  padding: const EdgeInsets.fromLTRB(15, 20, 15, 30),
                  itemCount: items.length,
                  separatorBuilder: (context, index) => const SizedBox(height: 15),
                  itemBuilder: (BuildContext context, int index) {
                    final NotificationModel notification = notifications[index];
                    // Show article tile if it has article_id and is a post/article type
                    if (notification.articleId != null && 
                        (notification.notificationType == 'post' || 
                         notification.notificationType == 'article')) {
                      return ArticleNotiificationTile(notification: notification);
                    }
                    // Otherwise show custom notification tile
                    return CustomNotificationTile(notificationModel: notification);
                  },
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
