import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/screens/article_details/author_info.dart';
import 'package:news_app/screens/article_details/post_share_button.dart';
import '../../../ads/ad_manager.dart';
import '../../../ads/banner_ad.dart';
import '../../../components/html_body.dart';
import '../../../components/video_player_widget.dart';
import '../../../services/app_service.dart';
import '../post_category.dart';
import '../article_summary.dart';
import '../article_tags.dart';
import '../bookmark_button.dart';
import '../comments_button.dart';
import '../date_and_reading_time.dart';
import '../like_button.dart';
import '../likes_count.dart';
import '../related_articles.dart';
import '../views_count.dart';

class VideoDetailsView extends ConsumerWidget {
  const VideoDetailsView({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    AdManager.initInterstitailAds(ref);

    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 0,
        backgroundColor: Platform.isAndroid ? Colors.black : null,
        elevation: 0,
        systemOverlayStyle: const SystemUiOverlayStyle(
          statusBarIconBrightness: Brightness.light,
        ),
      ),
      resizeToAvoidBottomInset: false,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: Column(
        mainAxisAlignment: MainAxisAlignment.start,
        children: [
          _VideoWidget(article: article),
          Expanded(
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
                        PostShareButton(
                          article: article,
                          bgColor: Colors.transparent,
                        )
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
                    padding: const EdgeInsets.only(top: 10),
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
                  HtmlBody(description: article.content ?? article.description),
                  const SizedBox(height: 20),
                  ArticleTags(article: article),
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

class _VideoWidget extends StatelessWidget {
  const _VideoWidget({required this.article});
  final Article article;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      bottom: false,
      top: true,
      child: Stack(
        fit: StackFit.passthrough,
        children: [
          VideoPlayerWidget(
            videoUrl: article.videoUrl.toString(),
            thumbnailUrl: article.thumbnailUrl,
          ),
          Align(
            alignment: Alignment.topLeft,
            child: IconButton(
              icon: const Icon(
                Icons.keyboard_arrow_left,
                color: Colors.white,
                size: 30,
              ),
              onPressed: () => Navigator.pop(context),
            ),
          ),
        ],
      ),
    );
  }
}
