import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/premium_tag.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../models/article.dart';
import '../../providers/app_settings_provider.dart';
import '../../services/app_service.dart';
import '../../utils/custom_cached_image.dart';
import '../media_icon.dart';

class FeaturedTile extends ConsumerWidget {
  final Article article;
  const FeaturedTile({super.key, required this.article});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);

    final heroTag = UniqueKey();
    return InkWell(
      child: Container(
        margin: const EdgeInsets.all(15),
        child: Stack(
          children: <Widget>[
            Hero(
              tag: heroTag,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Container(
                    width: MediaQuery.sizeOf(context).width,
                    decoration: BoxDecoration(color: Theme.of(context).cardColor, borderRadius: BorderRadius.circular(5)),
                    child: CustomCacheImage(imageUrl: article.thumbnailUrl, radius: 5),
                  ),
                  MediaIcon(article: article, iconSize: 50),
                ],
              ),
            ),
            PremiumTag(article: article, topMargin: 50),
            Positioned(
              bottom: 0,
              left: 0,
              right: 0,
              child: Container(
                padding: const EdgeInsets.only(left: 15, right: 15, top: 15, bottom: 15),
                decoration: const BoxDecoration(
                    color: Colors.black26, borderRadius: BorderRadius.only(bottomLeft: Radius.circular(5), bottomRight: Radius.circular(5))),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: <Widget>[
                    Text(
                      AppService.getNormalText(article.title),
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w600),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(
                      height: 5,
                    ),
                    settings?.date == false
                        ? const SizedBox.shrink()
                        : Row(
                            children: <Widget>[
                              const Icon(CupertinoIcons.time, size: 16, color: Colors.white),
                              const SizedBox(
                                width: 5,
                              ),
                              Text(
                                AppService.getDate(article.createdAt),
                                style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: Colors.grey.shade200),
                              )
                            ],
                          )
                  ],
                ),
              ),
            ),
            Align(
              alignment: Alignment.topLeft,
              child: Padding(
                padding: const EdgeInsets.all(15),
                child: Row(
                  children: <Widget>[
                    // Live Badge
                    if (article.isLive == true)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.red,
                          borderRadius: BorderRadius.circular(5),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.red.withValues(alpha: 0.5),
                              spreadRadius: 1,
                              blurRadius: 8,
                            ),
                          ],
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Container(
                              width: 8,
                              height: 8,
                              decoration: const BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                              ),
                            ),
                            const SizedBox(width: 6),
                            const Text(
                              'LIVE',
                              style: TextStyle(
                                color: Colors.white,
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 1.2,
                              ),
                            ),
                          ],
                        ),
                      ),
                    // Breaking Badge
                    if (article.isBreaking == true && article.isLive != true)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            colors: [Colors.orange.shade700, Colors.orange.shade900],
                          ),
                          borderRadius: BorderRadius.circular(5),
                        ),
                        child: const Text(
                          '⚡ BREAKING',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 12,
                            fontWeight: FontWeight.bold,
                            letterSpacing: 0.8,
                          ),
                        ),
                      ),
                  ],
                ),
              ),
            ),
            Align(
              alignment: Alignment.topRight,
              child: Padding(
                padding: const EdgeInsets.all(15),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: <Widget>[
                    // Only show Featured badge if article is NOT Live (Live takes priority)
                    if (article.isLive != true) ...[
                      Container(
                        padding: const EdgeInsets.only(
                          left: 10,
                          right: 10,
                        ),
                        height: 30,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(borderRadius: BorderRadius.circular(5), color: Theme.of(context).primaryColor.withValues(alpha: 0.7)),
                        child: const Text(
                          'featured',
                          style: TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w600),
                        ).tr(),
                      ),
                      const SizedBox(width: 8),
                    ],
                    settings?.likes == false
                        ? const SizedBox.shrink()
                        : Container(
                            padding: const EdgeInsets.only(left: 10, right: 10, top: 6, bottom: 6),
                            height: 30,
                            decoration: BoxDecoration(borderRadius: BorderRadius.circular(25), color: Colors.black45),
                            child: Row(
                              children: <Widget>[
                                const Icon(
                                  Icons.favorite,
                                  size: 20,
                                  color: Colors.white,
                                ),
                                const SizedBox(
                                  width: 2,
                                ),
                                Text(
                                  article.likes.toString(),
                                  style: const TextStyle(color: Colors.white),
                                )
                              ],
                            ),
                          ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
      onTap: () => NextScreen().handlePostNavigation(context, article, heroTag, false, ref),
    );
  }
}
