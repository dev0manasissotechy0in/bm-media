import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/loading_list_tile.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/screens/all_articles/articles_view.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../../../components/article_tiles/article_tile2.dart';
import '../../../../services/api_service.dart';

final popularArticlesProvider = FutureProvider<List<Article>>((ref) async {
  final articles = await ApiService().getPopularArticles(4);
  return articles;
});

class PopularArticles extends ConsumerWidget {
  const PopularArticles({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final articlesRef = ref.watch(popularArticlesProvider);
    return articlesRef.when(
      skipError: true,
      skipLoadingOnRefresh: false,
      error: (error, stackTrace){
        debugPrint('error on popular articles: $error');
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
                      'popular-articles',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                    ).tr(),
                    const Spacer(),
                    TextButton(
                        style: TextButton.styleFrom(padding: const EdgeInsets.all(0), tapTargetSize: MaterialTapTargetSize.shrinkWrap),
                        child: const Text(
                          'view-all',
                        ).tr(),
                        onPressed: () => NextScreen.normal(context, AllArticlesView(articlesBy: ArticlesBy.popular, title: 'popular-articles'.tr()))),
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
