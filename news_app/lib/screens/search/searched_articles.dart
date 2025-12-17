import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/ads/inline_ads.dart';
import 'package:news_app/components/article_tiles/article_tile3.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/utils/empty_animation.dart';
import '../../components/loading_list_tile.dart';
import '../../models/article.dart';
import 'search_view.dart';

class SearchedArticles extends ConsumerWidget {
  const SearchedArticles({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final articlesProvider = ref.watch(searchedArticlesProvider);
    return articlesProvider.when(
      loading: () => const LoadingListTile(height: 160),
      error: (error, stackTrace) => Center(
        child: Text(error.toString()),
      ),
      data: (articles) {
        if (articles.isEmpty) return EmptyAnimation(animationString: emptyAnimation, title: 'no-articles'.tr());
        return ListView.separated(
          padding: const EdgeInsets.all(20),
          itemCount: articles.length,
          separatorBuilder: (context, index) => const SizedBox(height: 20),
          itemBuilder: (context, index) {
            final Article article = articles[index];
            return Column(
              children: [
                InlineAds(ref: ref, index: index),
                ArticleTile3(article: article, isReplace: false,),
              ],
            );
          },
        );
      },
    );
  }
}
