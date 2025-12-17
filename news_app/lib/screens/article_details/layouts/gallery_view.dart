import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:carousel_slider/carousel_slider.dart';
import 'package:dots_indicator/dots_indicator.dart';
import '../../../ads/ad_manager.dart';
import '../../../ads/banner_ad.dart';
import '../../../services/app_service.dart';
import '../../../utils/custom_cached_image.dart';
import '../article_summary.dart';
import '../article_tags.dart';
import '../author_info.dart';
import '../bookmark_button.dart';
import '../comments_button.dart';
import '../date_and_reading_time.dart';
import '../like_button.dart';
import '../likes_count.dart';
import '../post_share_button.dart';
import '../post_source_button.dart';
import '../related_articles.dart';
import '../views_count.dart';

class GalleryView extends ConsumerStatefulWidget {
  const GalleryView({super.key, required this.article, this.heroTag});

  final Article article;
  final Object? heroTag;

  @override
  ConsumerState<GalleryView> createState() => _GalleryViewState();
}

class _GalleryViewState extends ConsumerState<GalleryView> {
  int _currentImageIndex = 0;
  List<String> _galleryImages = [];

  @override
  void initState() {
    super.initState();
    // Parse gallery images from description or use thumbnail as single image
    _parseGalleryImages();
  }

  void _parseGalleryImages() {
    // TODO: In production, you should have a dedicated gallery_images field in Article model
    // For now, we'll use thumbnail as the main image
    _galleryImages = [];
    if (widget.article.thumbnailUrl != null) {
      _galleryImages.add(widget.article.thumbnailUrl!);
    }
    // You can add more images from a JSON field in the article
  }

  @override
  Widget build(BuildContext context) {
    AdManager.initInterstitailAds(ref);
    
    return Scaffold(
      resizeToAvoidBottomInset: false,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: SafeArea(
        bottom: true,
        top: false,
        child: CustomScrollView(
          slivers: [
            // App Bar
            SliverAppBar(
              pinned: true,
              floating: false,
              elevation: 0,
              automaticallyImplyLeading: false,
              actions: [
                CircleAvatar(
                  backgroundColor: Theme.of(context).colorScheme.onSecondary,
                  child: IconButton(
                    icon: Icon(Icons.arrow_back, color: Theme.of(context).colorScheme.primary),
                    onPressed: () => Navigator.pop(context),
                  ),
                ),
                const Spacer(),
                LikeButton(article: widget.article),
                BookmarkButton(article: widget.article),
                PostSourceButton(article: widget.article),
                PostShareButton(article: widget.article),
                const SizedBox(width: 10),
              ],
            ),
            
            SliverToBoxAdapter(
              child: Column(
                children: [
                  // Gallery Carousel
                  if (_galleryImages.isNotEmpty) ...[
                    SizedBox(
                      height: 400,
                      child: Stack(
                        children: [
                          CarouselSlider(
                            items: _galleryImages.map((imageUrl) {
                              return Container(
                                width: MediaQuery.of(context).size.width,
                                margin: const EdgeInsets.symmetric(horizontal: 5),
                                child: CustomCacheImage(
                                  imageUrl: imageUrl,
                                  radius: 0,
                                ),
                              );
                            }).toList(),
                            options: CarouselOptions(
                              height: 400,
                              viewportFraction: 1.0,
                              enlargeCenterPage: false,
                              enableInfiniteScroll: _galleryImages.length > 1,
                              onPageChanged: (index, reason) {
                                setState(() => _currentImageIndex = index);
                              },
                            ),
                          ),
                          // Image counter
                          Positioned(
                            bottom: 15,
                            right: 15,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              decoration: BoxDecoration(
                                color: Colors.black.withValues(alpha: 0.7),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                '${_currentImageIndex + 1}/${_galleryImages.length}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    if (_galleryImages.length > 1)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 10),
                        child: DotsIndicator(
                          dotsCount: _galleryImages.length,
                          position: _currentImageIndex.toDouble(),
                          decorator: DotsDecorator(
                            activeColor: Theme.of(context).primaryColor,
                            color: Colors.grey.shade300,
                            size: const Size.square(8),
                            activeSize: const Size(20, 8),
                            activeShape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(5),
                            ),
                          ),
                        ),
                      ),
                  ],
                  
                  // Content
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 10, 20, 30),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            if (widget.article.category != null)
                              Chip(
                                label: Text(
                                  widget.article.category!.name.toUpperCase(),
                                  style: const TextStyle(fontSize: 11),
                                ),
                                backgroundColor: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                                materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                              ),
                            const Spacer(),
                            ViewsCount(article: widget.article),
                            const SizedBox(width: 15),
                            LikesCount(article: widget.article),
                          ],
                        ),
                        const SizedBox(height: 10),
                        DateAndReadingTime(article: widget.article),
                        Text(
                          AppService.getNormalText(widget.article.title),
                          style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                                fontWeight: FontWeight.bold,
                                wordSpacing: 1.5,
                              ),
                        ),
                        const SizedBox(height: 15),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            AuthorInfo(article: widget.article),
                            CommentsButton(article: widget.article, ref: ref),
                          ],
                        ),
                        const SizedBox(height: 15),
                        ArticleSummary(article: widget.article),
                        if (widget.article.description.isNotEmpty) ...[
                          const SizedBox(height: 10),
                          Text(
                            widget.article.description,
                            style: Theme.of(context).textTheme.bodyLarge,
                          ),
                        ],
                        const SizedBox(height: 20),
                        ArticleTags(article: widget.article),
                        RelatedArticles(article: widget.article),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
