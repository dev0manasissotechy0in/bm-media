class Podcast {
  final int id;
  final String title;
  final String slug;
  final String description;
  final String thumbnail;
  final String? coverImage;
  final String authorName;
  final String? authorBio;
  final String category;
  final String language;
  final bool isSeries;
  final int totalEpisodes;
  final int viewsCount;
  final int likesCount;
  final int savesCount;
  final String status;
  final DateTime createdAt;
  final DateTime updatedAt;

  Podcast({
    required this.id,
    required this.title,
    required this.slug,
    required this.description,
    required this.thumbnail,
    this.coverImage,
    required this.authorName,
    this.authorBio,
    required this.category,
    required this.language,
    required this.isSeries,
    required this.totalEpisodes,
    required this.viewsCount,
    required this.likesCount,
    required this.savesCount,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Podcast.fromJson(Map<String, dynamic> json) {
    return Podcast(
      id: int.parse(json['id'].toString()),
      title: json['title'] ?? '',
      slug: json['slug'] ?? '',
      description: json['description'] ?? '',
      thumbnail: json['thumbnail'] ?? '',
      coverImage: json['cover_image'],
      authorName: json['author_name'] ?? 'Unknown',
      authorBio: json['author_bio'],
      category: json['category'] ?? 'Podcast',
      language: json['language'] ?? 'English',
      isSeries: json['is_series'] == 1 || json['is_series'] == true,
      totalEpisodes: int.parse((json['total_episodes'] ?? 0).toString()),
      viewsCount: int.parse((json['views_count'] ?? json['views'] ?? 0).toString()),
      likesCount: int.parse((json['likes_count'] ?? json['likes'] ?? 0).toString()),
      savesCount: int.parse((json['saves_count'] ?? json['saves'] ?? 0).toString()),
      status: json['status'] ?? 'published',
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: DateTime.parse(json['updated_at']),
    );
  }

  String get imageUrl => coverImage ?? thumbnail;
}

class Episode {
  final int id;
  final int podcastId;
  final String title;
  final String description;
  final String audioUrl;
  final int duration;
  final int? seasonNumber;
  final int episodeNumber;
  final int likesCount;
  final String status;
  final DateTime createdAt;

  Episode({
    required this.id,
    required this.podcastId,
    required this.title,
    required this.description,
    required this.audioUrl,
    required this.duration,
    this.seasonNumber,
    required this.episodeNumber,
    required this.likesCount,
    required this.status,
    required this.createdAt,
  });

  factory Episode.fromJson(Map<String, dynamic> json) {
    return Episode(
      id: json['id'],
      podcastId: json['podcast_id'],
      title: json['title'],
      description: json['description'],
      audioUrl: json['audio_url'],
      duration: json['duration'],
      seasonNumber: json['season_number'],
      episodeNumber: json['episode_number'],
      likesCount: json['likes_count'] ?? 0,
      status: json['status'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  String get durationFormatted {
    final hours = duration ~/ 3600;
    final minutes = (duration % 3600) ~/ 60;
    final seconds = duration % 60;
    
    if (hours > 0) {
      return '${hours.toString().padLeft(2, '0')}:${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
    }
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }
}

class PodcastProgress {
  final int podcastId;
  final int? episodeId;
  final int progressSeconds;
  final bool completed;
  final DateTime lastPlayed;

  PodcastProgress({
    required this.podcastId,
    this.episodeId,
    required this.progressSeconds,
    required this.completed,
    required this.lastPlayed,
  });

  factory PodcastProgress.fromJson(Map<String, dynamic> json) {
    return PodcastProgress(
      podcastId: json['podcast_id'],
      episodeId: json['episode_id'],
      progressSeconds: json['progress_seconds'],
      completed: json['completed'] == 1 || json['completed'] == true,
      lastPlayed: DateTime.parse(json['last_played']),
    );
  }
}
