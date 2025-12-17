import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/podcast.dart';
import 'package:news_app/services/api_service.dart';

final podcastsProvider = StateNotifierProvider<PodcastData, List<Podcast>>((ref) => PodcastData());

final hasPodcastsProvider = StateProvider<bool>((ref) => false);

final isPodcastsLoadingProvider = StateProvider<bool>((ref) => true);

class PodcastData extends StateNotifier<List<Podcast>> {
  PodcastData() : super([]);

  int currentPage = 1;

  getData(WidgetRef ref) async {
    debugPrint('🎙️ PodcastData.getData() called - currentPage: $currentPage');
    if (currentPage == 1) {
      ref.read(isPodcastsLoadingProvider.notifier).update((state) => true);
      await ApiService().getAllPodcasts(limit: 20, page: currentPage).then((podcasts) {
        debugPrint('🎙️ Received ${podcasts.length} podcasts from API');
        state = podcasts;
        debugPrint('🎙️ State updated with ${state.length} podcasts');
        currentPage++;
        ref.read(isPodcastsLoadingProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    } else {
      ref.read(hasPodcastsProvider.notifier).update((state) => true);
      await ApiService().getAllPodcasts(limit: 20, page: currentPage).then((podcasts) {
        debugPrint('🎙️ Received ${podcasts.length} more podcasts from API');
        state = [...state, ...podcasts];
        debugPrint('🎙️ State updated with ${state.length} total podcasts');
        currentPage++;
        ref.read(hasPodcastsProvider.notifier).update((state) => false);
      }).catchError((e) => _handleError(ref, e.toString()));
    }
    debugPrint('🎙️ getData() completed - state has ${state.length} podcasts');
  }

  _handleError(ref, String error) {
    ref.read(isPodcastsLoadingProvider.notifier).update((state) => false);
    ref.read(hasPodcastsProvider.notifier).update((state) => false);
    debugPrint('❌ Error loading podcasts: $error');
    debugPrint('❌ Full error details: $error');
    debugPrint('❌ Stack trace: ${StackTrace.current}');
  }
  
  void reset() {
    state = [];
    currentPage = 1;
  }
}
