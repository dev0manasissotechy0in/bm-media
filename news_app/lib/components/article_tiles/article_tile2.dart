import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/components/article_tiles/tile_bottom.dart';
import 'package:news_app/components/media_icon.dart';
import 'package:news_app/components/premium_tag.dart';
import 'package:news_app/utils/next_screen.dart';
import '../../models/article.dart';
import '../../providers/app_settings_provider.dart';
import '../../utils/custom_cached_image.dart';

// Small image at the right side

class ArticleTile2 extends ConsumerWidget {
  final Article article;
  const ArticleTile2({super.key, required this.article});

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
          child: Column(
            children: <Widget>[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Flexible(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: <Widget>[
                        Text(
                          article.title,
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600, fontSize: 18),
                          maxLines: 4,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(
                          height: 10,
                        ),
                        Container(
                          padding: const EdgeInsets.only(left: 10, right: 10, top: 3, bottom: 3),
                          decoration: BoxDecoration(borderRadius: BorderRadius.circular(3), color: Colors.blueGrey[600]),
                          child: Text(
                            article.category!.name,
                            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500, color: Colors.white),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 20),
                  Stack(
                    alignment: Alignment.center,
                    children: [
                      Container(
                        height: 100,
                        width: 100,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Hero(tag: heroTag, child: CustomCacheImage(imageUrl: article.thumbnailUrl, radius: 5.0)),
                      ),
                      MediaIcon(article: article, iconSize: 40),
                      PremiumTag(article: article),
                    ],
                  ),
                ],
              ),
              const SizedBox(
                height: 20,
              ),
              ArticleTileBottom(article: article, settings: settings),
            ],
          )),
      onTap: () => NextScreen().handlePostNavigation(context, article, heroTag, false, ref),
    );
  }
}
