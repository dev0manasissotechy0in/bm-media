import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:news_app/providers/article_details_provider.dart';
import 'package:news_app/screens/article_details/article_summary.dart';
import 'package:news_app/screens/article_details/live_timeline.dart';
import 'package:news_app/screens/article_details/author_info.dart';
import 'package:news_app/screens/article_details/bookmark_button.dart';
import 'package:news_app/screens/article_details/like_button.dart';
import 'package:news_app/screens/article_details/likes_count.dart';
import 'package:news_app/screens/article_details/post_back_button.dart';
import 'package:news_app/screens/article_details/post_share_button.dart';
import 'package:news_app/screens/article_details/post_source_button.dart';
import 'package:news_app/screens/article_details/related_articles.dart';
import 'package:news_app/screens/article_details/tts_button.dart';
import 'package:news_app/screens/article_details/play_reel_button.dart';
import '../../../ads/ad_manager.dart';
import '../../../ads/banner_ad.dart';
import '../../../components/html_body.dart';
import '../../../services/app_service.dart';
import '../../../utils/custom_cached_image.dart';
import '../article_tags.dart';
import '../comments_button.dart';
import '../date_and_reading_time.dart';
import '../views_count.dart';

class ArticleDetailsView2 extends ConsumerWidget {
  const ArticleDetailsView2({super.key, required this.article, this.heroTag});

  final Article article;
  final Object? heroTag;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    AdManager.initInterstitailAds(ref);
    debugPrint('📄 LAYOUT: DETAILS VIEW 2 (Compact) | Article: ${article.title}');
    
    // Fetch full article details with live updates
    final articleDetailsAsync = ref.watch(articleDetailsProvider(article.id));
    
    return articleDetailsAsync.when(
      data: (fullArticle) {
        // Use full article data if available, otherwise use the passed article
        final displayArticle = fullArticle ?? article;
        
        return _buildScaffold(context, ref, displayArticle);
      },
      loading: () => _buildScaffold(context, ref, article, isLoading: true),
      error: (error, stack) {
        debugPrint('Error loading article details: $error');
        return _buildScaffold(context, ref, article);
      },
    );
  }
  
  Widget _buildScaffold(BuildContext context, WidgetRef ref, Article displayArticle, {bool isLoading = false}) {
    return Scaffold(
      resizeToAvoidBottomInset: false,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: SafeArea(
        bottom: true,
        top: false,
        maintainBottomViewPadding: true,
        child: CustomScrollView(
          slivers: [
            SliverAppBar(
              pinned: false,
              floating: true,
              elevation: 0,
              actions: [
                const PostBackButton(),
                const Spacer(),
                LikeButton(article: displayArticle),
                BookmarkButton(article: displayArticle),
                PostSourceButton(article: displayArticle, iconSize: 25, hasCircle: false),
                PostShareButton(article: displayArticle, hasCircle: false, iconSize: 25),
                const SizedBox(width: 10),
              ],
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 30),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Padding(
                          padding: const EdgeInsets.only(bottom: 10),
                          child: Text(
                            displayArticle.category?.name.toUpperCase() ?? '',
                            style: Theme.of(context).textTheme.titleSmall,
                          ),
                        ),
                        const SizedBox(width: 10),
                        DateAndReadingTime(article: displayArticle),
                      ],
                    ),
                    Text(
                      AppService.getNormalText(displayArticle.title),
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold, wordSpacing: 1.5, letterSpacing: -0.3),
                    ),
                    Padding(
                      padding: const EdgeInsets.only(top: 10, bottom: 20),
                      child: Row(
                        children: [
                          ViewsCount(article: displayArticle),
                          const SizedBox(width: 20),
                          LikesCount(article: displayArticle),
                        ],
                      ),
                    ),
                    SizedBox(
                      height: 230,
                      child: Stack(
                        children: [
                          HeroMode(
                            enabled: heroTag != null,
                            child: Hero(
                              tag: heroTag ?? '',
                              child: CustomCacheImage(imageUrl: displayArticle.thumbnailUrl, radius: 5),
                            ),
                          ),
                          PlayReelButton(article: displayArticle),
                        ],
                      ),
                    ),
                    const SizedBox(height: 15),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        AuthorInfo(article: displayArticle),
                        CommentsButton(article: displayArticle, ref: ref),
                      ],
                    ),
                    const SizedBox(height: 15),
                    TtsButton(article: displayArticle),
                    const SizedBox(height: 10),
                    ArticleSummary(article: displayArticle),
                    LiveTimeline(article: displayArticle),
                    HtmlBody(description: displayArticle.content ?? displayArticle.description),
                    const SizedBox(height: 20),
                    ArticleTags(article: displayArticle),
                    RelatedArticles(article: displayArticle),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
