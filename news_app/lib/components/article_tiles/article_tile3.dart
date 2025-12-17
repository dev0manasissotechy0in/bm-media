import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/tile_bottom.dart';
import 'package:news_app/components/media_icon.dart';
import 'package:news_app/components/premium_tag.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../models/article.dart';
import '../../providers/app_settings_provider.dart';
import '../../utils/custom_cached_image.dart';

// Small image at the left side

class ArticleTile3 extends ConsumerWidget {
  final Article article;
  final bool? isReplace;
  const ArticleTile3({super.key, required this.article, this.isReplace});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    final heroTag = UniqueKey();
    return InkWell(
      child: Container(
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(5),
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.start,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Flexible(
              flex: 2,
              child: Stack(
                alignment: Alignment.center,
                children: [
                  Hero(
                    tag: heroTag,
                    child: Container(
                      height: 110,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(5.0),
                      ),
                      child: CustomCacheImage(imageUrl: article.thumbnailUrl, radius: 5.0),
                    ),
                  ),
                  MediaIcon(article: article, iconSize: 30),
                  PremiumTag(article: article),
                ],
              ),
            ),
            const SizedBox(width: 15),
            Flexible(
              flex: 4,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.start,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    article.title,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 18),
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 10),
                  ArticleTileBottom(article: article, settings: settings),
                ],
              ),
            )
          ],
        ),
      ),
      onTap: () => NextScreen().handlePostNavigation(context, article, heroTag, false, ref),
    );
  }
}
