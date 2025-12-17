class Podcast {
  final int id;
  final String title;
  final String slug;
  final String description;
  final int categoryId;
  final String categoryName;
  final String categoryColor;
  final String audioFile;
  final String coverImage;
  final int duration; // in seconds
  final int fileSize;
  final String? author;
  final String? host;
  final String? guest;
  final int? episodeNumber;
  final int? seasonNumber;
  final String? metaDescription;
  final String? tags;
  final DateTime? publishedAt;
  final bool isFeatured;
  final bool isActive;
  final int playCount;
  final int downloadCount;
  final int likeCount;
  final int shareCount;
  final DateTime createdAt;
  final DateTime updatedAt;

  Podcast({
    required this.id,
    required this.title,
    required this.slug,
    required this.description,
    required this.categoryId,
    required this.categoryName,
    required this.categoryColor,
    required this.audioFile,
    required this.coverImage,
    required this.duration,
    required this.fileSize,
    this.author,
    this.host,
    this.guest,
    this.episodeNumber,
    this.seasonNumber,
    this.metaDescription,
    this.tags,
    this.publishedAt,
    required this.isFeatured,
    required this.isActive,
    required this.playCount,
    required this.downloadCount,
    required this.likeCount,
    required this.shareCount,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Podcast.fromJson(Map<String, dynamic> json) {
    return Podcast(
      id: json['id'] ?? 0,
      title: json['title'] ?? '',
      slug: json['slug'] ?? '',
      description: json['description'] ?? '',
      categoryId: json['category_id'] ?? 0,
      categoryName: json['category_name'] ?? '',
      categoryColor: json['category_color'] ?? '#3B82F6',
      audioFile: json['audio_file'] ?? '',
      coverImage: json['cover_image'] ?? '',
      duration: json['duration'] ?? 0,
      fileSize: json['file_size'] ?? 0,
      author: json['author'],
      host: json['host'],
      guest: json['guest'],
      episodeNumber: json['episode_number'],
      seasonNumber: json['season_number'],
      metaDescription: json['meta_description'],
      tags: json['tags'],
      publishedAt: json['published_at'] != null 
          ? DateTime.tryParse(json['published_at']) 
          : null,
      isFeatured: json['is_featured'] == 1,
      isActive: json['is_active'] == 1,
      playCount: json['play_count'] ?? 0,
      downloadCount: json['download_count'] ?? 0,
      likeCount: json['like_count'] ?? 0,
      shareCount: json['share_count'] ?? 0,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}

class PodcastProgress {
  final int id;
  final int? userId;
  final String? guestToken;
  final int podcastId;
  final int currentTime; // in seconds
  final int duration; // in seconds
  final double progressPercentage;
  final bool isCompleted;
  final double playbackSpeed;
  final double volume;
  final DateTime lastPlayedAt;
  final DateTime createdAt;

  PodcastProgress({
    required this.id,
    this.userId,
    this.guestToken,
    required this.podcastId,
    required this.currentTime,
    required this.duration,
    required this.progressPercentage,
    required this.isCompleted,
    required this.playbackSpeed,
    required this.volume,
    required this.lastPlayedAt,
    required this.createdAt,
  });

  factory PodcastProgress.fromJson(Map<String, dynamic> json) {
    return PodcastProgress(
      id: json['id'] ?? 0,
      userId: json['user_id'],
      guestToken: json['guest_token'],
      podcastId: json['podcast_id'] ?? 0,
      currentTime: json['current_time'] ?? 0,
      duration: json['duration'] ?? 0,
      progressPercentage: double.parse(json['progress_percentage']?.toString() ?? '0'),
      isCompleted: json['is_completed'] == 1,
      playbackSpeed: double.parse(json['playback_speed']?.toString() ?? '1.0'),
      volume: double.parse(json['volume']?.toString() ?? '1.0'),
      lastPlayedAt: DateTime.parse(json['last_played_at']),
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class PodcastCategory {
  final int id;
  final String name;
  final String slug;
  final String? description;
  final String? icon;
  final String color;
  final int displayOrder;
  final bool isActive;
  final DateTime createdAt;
  final DateTime updatedAt;

  PodcastCategory({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.icon,
    required this.color,
    required this.displayOrder,
    required this.isActive,
    required this.createdAt,
    required this.updatedAt,
  });

  factory PodcastCategory.fromJson(Map<String, dynamic> json) {
    return PodcastCategory(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      slug: json['slug'] ?? '',
      description: json['description'],
      icon: json['icon'],
      color: json['color'] ?? '#3B82F6',
      displayOrder: json['display_order'] ?? 0,
      isActive: json['is_active'] == 1,
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }
}
