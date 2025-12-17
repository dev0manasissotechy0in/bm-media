import 'package:flutter/material.dart';

/// Notification Model for Case Threads
class CaseNotification {
  final int id;
  final int userId;
  final int? caseId;
  final NotificationType notificationType;
  final String title;
  final String? message;
  final String? actionUrl;
  final String? entityType;
  final int? entityId;
  final bool isRead;
  final bool isSent;
  final DateTime createdAt;
  final DateTime? readAt;

  // Case details (if available)
  final String? caseTitle;
  final String? caseSlug;
  final String? caseThumbnail;
  final String? caseStatus;

  // Formatted values
  final String? createdAtFormatted;
  final String? timeAgo;

  CaseNotification({
    required this.id,
    required this.userId,
    this.caseId,
    required this.notificationType,
    required this.title,
    this.message,
    this.actionUrl,
    this.entityType,
    this.entityId,
    required this.isRead,
    required this.isSent,
    required this.createdAt,
    this.readAt,
    this.caseTitle,
    this.caseSlug,
    this.caseThumbnail,
    this.caseStatus,
    this.createdAtFormatted,
    this.timeAgo,
  });

  factory CaseNotification.fromJson(Map<String, dynamic> json) {
    return CaseNotification(
      id: json['id'] ?? 0,
      userId: json['user_id'] ?? 0,
      caseId: json['case_id'],
      notificationType: _parseNotificationType(json['notification_type']),
      title: json['title'] ?? '',
      message: json['message'],
      actionUrl: json['action_url'],
      entityType: json['entity_type'],
      entityId: json['entity_id'],
      isRead: json['is_read'] == 1 || json['is_read'] == true,
      isSent: json['is_sent'] == 1 || json['is_sent'] == true,
      createdAt: DateTime.parse(json['created_at']),
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      caseTitle: json['case_title'],
      caseSlug: json['case_slug'],
      caseThumbnail: json['case_thumbnail'],
      caseStatus: json['case_status'],
      createdAtFormatted: json['created_at_formatted'],
      timeAgo: json['time_ago'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'case_id': caseId,
      'notification_type': notificationType.value,
      'title': title,
      'message': message,
      'action_url': actionUrl,
      'entity_type': entityType,
      'entity_id': entityId,
      'is_read': isRead,
      'is_sent': isSent,
      'created_at': createdAt.toIso8601String(),
      'read_at': readAt?.toIso8601String(),
      'case_title': caseTitle,
      'case_slug': caseSlug,
      'case_thumbnail': caseThumbnail,
      'case_status': caseStatus,
    };
  }

  static NotificationType _parseNotificationType(String? type) {
    switch (type) {
      case 'new_article':
        return NotificationType.newArticle;
      case 'timeline_event':
        return NotificationType.timelineEvent;
      case 'document_added':
        return NotificationType.documentAdded;
      case 'verdict':
        return NotificationType.verdict;
      case 'case_update':
        return NotificationType.caseUpdate;
      case 'case_closed':
        return NotificationType.caseClosed;
      default:
        return NotificationType.caseUpdate;
    }
  }

  /// Get icon for notification type
  IconData getIcon() {
    switch (notificationType) {
      case NotificationType.newArticle:
        return Icons.article;
      case NotificationType.timelineEvent:
        return Icons.access_time;
      case NotificationType.documentAdded:
        return Icons.description;
      case NotificationType.verdict:
        return Icons.gavel;
      case NotificationType.caseUpdate:
        return Icons.info;
      case NotificationType.caseClosed:
        return Icons.check_circle;
    }
  }

  /// Get color for notification type
  Color getColor() {
    switch (notificationType) {
      case NotificationType.newArticle:
        return Colors.blue;
      case NotificationType.timelineEvent:
        return Colors.cyan;
      case NotificationType.documentAdded:
        return Colors.green;
      case NotificationType.verdict:
        return Colors.red;
      case NotificationType.caseUpdate:
        return Colors.orange;
      case NotificationType.caseClosed:
        return Colors.grey;
    }
  }
}

/// Notification Types
enum NotificationType {
  newArticle('new_article'),
  timelineEvent('timeline_event'),
  documentAdded('document_added'),
  verdict('verdict'),
  caseUpdate('case_update'),
  caseClosed('case_closed');

  final String value;
  const NotificationType(this.value);
}

/// Notification Preferences Model
class NotificationPreferences {
  final bool notifyNewArticles;
  final bool notifyTimelineEvents;
  final bool notifyDocuments;
  final bool notifyVerdicts;

  NotificationPreferences({
    this.notifyNewArticles = true,
    this.notifyTimelineEvents = true,
    this.notifyDocuments = true,
    this.notifyVerdicts = true,
  });

  factory NotificationPreferences.fromJson(Map<String, dynamic> json) {
    return NotificationPreferences(
      notifyNewArticles: json['notify_new_articles'] == 1 || json['notify_new_articles'] == true,
      notifyTimelineEvents: json['notify_timeline_events'] == 1 || json['notify_timeline_events'] == true,
      notifyDocuments: json['notify_documents'] == 1 || json['notify_documents'] == true,
      notifyVerdicts: json['notify_verdicts'] == 1 || json['notify_verdicts'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'notify_new_articles': notifyNewArticles,
      'notify_timeline_events': notifyTimelineEvents,
      'notify_documents': notifyDocuments,
      'notify_verdicts': notifyVerdicts,
    };
  }
}
