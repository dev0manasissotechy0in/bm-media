import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/screens/article_details/post_share_button.dart';
import 'package:news_app/screens/article_details/related_articles.dart';
import 'package:news_app/utils/cache_image_filter.dart';
import '../../../../ads/ad_manager.dart';
import '../../../../ads/banner_ad.dart';
import '../../../../components/html_body.dart';
import '../../../../services/app_service.dart';
import '../../post_category.dart';
import '../../article_summary.dart';
import '../../author_info.dart';
import '../../bookmark_button.dart';
import '../../comments_button.dart';
import '../../date_and_reading_time.dart';
import '../../like_button.dart';
import '../../likes_count.dart';
import '../../post_back_button.dart';
import '../../post_source_button.dart';
import '../../views_count.dart';
import 'audio_player_widget.dart';

class AudioDetailsView extends ConsumerWidget {
  const AudioDetailsView({super.key, required this.article, this.heroTag});

  final Article article;
  final Object? heroTag;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    AdManager.initInterstitailAds(ref);

    return Scaffold(
      resizeToAvoidBottomInset: false,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            flexibleSpace: FlexibleSpaceBar(
              background: _AudioWidget(article: article, heroTag: heroTag),
            ),
            expandedHeight: 240,
            pinned: true,
            floating: false,
            elevation: 0,
            automaticallyImplyLeading: false,
            systemOverlayStyle: SystemUiOverlayStyle.light,
            actions: [
              const PostBackButton(),
              const Spacer(),
              PostSourceButton(article: article),
              const SizedBox(width: 10),
              PostShareButton(article: article),
              const SizedBox(width: 10),
            ],
          ),
          SliverToBoxAdapter(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 0, 20, 30),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Padding(
                    padding: const EdgeInsets.only(top: 20, bottom: 10),
                    child: Row(
                      children: <Widget>[
                        PostCategory(article: article),
                        const Spacer(),
                        LikeButton(article: article),
                        BookmarkButton(article: article),
                      ],
                    ),
                  ),
                  DateAndReadingTime(article: article),
                  Text(
                    AppService.getNormalText(article.title),
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold, wordSpacing: 1.5, letterSpacing: -0.3),
                  ),
                  Divider(
                    color: Theme.of(context).primaryColor,
                    endIndent: 280,
                    thickness: 2,
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                    child: Row(
                      children: [
                        ViewsCount(article: article),
                        const SizedBox(width: 20),
                        LikesCount(article: article),
                      ],
                    ),
                  ),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      AuthorInfo(article: article),
                      CommentsButton(article: article, ref: ref),
                    ],
                  ),
                  ArticleSummary(article: article),
                  HtmlBody(description: article.description),
                  RelatedArticles(article: article),
                ],
              ),
            ),
          )
        ],
      ),
    );
  }
}

class _AudioWidget extends StatelessWidget {
  const _AudioWidget({required this.article, this.heroTag});
  final Article article;
  final Object? heroTag;

  @override
  Widget build(BuildContext context) {
    return Stack(
      alignment: Alignment.bottomCenter,
      children: [
        HeroMode(
          enabled: heroTag != null,
          child: Hero(
            tag: heroTag ?? '',
            child: CustomCacheImageWithDarkFilterFull(imageUrl: article.thumbnailUrl.toString(), radius: 0),
          ),
        ),
        Positioned(bottom: 20, left: 0, right: 0, child: AudioPlayerWidget(audioUrl: article.audioUrl.toString())),
      ],
    );
  }
}
