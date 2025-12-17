import 'package:flutter/foundation.dart';
import '../models/author.dart';
import '../services/api_service.dart';
import '../services/firestore_stub.dart';
import 'article_category.dart';

class Article {
  final String id, title, priceStatus, description;
  final String? thumbnailUrl, summary, videoUrl, audioUrl, sourceUrl, shareUrl, content;
  final DateTime createdAt;
  // ignore: prefer_typing_uninitialized_variables
  var updatedAt;
  List<String>? tagIDs;
  final Author? author;
  final int? likes, views, comments;
  bool? isFeatured, isCommentsEnabled, isLive, isBreaking, hasArticle;
  final String contentType;
  String status;
  final ArticleCategory? category;
  List<Map<String, dynamic>>? timelineUpdates; // For live news updates

  Article({
    required this.id,
    this.thumbnailUrl,
    this.videoUrl,
    required this.createdAt,
    this.updatedAt,
    this.tagIDs,
    required this.status,
    this.author,
    this.likes,
    required this.priceStatus,
    this.isFeatured,
    this.isLive,
    this.isBreaking,
    this.hasArticle,
    required this.title,
    this.summary,
    this.audioUrl,
    this.views,
    this.comments,
    required this.contentType,
    required this.description,
    this.content,
    this.isCommentsEnabled,
    this.sourceUrl,
    this.shareUrl,
    this.category,
    this.timelineUpdates,
  });

  // Helper to convert int/bool to bool
  static bool _toBool(dynamic value, {bool defaultValue = false}) {
    if (value == null) return defaultValue;
    if (value is bool) return value;
    if (value is int) return value != 0;
    if (value is String) return value.toLowerCase() == 'true' || value == '1';
    return defaultValue;
  }

  factory Article.fromJson(Map<String, dynamic> d) {
    debugPrint('🔵 Article.fromJson - id: ${d['id']}, content_type: ${d['content_type']}');
    
    return Article(
      id: d['id'].toString(),
      title: d['title'] ?? 'Untitled',
      thumbnailUrl: ApiService.fixImageUrl(d['image_url']),
      createdAt: DateTime.parse(d['created_at'] ?? DateTime.now().toIso8601String()),
      updatedAt: d['updated_at'],
      videoUrl: ApiService.fixImageUrl(d['video_url']),
      tagIDs: d['tag_ids'] != null ? List<String>.from(d['tag_ids']) : [],
      status: d['status'] ?? 'published',
      author: d['author'] != null ? Author.fromMap(d['author']) : null,
      priceStatus: d['price_status'] ?? 'free',
      isFeatured: _toBool(d['featured']),
      isLive: _toBool(d['is_live']),
      isBreaking: _toBool(d['is_breaking']),
      hasArticle: _toBool(d['has_article']),
      isCommentsEnabled: _toBool(d['comments'], defaultValue: true),
      contentType: d['content_type'] ?? 'standard',
      description: d['description'] ?? '',
      content: d['content'],
      summary: d['summary'],
      audioUrl: ApiService.fixImageUrl(d['audio_url']),
      views: d['views'] is int ? d['views'] : (int.tryParse(d['views']?.toString() ?? '0') ?? 0),
      likes: d['likes'] is int ? d['likes'] : (int.tryParse(d['likes']?.toString() ?? '0') ?? 0),
      comments: d['comments'] is int ? d['comments'] : (int.tryParse(d['comments']?.toString() ?? '0') ?? 0),
      sourceUrl: d['source'],
      shareUrl: d['share_url'],
      category: d['category'] != null ? ArticleCategory.fromMap(d['category']) : null,
      timelineUpdates: d['timeline_updates'] != null ? List<Map<String, dynamic>>.from(d['timeline_updates']) : null,
      
    );
  }

  // Stub method for Firestore compatibility during migration
  factory Article.fromFirestore(DocumentSnapshot snap) {
    Map d = snap.data() as Map<String, dynamic>;
    return Article.fromJson({
      'id': snap.id,
      ...d,
    });
  }

}