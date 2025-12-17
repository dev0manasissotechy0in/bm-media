import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../services/api_service.dart';

final latestArticlesProvider = StateNotifierProvider<LatestArticlesNotifier, List<Article>>((ref) {
  return LatestArticlesNotifier();
});

final isLoadingLatestArticlesProvider = StateProvider<bool>((ref) => false);

class LatestArticlesNotifier extends StateNotifier<List<Article>> {
  LatestArticlesNotifier() : super([]);
  
  int _page = 1;
  bool _isLoading = false;
  bool _hasMore = true;

  Future<void> getData(WidgetRef ref) async {
    if (_isLoading || !_hasMore) return;
    
    _isLoading = true;
    ref.read(isLoadingLatestArticlesProvider.notifier).state = true;

    try {
      // For now, just load all articles once
      // TODO: Implement proper pagination in API
      if (state.isEmpty) {
        final articles = await ApiService().getAllArticles();
        state = articles;
        if (articles.isEmpty) {
          _hasMore = false;
        }
      } else {
        // No more pages for now
        _hasMore = false;
      }
    } catch (e) {
      print('Error loading articles: $e');
    } finally {
      _isLoading = false;
      ref.read(isLoadingLatestArticlesProvider.notifier).state = false;
    }
  }
}
