import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/tile_bottom.dart';
import 'package:news_app/components/premium_tag.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../providers/app_settings_provider.dart';
import '../../utils/custom_cached_image.dart';
import '../media_icon.dart';

// video articles tile

class ArticleTile4 extends ConsumerWidget {
  const ArticleTile4({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);

    final heroTag = UniqueKey();
    return InkWell(
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(5),
        ),
        child: Wrap(
          children: [
            Stack(
              alignment: Alignment.center,
              children: [
                SizedBox(
                  height: 160,
                  width: MediaQuery.sizeOf(context).width,
                  child: Hero(
                    tag: heroTag,
                    child: CustomCacheImage(
                      imageUrl: article.thumbnailUrl,
                      radius: 5.0,
                      circularShape: false,
                    ),
                  ),
                ),
                MediaIcon(article: article, iconSize: 40),
                PremiumTag(article: article),
              ],
            ),
            Container(
              margin: const EdgeInsets.only(left: 15, right: 15, top: 15, bottom: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(bottom: 5),
                    child: Text(article.category?.name.toString().toUpperCase() ?? ''),
                  ),
                  Text(
                    article.title,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 18),
                  ),
                  const SizedBox(
                    height: 10,
                  ),
                  ArticleTileBottom(article: article, settings: settings),
                ],
              ),
            ),
          ],
        ),
      ),
      onTap: () => NextScreen().handlePostNavigation(context, article, heroTag, false, ref),
    );
  }
}
