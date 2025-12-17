import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import '../services/api_service.dart';

// Use family provider to create separate instances per category
final customCategoryArticlesProvider = StateNotifierProvider.family<ArticleData, List<Article>, String>(
  (ref, categoryId) => ArticleData(categoryId),
);

final hasDataCustomCategoryProvider = StateProvider<bool>((ref) => false);

final isLoadingCustomCategoryProvider = StateProvider<bool>((ref) => true);

class ArticleData extends StateNotifier<List<Article>> {
  final String categoryId;
  ArticleData(this.categoryId) : super([]);

  int _offset = 0;
  final int _limit = 20;
  bool _isLoading = false;

  Future<void> getData(WidgetRef ref) async {
    if (_isLoading) return;
    
    _isLoading = true;
    try {
      print('Loading articles for category: $categoryId, offset: $_offset');
      if (_offset == 0) {
        // Initial load
        final articles = await ApiService().getArticlesByCategory(categoryId, limit: _limit, offset: _offset);
        print('Loaded ${articles.length} articles for category $categoryId');
        state = articles;
        _offset += articles.length;
        ref.read(isLoadingCustomCategoryProvider.notifier).update((state) => false);
      } else {
        // Load more
        ref.read(hasDataCustomCategoryProvider.notifier).update((state) => true);
        final articles = await ApiService().getArticlesByCategory(categoryId, limit: _limit, offset: _offset);
        print('Loaded ${articles.length} more articles for category $categoryId');
        if (articles.isNotEmpty) {
          state = [...state, ...articles];
          _offset += articles.length;
        }
        ref.read(hasDataCustomCategoryProvider.notifier).update((state) => false);
      }
    } catch (e) {
      print('Error loading articles for category $categoryId: $e');
      _handleError(ref, e.toString());
    } finally {
      _isLoading = false;
    }
  }

  _handleError(ref, String error) {
    ref.read(isLoadingCustomCategoryProvider.notifier).update((state) => false);
    ref.read(hasDataCustomCategoryProvider.notifier).update((state) => false);
    debugPrint(error);
  }
}
