import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/loading_list_tile.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/screens/all_articles/articles_view.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../../../components/article_tiles/article_tile2.dart';
import '../../../../services/api_service.dart';

final breakingNewsProvider = FutureProvider<List<Article>>((ref) async {
  debugPrint('📰 Fetching breaking news...');
  final articles = await ApiService().getBreakingNews(4);
  debugPrint('✅ Breaking news loaded: ${articles.length} articles');
  return articles;
});

class BreakingNews extends ConsumerWidget {
  const BreakingNews({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final articlesRef = ref.watch(breakingNewsProvider);
    return articlesRef.when(
      skipError: true,
      skipLoadingOnRefresh: false,
      error: (error, stackTrace){
        debugPrint('error on breaking news: $error');
        return const SizedBox.shrink();
      },
      loading: () => const LoadingListTile(count: 4, height: 200),
      data: (data) {
        if (data.isNotEmpty) {
          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      height: 23,
                      width: 4,
                      decoration: BoxDecoration(color: Theme.of(context).primaryColor, borderRadius: BorderRadius.circular(10)),
                    ),
                    const SizedBox(width: 5),
                    Text(
                      'Breaking News',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
                ListView.separated(
                  padding: const EdgeInsets.only(top: 10, bottom: 20),
                  physics: const NeverScrollableScrollPhysics(),
                  shrinkWrap: true,
                  itemCount: data.length,
                  separatorBuilder: (context, index) => const SizedBox(height: 20),
                  itemBuilder: (context, index) {
                    final Article article = data[index];
                    return ArticleTile2(article: article);
                  },
                ),
              ],
            ),
          );
        }
        return const SizedBox.shrink();
      },
    );
  }
}
