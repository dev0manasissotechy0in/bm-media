import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:carousel_slider/carousel_slider.dart';
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

class GalleryWithTextView extends ConsumerStatefulWidget {
  const GalleryWithTextView({super.key, required this.article, this.heroTag});

  final Article article;
  final Object? heroTag;

  @override
  ConsumerState<GalleryWithTextView> createState() => _GalleryWithTextViewState();
}

class _GalleryWithTextViewState extends ConsumerState<GalleryWithTextView> {
  int _currentIndex = 0;
  final CarouselSliderController _carouselController = CarouselSliderController();
  
  // Sample gallery items with heading and description
  late List<Map<String, String>> _galleryItems;

  @override
  void initState() {
    super.initState();
    _initializeGalleryItems();
  }

  void _initializeGalleryItems() {
    // TODO: In production, fetch this from API/database
    // For now, using sample data
    _galleryItems = [
      {
        'image': widget.article.thumbnailUrl ?? '',
        'heading': widget.article.title,
        'description': widget.article.summary ?? widget.article.description,
      },
      // Add more items from article data if available
    ];
  }

  @override
  Widget build(BuildContext context) {
    AdManager.initInterstitailAds(ref);
    
    return Scaffold(
      resizeToAvoidBottomInset: false,
      backgroundColor: Colors.black,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: SafeArea(
        bottom: true,
        top: false,
        child: Stack(
          children: [
            // Full Screen Carousel with Text Overlay
            CarouselSlider.builder(
              carouselController: _carouselController,
              itemCount: _galleryItems.length,
              options: CarouselOptions(
                height: MediaQuery.of(context).size.height,
                viewportFraction: 1.0,
                enlargeCenterPage: false,
                enableInfiniteScroll: false,
                onPageChanged: (index, reason) {
                  setState(() => _currentIndex = index);
                },
              ),
              itemBuilder: (context, index, realIndex) {
                final item = _galleryItems[index];
                return Stack(
                  fit: StackFit.expand,
                  children: [
                    // Background Image
                    CustomCacheImage(
                      imageUrl: item['image'] ?? '',
                      radius: 0,
                    ),
                    
                    // Dark Gradient Overlay
                    Container(
                      decoration: BoxDecoration(
                        gradient: LinearGradient(
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                          colors: [
                            Colors.transparent,
                            Colors.black.withValues(alpha: 0.7),
                            Colors.black.withValues(alpha: 0.9),
                          ],
                          stops: const [0.3, 0.7, 1.0],
                        ),
                      ),
                    ),
                    
                    // Text Content
                    Positioned(
                      bottom: 120,
                      left: 20,
                      right: 20,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Heading
                          Text(
                            AppService.getNormalText(item['heading'] ?? ''),
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              height: 1.3,
                            ),
                          ),
                          const SizedBox(height: 15),
                          
                          // Description
                          Text(
                            item['description'] ?? '',
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.9),
                              fontSize: 16,
                              height: 1.5,
                            ),
                            maxLines: 4,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    
                    // Slide Indicator
                    if (_galleryItems.length > 1)
                      Positioned(
                        bottom: 80,
                        left: 0,
                        right: 0,
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: List.generate(
                            _galleryItems.length,
                            (index) => Container(
                              width: 30,
                              height: 3,
                              margin: const EdgeInsets.symmetric(horizontal: 3),
                              decoration: BoxDecoration(
                                color: _currentIndex == index
                                    ? Colors.white
                                    : Colors.white.withValues(alpha: 0.3),
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
            
            // Top Bar
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              child: Container(
                padding: const EdgeInsets.all(15),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withValues(alpha: 0.7),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: Row(
                  children: [
                    CircleAvatar(
                      backgroundColor: Colors.black.withValues(alpha: 0.5),
                      child: IconButton(
                        icon: const Icon(Icons.arrow_back, color: Colors.white),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ),
                    const Spacer(),
                    IconButton(
                      icon: const Icon(Icons.info_outline, color: Colors.white),
                      onPressed: () {
                        _showArticleDetails();
                      },
                    ),
                    LikeButton(article: widget.article, iconColor: Colors.white),
                    BookmarkButton(article: widget.article, iconColor: Colors.white),
                    PostSourceButton(article: widget.article, iconColor: Colors.white),
                    PostShareButton(article: widget.article, iconColor: Colors.white),
                  ],
                ),
              ),
            ),
            
            // Navigation Arrows (for larger screens)
            if (_galleryItems.length > 1 && MediaQuery.of(context).size.width > 600) ...[
              Positioned(
                left: 20,
                top: 0,
                bottom: 0,
                child: Center(
                  child: IconButton(
                    icon: const Icon(Icons.arrow_back_ios, color: Colors.white, size: 30),
                    onPressed: () => _carouselController.previousPage(),
                  ),
                ),
              ),
              Positioned(
                right: 20,
                top: 0,
                bottom: 0,
                child: Center(
                  child: IconButton(
                    icon: const Icon(Icons.arrow_forward_ios, color: Colors.white, size: 30),
                    onPressed: () => _carouselController.nextPage(),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  void _showArticleDetails() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        builder: (context, scrollController) => Container(
          decoration: BoxDecoration(
            color: Theme.of(context).scaffoldBackgroundColor,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: SingleChildScrollView(
            controller: scrollController,
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 20),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                if (widget.article.category != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: Chip(
                      label: Text(widget.article.category!.name.toUpperCase()),
                      backgroundColor: Theme.of(context).primaryColor.withValues(alpha: 0.1),
                    ),
                  ),
                Text(
                  AppService.getNormalText(widget.article.title),
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    ViewsCount(article: widget.article),
                    const SizedBox(width: 20),
                    LikesCount(article: widget.article),
                  ],
                ),
                const SizedBox(height: 15),
                DateAndReadingTime(article: widget.article),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    AuthorInfo(article: widget.article),
                    CommentsButton(article: widget.article, ref: ref),
                  ],
                ),
                const SizedBox(height: 15),
                ArticleSummary(article: widget.article),
                const SizedBox(height: 20),
                ArticleTags(article: widget.article),
                RelatedArticles(article: widget.article),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
