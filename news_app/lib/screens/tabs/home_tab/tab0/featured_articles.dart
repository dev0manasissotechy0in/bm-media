import 'package:carousel_slider/carousel_slider.dart';
import 'package:dots_indicator/dots_indicator.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/models/article.dart';
import '../../../../components/article_tiles/featured_tile.dart';
import '../../../../components/loading_tile.dart';
import '../../../../services/api_service.dart';

final featuredArticlesProvider = FutureProvider<List<Article>>((ref) async {
  // Now uses banner.php which returns live articles first, then featured
  debugPrint('🎯 Fetching banner articles...');
  final List<Article> articles = await ApiService().getBannerArticles();
  debugPrint('✅ Banner articles loaded: ${articles.length} articles');
  return articles;
});

final featuredPageIndexProvider = StateProvider<int>((ref) => 0);

class FeaturedArticles extends ConsumerWidget {
  const FeaturedArticles({super.key, required this.settings});
  final AppSettingsModel settings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final articles = ref.watch(featuredArticlesProvider);
    final pageIndex = ref.watch(featuredPageIndexProvider);
    final bool autoSlide = settings.featureAutoSlide ?? true;

    return articles.when(
      skipLoadingOnRefresh: false,
      data: (articles) {
        return Visibility(
          visible: articles.isNotEmpty,
          child: Column(
            children: [
              CarouselSlider(
                items: articles.map((article) {
                  return FeaturedTile(article: article);
                }).toList(),
                options: CarouselOptions(
                  height: 250,
                  enableInfiniteScroll: true,
                  pageSnapping: true,
                  viewportFraction: 1,
                  autoPlay: autoSlide,
                  enlargeCenterPage: false,
                  onPageChanged: (index, reason) {
                    ref.read(featuredPageIndexProvider.notifier).update((state) => index);
                  },
                ),
              ),
              DotsIndicator(
                dotsCount: articles.isEmpty ? 1 : articles.length,
                position: pageIndex.toDouble(),
                decorator: DotsDecorator(
                  activeColor: Theme.of(context).colorScheme.primary,
                  color: Theme.of(context).colorScheme.secondary,
                  spacing: const EdgeInsets.all(3),
                  size: const Size.square(5),
                  activeSize: const Size(20.0, 3.0),
                  activeShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5.0)),
                ),
              ),
            ],
          ),
        );
      },
      error: (e, x) => Text('error: $e, $x'),
      loading: () => const LoadingTile(height: 230, borderRadius: 5, padding: 15),
    );
  }
}
