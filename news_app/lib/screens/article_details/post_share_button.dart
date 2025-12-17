import 'dart:io';
import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:share_plus/share_plus.dart';
import '../../configs/app_config.dart';
import '../../providers/package_provider.dart';
import '../../services/deep_link_service.dart';
import '../../services/api_service.dart';

class PostShareButton extends ConsumerWidget {
  const PostShareButton({super.key, required this.article, this.bgColor, this.hasCircle, this.iconSize, this.iconColor});

  final Article article;
  final Color? bgColor;
  final bool? hasCircle;
  final double? iconSize;
  final Color? iconColor;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final button = IconButton(
      icon: Icon(FeatherIcons.share2, size: iconSize ?? 18, color: iconColor),
      onPressed: () {
        // Use shareUrl from article if available (for reels), otherwise generate deep link
        String deepLink = article.shareUrl ?? DeepLinkService.generateArticleWebLink(article.id.toString());
        
        // Ensure the URL includes the site domain
        if (!deepLink.startsWith('http://') && !deepLink.startsWith('https://')) {
          deepLink = '${ApiService.baseUrl}$deepLink';
        }
        
        debugPrint('📤 Sharing article: ${article.title}');
        debugPrint('🔗 Share URL: $deepLink');
        
        // Android
        final packageInfo = ref.read(packageInfoProvider).value;
        final String androidPackageName = packageInfo?.packageName ?? '';

        final String shareTextAndroid =
            "${article.title}\n\nRead full article: $deepLink\n\nDownload ${AppConfig.appName} App: https://play.google.com/store/apps/details?id=$androidPackageName";

        // iOS
        final String shareTextiOS =
            "${article.title}\n\nRead full article: $deepLink\n\nDownload ${AppConfig.appName} App: https://apps.apple.com/app/id${AppConfig.iosAppID}";

        final String shareText = Platform.isAndroid ? shareTextAndroid : shareTextiOS;

        SharePlus.instance.share(ShareParams(text: shareText));
      },
    );

    if (hasCircle == null || hasCircle == true) {
      return CircleAvatar(
        backgroundColor: bgColor ?? Theme.of(context).cardColor,
        radius: 18,
        child: button,
      );
    } else {
      return button;
    }
  }
}
