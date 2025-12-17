import 'package:firebase_messaging/firebase_messaging.dart';

class NotificationModel {
  final String id, title, body;
  final String? thumbnail, articleId, notificationType, contentType, image, description;
  final DateTime recievedAt;
  bool? read;

  NotificationModel({
    required this.id,
    required this.title,
    required this.body,
    required this.recievedAt,
    this.read,
    this.thumbnail,
    this.articleId,
    this.contentType,
    this.notificationType,
    this.image,
    this.description,
  });

  factory NotificationModel.fromRemoteMessage(RemoteMessage d) {
    // Get title and body from notification or data
    String title = d.notification?.title ?? d.data['title'] ?? '';
    String body = d.notification?.body ?? d.data['body'] ?? d.data['description'] ?? '';
    String? imageUrl = d.data['image_url'] ?? d.data['image'];
    
    return NotificationModel(
      id: d.messageId.toString(),
      title: title,
      recievedAt: DateTime.now(),
      body: body,
      thumbnail: imageUrl,
      articleId: d.data['article_id'],
      contentType: d.data['content_type'],
      notificationType: d.data['notification_type'] ?? d.data['type'] ?? 'custom',
      image: imageUrl,
      description: body,
    );
  }

  factory NotificationModel.fromHive(dynamic d) {
    return NotificationModel(
      id: d['id'],
      title: d['title'],
      recievedAt: d['recieved_at'],
      body: d['description'] ?? d['body'] ?? '',
      read: d['read'] ?? false,
      thumbnail: d['image_url'] ?? d['image'],
      articleId: d['article_id'],
      contentType: d['content_type'],
      notificationType: d['notification_type'],
      image: d['image_url'] ?? d['image'],
      description: d['description'] ?? d['body'],
    );
  }

  static Map<String, dynamic> getMap(NotificationModel d) {
    return {
      'id': d.id,
      'title': d.title,
      'body': d.body,
      'description': d.description ?? d.body,
      'recieved_at': d.recievedAt,
      'read': d.read,
      'image_url': d.thumbnail ?? d.image,
      'image': d.image ?? d.thumbnail,
      'article_id': d.articleId,
      'content_type': d.contentType,
      'notification_type': d.notificationType,
    };
  }
}