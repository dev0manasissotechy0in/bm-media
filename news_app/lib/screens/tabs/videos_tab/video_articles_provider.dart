import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/services/api_service.dart';
import '../../all_articles/articles_view.dart';

final videosProvider = StateNotifierProvider<VideoArticlesData, List<Article>>((ref) => VideoArticlesData());

final hasVideosProvider = StateProvider<bool>((ref) => false);

final isVideosLoadingProvider = StateProvider<bool>((ref) => true);

final videoArticlesOrder = StateProvider<ArticlesBy>((ref) => ArticlesBy.latest);

class VideoArticlesData extends StateNotifier<List<Article>> {
  VideoArticlesData() : super([]);

  int currentPage = 1;

  getData(WidgetRef ref) async {
    debugPrint('📹 VideoArticlesData.getData() called - currentPage: $currentPage');
    if (currentPage == 1) {
      ref.read(isVideosLoadingProvider.notifier).update((state) => true);
      await ApiService().getVideoArticles(limit: 20, page: currentPage).then((articles) {
        debugPrint('📹 Received ${articles.length} articles from API');
        state = articles;
        debugPrint('📹 State updated with ${state.length} articles');
        currentPage++;
        ref.read(isVideosLoadingProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    } else {
      ref.read(hasVideosProvider.notifier).update((state) => true);
      await ApiService().getVideoArticles(limit: 20, page: currentPage).then((articles) {
        debugPrint('📹 Received ${articles.length} more articles from API');
        state = [...state, ...articles];
        debugPrint('📹 State updated with ${state.length} total articles');
        currentPage++;
        ref.read(hasVideosProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    }
    debugPrint('📹 getData() completed - state has ${state.length} articles');
  }

  _handleError(ref, String error) {
    ref.read(isVideosLoadingProvider.notifier).update((state) => false);
    ref.read(hasVideosProvider.notifier).update((state) => false);
    debugPrint('❌ Error loading videos: $error');
    debugPrint('❌ Stack trace: ${StackTrace.current}');
  }
  
  void reset() {
    state = [];
    currentPage = 1;
  }
}
