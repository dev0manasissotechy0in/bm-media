import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/article_tile3.dart';
import 'package:news_app/components/article_tiles/article_tile5.dart';
import 'package:news_app/models/article.dart';

import '../../services/firebase_service_stub.dart';

final relatedArticlesProvider = FutureProvider.family.autoDispose<List<Article>, Article>((ref, article) async {
  final articles = article.contentType == 'audio'
      ? await FirebaseService().getRelatedArticlesByAudio(article, 5)
      : await FirebaseService().getRelatedArticlesByCategory(article, 5);
  return articles;
});

class RelatedArticles extends ConsumerWidget {
  const RelatedArticles({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final articlesRef = ref.watch(relatedArticlesProvider(article));
    debugPrint(article.contentType);
    return articlesRef.when(
      skipError: true,
      error: (error, stackTrace) => Container(),
      loading: () => Container(),
      data: (data) {
        if (data.isNotEmpty) {
          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 20),
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
                      'related-articles',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
                    ).tr(),
                  ],
                ),
                ListView.separated(
                  padding: const EdgeInsets.symmetric(vertical: 20),
                  physics: const NeverScrollableScrollPhysics(),
                  shrinkWrap: true,
                  itemCount: data.length,
                  separatorBuilder: (context, index) => const SizedBox(height: 20),
                  itemBuilder: (context, index) {
                    final Article _article = data[index];
                    if (article.contentType == 'audio') return ArticleTile5(article: _article, height: 120, isReplace: true);
                    return ArticleTile3(article: _article, isReplace: true);
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
