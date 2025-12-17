import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/tile_bottom.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import 'package:news_app/utils/cache_image_filter.dart';
import '../../models/article.dart';
import '../../utils/next_screen.dart';
import '../premium_tag.dart';

// podcast articles tile

class ArticleTile5 extends ConsumerWidget {
  const ArticleTile5({super.key, required this.article, this.height, this.isReplace});

  final Article article;
  final double? height;
  final bool? isReplace;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final heroTag = UniqueKey();
    final settings = ref.read(appSettingsProvider);

    return InkWell(
      child: Container(
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: BorderRadius.circular(5),
        ),
        child: Stack(
          alignment: Alignment.bottomLeft,
          children: [
            SizedBox(
              height: height ?? 200,
              width: MediaQuery.sizeOf(context).width,
              child: Hero(
                tag: heroTag,
                child: CustomCacheImageWithDarkFilterFull(
                  imageUrl: article.thumbnailUrl.toString(),
                  radius: 5,
                ),
              ),
            ),
            // MediaIcon(article: article, iconSize: 40),
            PremiumTag(article: article),
            Align(
              alignment: Alignment.bottomLeft,
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    isReplace == true
                        ? const SizedBox.shrink()
                        : Text(
                            article.category?.name.toUpperCase() ?? '',
                            style: Theme.of(context).textTheme.titleMedium?.copyWith(color: Colors.white, fontSize: 14),
                          ),
                    Text(
                      article.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 18, color: Colors.white),
                    ),
                    const SizedBox(height: 10),
                    ArticleTileBottom(article: article, settings: settings, color: Colors.white)
                  ],
                ),
              ),
            )
          ],
        ),
      ),
      onTap: () {
        NextScreen().handlePostNavigation(context, article, heroTag, isReplace, ref);
      },
    );
  }
}
