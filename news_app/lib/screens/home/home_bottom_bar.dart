import 'dart:io';
import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:line_icons/line_icons.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/screens/home/home_view.dart';

// Bottom Tabs (0-indexed to match PageView)
final Map<int, List> homeTabs = {
  0: ['home', FeatherIcons.home],
  1: ['reels', Icons.video_library],
  2: ['videos', FeatherIcons.playCircle],
  3: ['podcasts', LineIcons.microphone],
  4: ['casethreads', FeatherIcons.layers],
  // 5: ['categories', FeatherIcons.grid],
  // 6: ['profile', FeatherIcons.user],
};

final navBarIndexProvider = StateProvider<int>((ref) => 0);

class BottomBar extends ConsumerWidget {
  const BottomBar({super.key, required this.settings});

  final AppSettingsModel settings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final currentIndex = ref.watch(navBarIndexProvider);
    // final isDarkMode = ref.watch(themeProvider).isDarkMode;
    final double osSepciicHeight = Platform.isAndroid ? 25 : 38;
    final double height = kBottomNavigationBarHeight + osSepciicHeight;
    final double? letterSpacing = Platform.isIOS ? -0.2 : null;

    final tabs = _getTabs(settings);

    return Container(
      height: height,
      decoration: BoxDecoration(border: Border(top: BorderSide(width: 0.5, color: Theme.of(context).dividerColor))),
      child: BottomNavigationBar(
        type: BottomNavigationBarType.fixed,
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        iconSize: 25,
        elevation: 0,
        currentIndex: currentIndex,
        unselectedLabelStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, letterSpacing: letterSpacing),
        selectedLabelStyle: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, letterSpacing: letterSpacing),
        selectedFontSize: 13,
        selectedItemColor: Theme.of(context).primaryColor,
        unselectedItemColor: Colors.grey,
        onTap: (int index) {
          debugPrint('🔘 Bottom bar tapped: index $index');
          ref.read(navBarIndexProvider.notifier).state = index;
          if (_shouldAnimate(currentIndex, index)) {
            debugPrint('🔘 Animating to page $index');
            ref
                .read(homeTabControllerProvider.notifier)
                .state
                .animateToPage(index, duration: const Duration(milliseconds: 250), curve: Curves.easeIn);
          } else {
            debugPrint('🔘 Jumping to page $index');
            ref.read(homeTabControllerProvider.notifier).state.jumpToPage(index);
          }
        },
        items: tabs.entries.map((e) {
          final isSelected = currentIndex == e.key;
          return BottomNavigationBarItem(
            icon: AnimatedContainer(
              duration: const Duration(milliseconds: 300),
              curve: Curves.easeInOut,
              child: AnimatedScale(
                scale: isSelected ? 1.2 : 1.0,
                duration: const Duration(milliseconds: 300),
                curve: Curves.easeInOut,
                child: Icon(
                  e.value[1],
                  color: isSelected 
                    ? Theme.of(context).primaryColor 
                    : Colors.grey.withOpacity(0.7),
                ),
              ),
            ),
            label: "${e.value[0]}".tr(),
          );
        }).toList(),
      ),
    );
  }

  bool _shouldAnimate(int currentIndex, int newIndex) {
    int dif = currentIndex - newIndex;
    if (dif > 1 || dif < -1) {
      return false;
    } else {
      return true;
    }
  }

  Map<int, List> _getTabs(AppSettingsModel settings) {
    bool audioTabEnabled = settings.audioTab ?? true; // Default to true to always show podcast
    bool videoTabEnabled = settings.videoTab ?? true; // Default to true to always show videos

    // Build tabs dynamically to match home_view.dart order
    Map<int, List> tabs = {
      0: ['home', FeatherIcons.home],
      1: ['reels', Icons.video_library],
    };

    int currentIndex = 2;

    // Add videos tab if enabled
    if (videoTabEnabled) {
      debugPrint('🔘 Bottom bar adding Videos at index $currentIndex');
      tabs[currentIndex] = ['videos', FeatherIcons.playCircle];
      currentIndex++;
    }

    // Add podcast tab if enabled
    if (audioTabEnabled) {
      debugPrint('🔘 Bottom bar adding Podcasts at index $currentIndex');
      tabs[currentIndex] = ['podcasts', LineIcons.microphone];
      currentIndex++;
    }

    // Always add Case Threads tab
    debugPrint('🔘 Bottom bar adding CaseThreads at index $currentIndex');
    tabs[currentIndex] = ['casethreads', FeatherIcons.layers];

    debugPrint('🔘 Bottom bar total tabs: ${tabs.length}');
    return tabs;
  }
}
