class MobileStory {
  final String id;
  final String title;
  final String imageUrl;
  final String? videoUrl;
  final String mediaType; // 'image' or 'video'
  final String? description;
  final String? link;
  final String? categoryName;
  final DateTime createdAt;
  final DateTime? expiresAt;
  final int viewsCount;
  final bool isExpired;
  
  MobileStory({
    required this.id,
    required this.title,
    required this.imageUrl,
    this.videoUrl,
    this.mediaType = 'image',
    this.description,
    this.link,
    this.categoryName,
    required this.createdAt,
    this.expiresAt,
    this.viewsCount = 0,
    this.isExpired = false,
  });
  
  factory MobileStory.fromJson(Map<String, dynamic> json) {
    return MobileStory(
      id: json['id'].toString(),
      title: json['title'] ?? '',
      imageUrl: json['image_url'] ?? '',
      videoUrl: json['video_url'],
      mediaType: json['media_type'] ?? 'image',
      description: json['description'],
      link: json['link'],
      categoryName: json['category_name'],
      createdAt: DateTime.parse(json['created_at']),
      expiresAt: json['expires_at'] != null ? DateTime.parse(json['expires_at']) : null,
      viewsCount: int.tryParse(json['views_count']?.toString() ?? '0') ?? 0,
      isExpired: json['is_expired'] == 1 || json['is_expired'] == true,
    );
  }
  
  bool get isVideo => mediaType == 'video' && videoUrl != null;
  
  bool get hasExpired {
    if (expiresAt == null) return false;
    return DateTime.now().isAfter(expiresAt!);
  }
  
  Duration get timeRemaining {
    if (expiresAt == null) return Duration.zero;
    final remaining = expiresAt!.difference(DateTime.now());
    return remaining.isNegative ? Duration.zero : remaining;
  }
  
  String get timeRemainingText {
    if (hasExpired) return 'Expired';
    final remaining = timeRemaining;
    if (remaining.inHours > 0) {
      return '${remaining.inHours}h remaining';
    } else if (remaining.inMinutes > 0) {
      return '${remaining.inMinutes}m remaining';
    } else {
      return '${remaining.inSeconds}s remaining';
    }
  }
  
  String get uploadTimeText {
    final now = DateTime.now();
    final difference = now.difference(createdAt);
    
    if (difference.inMinutes < 1) {
      return 'Just now';
    } else if (difference.inMinutes < 60) {
      return '${difference.inMinutes}m ago';
    } else if (difference.inHours < 24) {
      return '${difference.inHours}h ago';
    } else {
      return '${difference.inDays}d ago';
    }
  }
}
