import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/ads/inline_ads.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/screens/tabs/videos_tab/video_articles_provider.dart';
import 'package:news_app/utils/loading_widget.dart';
import '../../../components/article_tiles/article_tile4.dart';
import '../../../components/loading_list_tile.dart';
import '../../../configs/app_assets.dart';
import '../../../utils/empty_animation.dart';
import '../../../utils/next_screen.dart';
import '../../all_articles/articles_view.dart';
import '../../search/search_view.dart';

class VideosTab extends ConsumerStatefulWidget {
  const VideosTab({super.key});

  @override
  ConsumerState<VideosTab> createState() => _VideosTabState();
}

class _VideosTabState extends ConsumerState<VideosTab> with AutomaticKeepAliveClientMixin {
  late ScrollController _controller;
  final textCtlr = TextEditingController();

  @override
  void initState() {
    debugPrint('🎬 VideosTab initState called');
    _controller = ScrollController(initialScrollOffset: 0.0);
    _controller.addListener(_scrollListener);
    super.initState();
    debugPrint('🎬 VideosTab calling getData...');
    Future.microtask(() => ref.read(videosProvider.notifier).getData(ref));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  _scrollListener() async {
    var isEnd = _controller.offset >= _controller.position.maxScrollExtent && !_controller.position.outOfRange;
    if (isEnd) {
      ref.read(videosProvider.notifier).getData(ref);
    }
  }

  _onRefresh() async {
    ref.read(hasVideosProvider.notifier).update((state) => false);
    ref.read(isVideosLoadingProvider.notifier).update((state) => true);
    ref.invalidate(videosProvider);
    await ref.read(videosProvider.notifier).getData(ref);
  }

  _onOrderChanged(ArticlesBy newOrder) async {
    ref.read(videoArticlesOrder.notifier).update((state) => newOrder);
    await _onRefresh();
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);
    final articles = ref.watch(videosProvider);
    final hasData = ref.watch(hasVideosProvider);
    final isLoading = ref.watch(isVideosLoadingProvider);

    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      appBar: AppBar(
        title: const Text('videos').tr(),
        centerTitle: false,
        automaticallyImplyLeading: false,
        titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        actions: [
          IconButton(onPressed: () async => NextScreen.normal(context, const SearchScreen()), icon: const Icon(FeatherIcons.search, size: 22)),
          PopupMenuButton(
            icon: const Icon(Icons.sort),
            initialValue: ArticlesBy.latest,
            onSelected: (value) => _onOrderChanged(value),
            itemBuilder: (context) {
              return [
                PopupMenuItem(
                  value: ArticlesBy.latest,
                  child: Text(
                    'latest',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500),
                  ).tr(),
                ),
                PopupMenuItem(
                  value: ArticlesBy.popular,
                  child: Text(
                    'popular',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500),
                  ).tr(),
                )
              ];
            },
          )
        ],
      ),
      body: isLoading
          ? const LoadingListTile(
              height: 200,
            )
          : articles.isEmpty
              ? EmptyAnimation(animationString: emptyAnimation, title: 'no-articles'.tr())
              : RefreshIndicator(
                  onRefresh: () async => await _onRefresh(),
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.only(left: 15, right: 15, bottom: 50, top: 15),
                    physics: const AlwaysScrollableScrollPhysics(),
                    controller: _controller,
                    child: Column(
                      children: [
                        ListView.separated(
                          physics: const NeverScrollableScrollPhysics(),
                          shrinkWrap: true,
                          itemCount: articles.length,
                          separatorBuilder: (context, index) => const SizedBox(height: 20),
                          itemBuilder: (BuildContext context, int index) {
                            final Article article = articles[index];

                            return Column(
                              children: [
                                InlineAds(ref: ref, index: index),
                                ArticleTile4(article: article),
                              ],
                            );
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
                ),
    );
  }

  @override
  bool get wantKeepAlive => true;
}
