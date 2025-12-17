class CaseThread {
  final int id;
  final String title;
  final String slug;
  final String shortDescription;
  final String fullDescription;
  final String status;
  final String category;
  final String primaryLocation;
  final DateTime startDate;
  final DateTime? endDate;
  final String? thumbnail;
  final String? coverImage;
  final int totalArticles;
  final int totalFollowers;
  final int totalViews;
  final bool isFollowing;
  final DateTime createdAt;
  final DateTime? lastActivityAt;

  CaseThread({
    required this.id,
    required this.title,
    required this.slug,
    required this.shortDescription,
    required this.fullDescription,
    required this.status,
    required this.category,
    required this.primaryLocation,
    required this.startDate,
    this.endDate,
    this.thumbnail,
    this.coverImage,
    required this.totalArticles,
    required this.totalFollowers,
    required this.totalViews,
    required this.isFollowing,
    required this.createdAt,
    this.lastActivityAt,
  });

  factory CaseThread.fromJson(Map<String, dynamic> json) {
    return CaseThread(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      slug: json['slug'] ?? '',
      shortDescription: json['short_description'] ?? '',
      fullDescription: json['full_description'] ?? '',
      status: json['status'] ?? 'active',
      category: json['category'] ?? '',
      primaryLocation: json['primary_location'] ?? '',
      startDate: json['start_date'] != null ? DateTime.parse(json['start_date']) : DateTime.now(),
      endDate: json['end_date'] != null ? DateTime.parse(json['end_date']) : null,
      thumbnail: json['thumbnail'],
      coverImage: json['cover_image'],
      totalArticles: json['total_articles'] ?? 0,
      totalFollowers: json['total_followers'] ?? 0,
      totalViews: json['total_views'] ?? 0,
      isFollowing: json['is_following'] ?? false,
      createdAt: json['created_at'] != null ? DateTime.parse(json['created_at']) : DateTime.now(),
      lastActivityAt: json['last_activity_at'] != null ? DateTime.parse(json['last_activity_at']) : null,
    );
  }
}

class TimelineEvent {
  final int id;
  final String eventTitle;
  final String eventDescription;
  final DateTime eventDate;
  final String? eventTime;
  final String eventType;
  final bool isMajorEvent;
  final int linkedArticlesCount;

  TimelineEvent({
    required this.id,
    required this.eventTitle,
    required this.eventDescription,
    required this.eventDate,
    this.eventTime,
    required this.eventType,
    required this.isMajorEvent,
    required this.linkedArticlesCount,
  });

  factory TimelineEvent.fromJson(Map<String, dynamic> json) {
    return TimelineEvent(
      id: json['id'],
      eventTitle: json['event_title'],
      eventDescription: json['event_description'],
      eventDate: DateTime.parse(json['event_date']),
      eventTime: json['event_time'],
      eventType: json['event_type'],
      isMajorEvent: json['is_major_event'] == 1 || json['is_major_event'] == true,
      linkedArticlesCount: json['linked_articles_count'] ?? 0,
    );
  }
}
