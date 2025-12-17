import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/article_tile1.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/providers/custom_category_articles_provider.dart';
import 'package:news_app/utils/empty_animation.dart';
import 'package:news_app/utils/loading_widget.dart';
import '../../../ads/ad_manager.dart';
import '../../../ads/native_ad_widget.dart';
import '../../../components/loading_list_tile.dart';

class CustomCategoryTab extends ConsumerStatefulWidget {
  final HomeCategory category;
  final ScrollController sc;

  const CustomCategoryTab({super.key, required this.category, required this.sc});

  @override
  ConsumerState<CustomCategoryTab> createState() => _CustomCategoryTabState();
}

class _CustomCategoryTabState extends ConsumerState<CustomCategoryTab> {
  @override
  void initState() {
    widget.sc.addListener(_scrollListener);
    Future.microtask(() {
      ref.read(customCategoryArticlesProvider(widget.category.id).notifier).getData(ref);
    });
    super.initState();
  }

  _scrollListener() async {
    bool isEnd = widget.sc.offset >= widget.sc.position.maxScrollExtent && !widget.sc.position.outOfRange;
    // debugPrint('isEnd: $isEnd');
    if (isEnd) {
      ref.read(customCategoryArticlesProvider(widget.category.id).notifier).getData(ref);
    }
  }

  _onRefresh() async {
    ref.read(hasDataCustomCategoryProvider.notifier).update((state) => false);
    ref.read(isLoadingCustomCategoryProvider.notifier).update((state) => true);
    ref.invalidate(customCategoryArticlesProvider(widget.category.id));
    await ref.read(customCategoryArticlesProvider(widget.category.id).notifier).getData(ref);
  }

  @override
  Widget build(BuildContext context) {
    final bool hasData = ref.watch(hasDataCustomCategoryProvider);
    final bool isLoading = ref.watch(isLoadingCustomCategoryProvider);
    final articles = ref.watch(customCategoryArticlesProvider(widget.category.id));

    return RefreshIndicator(
      backgroundColor: Theme.of(context).primaryColor,
      color: Colors.white,
      onRefresh: () async => await _onRefresh(),
      child: isLoading
          ? const LoadingListTile(height: 250)
          : articles.isEmpty
              ? EmptyAnimation(animationString: 'assets/animation_files/empty.json', title: 'no-articles'.tr())
              : SingleChildScrollView(
                  child: Column(
                    children: [
                      ListView.separated(
                        physics: const NeverScrollableScrollPhysics(),
                        padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 15),
                        shrinkWrap: true,
                        itemCount: articles.length,
                        separatorBuilder: (context, index) => const SizedBox(height: 20),
                        itemBuilder: (BuildContext context, int index) {
                          if (AdManager.isNativeAdsEnabled(ref) && AdManager.isPostIntervalValid(ref, index)) {
                            return const NativeAdWidget(isSmallSize: false);
                          }
                          return ArticleTile(article: articles[index]);
                        },
                      ),
                      Opacity(
                        opacity: hasData ? 1.0 : 0.0,
                        child: const Padding(
                          padding: EdgeInsets.symmetric(vertical: 30),
                          child: LoadingIndicatorWidget(),
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}
