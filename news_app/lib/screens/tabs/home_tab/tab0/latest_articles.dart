import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/ads/inline_ads.dart';
import 'package:news_app/components/article_tiles/article_tile1.dart';
import 'package:news_app/providers/latest_articles_provider.dart';
import '../../../../models/article.dart';
import '../../../../utils/loading_widget.dart';

class LattestArticles extends ConsumerStatefulWidget {
  const LattestArticles(this.sc, {super.key});

  final ScrollController sc;

  @override
  ConsumerState<LattestArticles> createState() => _LattestArticlesState();
}

class _LattestArticlesState extends ConsumerState<LattestArticles> {
  @override
  void initState() {
    widget.sc.addListener(_scrollListener);
    Future.microtask(() {
      ref.read(latestArticlesProvider.notifier).getData(ref);
    });
    super.initState();
  }

  _scrollListener() async {
    bool isEnd = widget.sc.offset >= widget.sc.position.maxScrollExtent && !widget.sc.position.outOfRange;
    // debugPrint('isEnd: $isEnd');
    if (mounted) {
      if (isEnd) {
        ref.read(latestArticlesProvider.notifier).getData(ref);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = ref.watch(isLoadingLatestArticlesProvider);
    final articles = ref.watch(latestArticlesProvider);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.center,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 15, right: 15, top: 20, bottom: 0),
          child: Row(
            children: [
              Container(
                height: 23,
                width: 4,
                decoration: BoxDecoration(color: Theme.of(context).primaryColor, borderRadius: BorderRadius.circular(10)),
              ),
              const SizedBox(width: 5),
              Text(
                'latest-articles',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.bold),
              ).tr(),
            ],
          ),
        ),
        articles.isEmpty
            ? Container()
            : ListView.separated(
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.all(15),
                shrinkWrap: true,
                itemCount: articles.length,
                separatorBuilder: (context, index) => const SizedBox(height: 20),
                itemBuilder: (BuildContext context, int index) {
                  final Article article = articles[index];
                  return Column(
                    children: [
                      InlineAds(ref: ref, index: index),
                      ArticleTile(article: article),
                    ],
                  );
                },
              ),
        Opacity(
          opacity: isLoading == true ? 1.0 : 0.0,
          child: const LoadingIndicatorWidget(),
        ),
      ],
    );
  }
}
