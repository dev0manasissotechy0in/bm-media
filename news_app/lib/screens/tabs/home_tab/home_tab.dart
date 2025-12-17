import 'package:easy_localization/easy_localization.dart';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:icons_plus/icons_plus.dart';
import 'package:line_icons/line_icons.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/screens/notifications/notifications.dart';
import 'package:news_app/screens/search/search_view.dart';
import 'package:news_app/screens/mobile_stories/mobile_stories.dart';
import 'package:news_app/utils/next_screen.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/providers/story_provider.dart';
import 'package:news_app/services/mobile_stories_service.dart';

import '../../../components/app_logo.dart';
import '../../../components/drawer_menu.dart';
import 'tab_medium.dart';

Future<bool> _checkForUnreadStories(WidgetRef ref) async {
  try {
    final storiesService = MobileStoriesService();
    final stories = await storiesService.getActiveStories();
    if (stories.isEmpty) return false;
    
    final viewedStories = ref.read(storyProvider);
    final allStoryIds = stories.map((s) => s.id).toList();
    return allStoryIds.any((id) => !viewedStories.contains(id));
  } catch (e) {
    return false;
  }
}

class HomeTab extends ConsumerStatefulWidget {
  const HomeTab({
    super.key,
    required this.settings,
  });

  final AppSettingsModel settings;

  @override
  ConsumerState<HomeTab> createState() => _HomeTabState();
}

class _HomeTabState extends ConsumerState<HomeTab> with AutomaticKeepAliveClientMixin, TickerProviderStateMixin {
  late TabController _tabController;
  final scaffoldKey = GlobalKey<ScaffoldState>();
  List<Tab> _categoryTabs = [];

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: widget.settings.homeCategories!.length + 1, initialIndex: 0, vsync: this);
    _categoryTabs = widget.settings.homeCategories!
        .map((e) => Tab(
              text: e.name,
            ))
        .toList()
      ..insert(0, Tab(text: 'explore'.tr()));
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // super.build(context);
    return Scaffold( 
      backgroundColor: Theme.of(context).canvasColor,
      drawer: Visibility(visible: widget.settings.drawerMenu ?? true, child: CustomDrawer(settings: widget.settings)),
      key: scaffoldKey,
      body: NestedScrollView(headerSliverBuilder: (BuildContext context, bool innerBoxIsScrolled) {
        return <Widget>[
          SliverAppBar(
            automaticallyImplyLeading: false,
            centerTitle: widget.settings.logoAtCenter ?? false,
            titleSpacing: widget.settings.drawerMenu == true ? 0 : 20,
            title: const AppLogo(),
            leading: Visibility(
              visible: widget.settings.drawerMenu ?? true,
              child: IconButton(
                icon: const Icon(EvaIcons.menu_2, size: 28),
                onPressed: () {
                  scaffoldKey.currentState!.openDrawer();
                },
              ),
            ),
            elevation: 1,
            actions: <Widget>[
              // IconButton(
              //   icon: const Icon(FeatherIcons.search, size: 22),
              //   onPressed: () => NextScreen.normal(context, const SearchScreen()),
              // ),
              Consumer(
                builder: (context, ref, child) {
                  return FutureBuilder<bool>(
                    future: _checkForUnreadStories(ref),
                    builder: (context, snapshot) {
                      final hasUnread = snapshot.data ?? false;
                      return Stack(
                        clipBehavior: Clip.none,
                        children: [
                          IconButton(
                            padding: const EdgeInsets.only(right: 8),
                            constraints: const BoxConstraints(),
                            icon: const Icon(
                              LineIcons.fire,
                              // size: 22,
                            ),
                            onPressed: () => NextScreen.normal(context, const MobileStories()),
                          ),
                          if (hasUnread)
                            Positioned(
                              right: 8,
                              top: 8,
                              child: Container(
                                width: 8,
                                height: 8,
                                decoration: const BoxDecoration(
                                  color: Colors.red,
                                  shape: BoxShape.circle,
                                ),
                              ),
                            ),
                        ],
                      );
                    },
                  );
                },
              ),
              const SizedBox(width: 3),
              IconButton(
                padding: const EdgeInsets.only(right: 8),
                constraints: const BoxConstraints(),
                icon: const Icon(
                  LineIcons.bell,
                  // size: 22,
                ),
                onPressed: () => NextScreen.normal(context, const Notifications()),
              )
            ],
            pinned: true,
            floating: true,
            forceElevated: innerBoxIsScrolled,
            bottom: TabBar(
              tabAlignment: TabAlignment.start,
              labelStyle: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 17),
              controller: _tabController,
              indicatorSize: TabBarIndicatorSize.label,
              unselectedLabelColor: Colors.grey, //niceish grey
              isScrollable: true,
              indicatorColor: Theme.of(context).primaryColor,
              tabs: _categoryTabs,
            ),
          ),
        ];
      }, body: Builder(
        builder: (BuildContext context) {
          final ScrollController innerScrollController = PrimaryScrollController.of(context);
          return TabMedium(
            sc: innerScrollController,
            tc: _tabController,
            homeCategories: widget.settings.homeCategories ?? [],
            settings: widget.settings,
          );
        },
      )),
    );
  }

  // @override
  // bool get wantKeepAlive => true;
}
