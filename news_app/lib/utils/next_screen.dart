import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/constants/app_constants.dart';
// import 'package:news_app/iAP/iap_screen.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import 'package:news_app/providers/user_data_provider.dart';
import 'package:news_app/screens/article_details/layouts/audio_article_view/audio_details_view.dart';
import 'package:news_app/screens/article_details/layouts/details_view1.dart';
import 'package:news_app/screens/article_details/layouts/details_view2.dart';
import 'package:news_app/screens/article_details/layouts/details_view3.dart';
import 'package:news_app/screens/article_details/layouts/video_details_view.dart';
import 'package:news_app/screens/auth/login.dart';
import 'package:news_app/screens/notifications/article_notifcation_details.dart';
import 'package:news_app/screens/notifications/custom_notification_details.dart';
import 'package:news_app/screens/reels/vertical_reel_player.dart';
import '../models/article.dart';
import '../models/notification_model.dart';

class NextScreen {
  static void normal(context, page) {
    Navigator.push(context, MaterialPageRoute(builder: (context) => page));
  }

  static void iOS(context, page) {
    Navigator.push(context, CupertinoPageRoute(builder: (context) => page));
  }

  static void closeOthers(context, page) {
    Navigator.pushAndRemoveUntil(context, MaterialPageRoute(builder: (context) => page), (route) => false);
  }

  static void replace(context, page) {
    Navigator.pushReplacement(context, MaterialPageRoute(builder: (context) => page));
  }

  static void popup(context, page) {
    Navigator.push(
      context,
      MaterialPageRoute(fullscreenDialog: true, builder: (context) => page),
    );
  }

  static void replaceAnimation(context, page) {
    Navigator.of(context).pushReplacement(PageRouteBuilder(
      pageBuilder: (
        BuildContext context,
        Animation<double> animation,
        Animation<double> secondaryAnimation,
      ) =>
          page,
      transitionsBuilder: (
        BuildContext context,
        Animation<double> animation,
        Animation<double> secondaryAnimation,
        Widget child,
      ) =>
          FadeTransition(
        opacity: animation,
        child: child,
      ),
    ));
  }

  static void closeOthersAnimation(context, page) {
    Navigator.of(context).pushAndRemoveUntil(
        PageRouteBuilder(
          pageBuilder: (
            BuildContext context,
            Animation<double> animation,
            Animation<double> secondaryAnimation,
          ) =>
              page,
          transitionsBuilder: (
            BuildContext context,
            Animation<double> animation,
            Animation<double> secondaryAnimation,
            Widget child,
          ) =>
              FadeTransition(
            opacity: animation,
            child: child,
          ),
        ),
        ((route) => false));
  }

  static void openBottomSheet(context, page, {double maxHeight = 0.95, bool isDismissable = true}) {
    showModalBottomSheet(
      enableDrag: isDismissable,
      isScrollControlled: true,
      isDismissible: isDismissable,
      clipBehavior: Clip.antiAliasWithSaveLayer,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.only(topLeft: Radius.circular(15), topRight: Radius.circular(15))),
      constraints: BoxConstraints(
        minHeight: MediaQuery.of(context).size.height * 0.50,
        maxHeight: MediaQuery.of(context).size.height * maxHeight,
      ),
      context: context,
      builder: (context) => page,
    );
  }

  void _navigateToDetailsScreen(context, Article article, Object? heroTag, AppSettingsModel? settings) {
    if (article.contentType == 'video') {
      normal(context, VideoDetailsView(article: article));
    } else if (article.contentType == 'audio') {
      iOS(context, AudioDetailsView(article: article, heroTag: heroTag));
    } else {
      // Treat reel articles like regular articles - show article page first
      final Widget layout = getPostLayout(article, heroTag, settings);
      iOS(context, layout);
    }
  }

  void _navigateToDetailsScreenByReplace(context, Article article, Object? heroTag, bool? isReplace, AppSettingsModel? settings) {
    if (isReplace == null || isReplace == false) {
      _navigateToDetailsScreen(context, article, heroTag, settings);
    } else {
      if (article.contentType == 'video') {
        replace(context, VideoDetailsView(article: article));
      } else if (article.contentType == 'audio') {
        replace(context, AudioDetailsView(article: article));
      } else {
        // Treat reel articles like regular articles - show article page first
        final Widget layout = getPostLayout(article, heroTag, settings);
        replace(context, layout);
      }
    }
  }

  static navigateToNotificationDetailsScreen(context, NotificationModel notification) {
    if (notification.notificationType != null && notification.notificationType == 'post') {
      normal(context, ArticleNotificationDeatils(notification: notification));
    } else {
      normal(context, CustomNotificationDeatils(notificationModel: notification));
    }
  }

  Widget getPostLayout(Article article, Object? heroTag, AppSettingsModel? settings) {
    debugPrint('🎲 Post Details Layout Setting: ${settings?.postDetailsLayout}');
    
    if (settings?.postDetailsLayout == postDetailsLayoutTypes.keys.elementAt(0)) {
      // Random layout selection
      final List<Widget> layouts = [
        ArticleDetailsView1(article: article, heroTag: heroTag),
        ArticleDetailsView2(article: article, heroTag: heroTag),
        ArticleDetailsView3(article: article, heroTag: heroTag),
      ];
      layouts.shuffle();
      final selectedLayout = layouts.first;
      debugPrint('🎲 Random layout selected for: ${article.title}');
      return selectedLayout;
    } else if (settings?.postDetailsLayout == postDetailsLayoutTypes.keys.elementAt(1)) {
      debugPrint('🎯 Fixed Layout 1 selected');
      return ArticleDetailsView1(article: article, heroTag: heroTag);
    } else if (settings?.postDetailsLayout == postDetailsLayoutTypes.keys.elementAt(2)) {
      debugPrint('🎯 Fixed Layout 2 selected');
      return ArticleDetailsView2(article: article, heroTag: heroTag);
    } else if (settings?.postDetailsLayout == postDetailsLayoutTypes.keys.elementAt(3)) {
      debugPrint('🎯 Fixed Layout 3 selected');
      return ArticleDetailsView3(article: article, heroTag: heroTag);
    } else {
      debugPrint('⚠️ Default Layout 1 (fallback)');
      return ArticleDetailsView1(article: article, heroTag: heroTag);
    }
  }

  void handlePostNavigation(context, Article article, Object? heroTag, bool? isReplace, WidgetRef ref) {
    final user = ref.read(userDataProvider);
    final settings = ref.read(appSettingsProvider);

    if (article.priceStatus == 'free') {
      _navigateToDetailsScreenByReplace(context, article, heroTag, isReplace, settings);
    } else {
      if (user != null) {
        final bool _isPremiumUser = user.isPremiumUser ?? false;
        if (_isPremiumUser) {
          _navigateToDetailsScreenByReplace(context, article, heroTag, isReplace, settings);
        } else {
          // openBottomSheet(context, const IAPScreen());
        }
      } else {
        openBottomSheet(context, const LoginScreen(popUpScreen: true));
      }
    }
  }
}
