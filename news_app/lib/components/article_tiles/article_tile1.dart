import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/tile_bottom.dart';
import 'package:news_app/components/media_icon.dart';
import 'package:news_app/components/premium_tag.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/providers/app_settings_provider.dart';
import 'package:news_app/services/app_service.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../utils/custom_cached_image.dart';

// Big image with title and summary

class ArticleTile extends ConsumerWidget {
  const ArticleTile({super.key, required this.article});

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
                      radius: 5,
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
                    height: 6
                  ),
                  Visibility(
                    visible: article.contentType == 'normal',
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: Text(
                        article.summary ?? AppService.getNormalText(article.description),
                        overflow: TextOverflow.ellipsis,
                        maxLines: 2,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: ArticleTileBottom(article: article, settings: settings),
                  ),
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
