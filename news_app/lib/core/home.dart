import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import '../ads/ad_manager.dart';
import '../mixins/search_mixin.dart';
import '../providers/user_data_provider.dart';
import '../screens/home/home_view.dart';
import '../services/notification_service.dart';
import '../utils/disable_user_dialog.dart';

class Home extends ConsumerStatefulWidget {
  const Home({super.key});

  @override
  ConsumerState<Home> createState() => _HomeState();
}

class _HomeState extends ConsumerState<Home> {
  _initData() async {
    SearchMixin.getRecentSearchList(ref: ref);
    await NotificationService().initFirebasePushNotification(context, ref).then((value) => NotificationService().checkNotificationSubscription(ref));
  }

  _checkUserAccess() async {
    final bool isDisabled = ref.read(userDataProvider)?.isDisbaled ?? false;
    if (isDisabled) {
      await Future.delayed(const Duration(seconds: 3)).then((value) {
        if (!mounted) return;
        openDisableUserDialog(context);
      });
    }
  }

  @override
  void initState() {
    _initData();
    AdManager.initAds(ref);
    _checkUserAccess();
    super.initState();
  }

  // _onBackPressed() async {
  //   final int currentIndex = ref.read(navBarIndexProvider);
  //   if (currentIndex != 0) {
  //     ref.read(navBarIndexProvider.notifier).update((state) => 0);
  //     ref.read(homeTabControllerProvider.notifier).state.animateToPage(0, duration: const Duration(milliseconds: 200), curve: Curves.easeIn);
  //   } else {
  //     await SystemChannels.platform.invokeMethod<void>('SystemNavigator.pop', true);
  //   }
  // }

  @override
  Widget build(BuildContext context) {
    final settings = ref.read(appSettingsProvider);
    return HomeView(settings: settings!);
  }
}
