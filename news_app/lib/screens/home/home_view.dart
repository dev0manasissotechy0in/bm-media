import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/screens/tabs/categories_tab/categories_tab.dart';
import 'package:news_app/screens/tabs/home_tab/home_tab.dart';
import 'package:news_app/screens/tabs/home_tab/home_tab_without_tabs.dart';
import 'package:news_app/screens/tabs/podcast_tab.dart/podcast_tab.dart';
import 'package:news_app/screens/tabs/videos_tab/videos_tab.dart';
import 'package:news_app/screens/tabs/reels_tab/reels_tab.dart';
import '../tabs/profile_tab/profile_tab.dart';
import '../cases/case_threads_list_screen.dart';
import 'home_bottom_bar.dart';

final homeTabControllerProvider = StateProvider<PageController>((ref) => PageController(initialPage: 0));

class HomeView extends ConsumerWidget {
  const HomeView({super.key, required this.settings});

  final AppSettingsModel settings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final tabController = ref.watch(homeTabControllerProvider);

    return Scaffold(
      bottomNavigationBar: BottomBar(settings: settings),
      body: PageView(
        allowImplicitScrolling: true,
        controller: tabController,
        physics: const NeverScrollableScrollPhysics(),
        children: _childrens(),
      ),
    );
  }

  List<Widget> _childrens() {
    final homeTab = settings.homeCategories?.isEmpty ?? true ? HomeTabWithoutTabs(settings: settings) : HomeTab(settings: settings);

    bool audioTabEnabled = settings.audioTab ?? true; // Default to true to always show podcast
    bool videoTabEnabled = settings.videoTab ?? true; // Default to true to always show videos

    // Build tabs array dynamically to match bottom bar indices exactly
    // IMPORTANT: Only add enabled tabs so indices match bottom bar
    List<Widget> tabs = [
      homeTab,                          // Index 0
      const ReelsTab(),                 // Index 1
    ];

    int currentIndex = 2;

    // Add videos tab if enabled
    if (videoTabEnabled) {
      debugPrint('🏗️ Adding VideosTab at index $currentIndex');
      tabs.add(const VideosTab());
      currentIndex++;
    }

    // Add podcast tab if enabled
    if (audioTabEnabled) {
      debugPrint('🏗️ Adding PodcastTab at index $currentIndex');
      tabs.add(const PodcastTab());
      currentIndex++;
    }

    // Always add Case Threads tab
    debugPrint('🏗️ Adding CaseThreadsListScreen at index $currentIndex');
    tabs.add(const CaseThreadsListScreen());

    debugPrint('🏗️ Total tabs built: ${tabs.length}');
    return tabs;
  }
}
