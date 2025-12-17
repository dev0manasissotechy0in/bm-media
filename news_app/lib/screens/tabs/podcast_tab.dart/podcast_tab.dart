import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/ads/inline_ads.dart';
import 'package:news_app/components/podcast_tiles/podcast_tile.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/models/podcast.dart';
import 'package:news_app/screens/search/search_view.dart';
import 'package:news_app/screens/tabs/podcast_tab.dart/podcast_provider.dart';
import 'package:news_app/utils/empty_animation.dart';
import 'package:news_app/utils/loading_widget.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../../components/loading_list_tile.dart';

class PodcastTab extends ConsumerStatefulWidget {
  const PodcastTab({super.key});

  @override
  ConsumerState<PodcastTab> createState() => _PodcastTabState();
}

class _PodcastTabState extends ConsumerState<PodcastTab> with AutomaticKeepAliveClientMixin {
  late ScrollController _controller;
  final textCtlr = TextEditingController();

  @override
  void initState() {
    debugPrint('🎙️ PodcastTab initState called');
    _controller = ScrollController(initialScrollOffset: 0.0);
    _controller.addListener(_scrollListener);
    super.initState();
    debugPrint('🎙️ PodcastTab calling getData...');
    Future.microtask(() => ref.read(podcastsProvider.notifier).getData(ref));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  _scrollListener() async {
    var isEnd = _controller.offset >= _controller.position.maxScrollExtent && !_controller.position.outOfRange;
    if (isEnd) {
      ref.read(podcastsProvider.notifier).getData(ref);
    }
  }

  _onRefresh() async {
    ref.read(hasPodcastsProvider.notifier).update((state) => false);
    ref.read(isPodcastsLoadingProvider.notifier).update((state) => true);
    ref.invalidate(podcastsProvider);
    await ref.read(podcastsProvider.notifier).getData(ref);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    final podcasts = ref.watch(podcastsProvider);
    final hasData = ref.watch(hasPodcastsProvider);
    final isLoading = ref.watch(isPodcastsLoadingProvider);

    return Scaffold(
      backgroundColor: Theme.of(context).canvasColor,
      appBar: AppBar(
        title: const Text('podcasts').tr(),
        centerTitle: false,
        automaticallyImplyLeading: false,
        titleTextStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
        actions: [
          IconButton(onPressed: () async => NextScreen.normal(context, const SearchScreen()), icon: const Icon(FeatherIcons.search, size: 22)),
        ],
      ),
      body: isLoading
          ? const LoadingListTile(
              height: 200,
            )
          : podcasts.isEmpty
              ? EmptyAnimation(animationString: emptyAnimation, title: 'No podcasts available')
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
                          itemCount: podcasts.length,
                          separatorBuilder: (context, index) => const SizedBox(height: 15),
                          itemBuilder: (BuildContext context, int index) {
                            final Podcast podcast = podcasts[index];
                            return Column(
                              children: [
                                InlineAds(ref: ref, index: index),
                                PodcastTile(podcast: podcast),
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
