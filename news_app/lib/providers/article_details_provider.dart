import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/article.dart';
import '../services/firebase_service_stub.dart';

/// Provider for fetching full article details including live updates
final articleDetailsProvider = FutureProvider.family<Article?, String>((ref, articleId) async {
  debugPrint('🟢 articleDetailsProvider - Fetching article: $articleId');
  final article = await FirebaseService().getArticle(articleId);
  debugPrint('🟢 articleDetailsProvider - Article fetched: ${article?.title}, isLive: ${article?.isLive}, updates: ${article?.timelineUpdates?.length}');
  return article;
});
