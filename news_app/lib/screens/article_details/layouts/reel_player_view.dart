import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/article.dart';
import 'package:video_player/video_player.dart';
import '../../../ads/ad_manager.dart';
import '../../../ads/banner_ad.dart';
import '../../../services/app_service.dart';
import '../article_summary.dart';
import '../article_tags.dart';
import '../author_info.dart';
import '../bookmark_button.dart';
import '../comments_button.dart';
import '../date_and_reading_time.dart';
import '../like_button.dart';
import '../likes_count.dart';
import '../post_share_button.dart';
import '../related_articles.dart';
import '../views_count.dart';

class ReelPlayerView extends ConsumerStatefulWidget {
  const ReelPlayerView({super.key, required this.article, this.heroTag});

  final Article article;
  final Object? heroTag;

  @override
  ConsumerState<ReelPlayerView> createState() => _ReelPlayerViewState();
}

class _ReelPlayerViewState extends ConsumerState<ReelPlayerView> {
  VideoPlayerController? _controller;
  bool _isPlaying = false;
  bool _showControls = true;

  @override
  void initState() {
    super.initState();
    _initializeVideo();
  }

  void _initializeVideo() {
    if (widget.article.videoUrl != null) {
      _controller = VideoPlayerController.networkUrl(Uri.parse(widget.article.videoUrl!))
        ..initialize().then((_) {
          setState(() {});
          _controller?.play();
          _isPlaying = true;
        });
      
      _controller?.addListener(() {
        if (_controller!.value.isPlaying != _isPlaying) {
          setState(() {
            _isPlaying = _controller!.value.isPlaying;
          });
        }
      });
    }
  }

  @override
  void dispose() {
    _controller?.dispose();
    super.dispose();
  }

  void _togglePlayPause() {
    if (_controller != null) {
      if (_controller!.value.isPlaying) {
        _controller!.pause();
      } else {
        _controller!.play();
      }
      setState(() {
        _isPlaying = _controller!.value.isPlaying;
        _showControls = true;
      });
      
      // Hide controls after 3 seconds
      Future.delayed(const Duration(seconds: 3), () {
        if (mounted && _isPlaying) {
          setState(() => _showControls = false);
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    AdManager.initInterstitailAds(ref);
    
    return Scaffold(
      backgroundColor: Colors.black,
      bottomNavigationBar: AdManager.isBannerEnbaled(ref) ? const BannerAdWidget() : null,
      body: SafeArea(
        child: Stack(
          children: [
            // Video Player
            Center(
              child: _controller != null && _controller!.value.isInitialized
                  ? AspectRatio(
                      aspectRatio: _controller!.value.aspectRatio,
                      child: VideoPlayer(_controller!),
                    )
                  : const CircularProgressIndicator(color: Colors.white),
            ),
            
            // Tap to show/hide controls
            GestureDetector(
              onTap: () {
                setState(() => _showControls = !_showControls);
                if (_showControls && _isPlaying) {
                  Future.delayed(const Duration(seconds: 3), () {
                    if (mounted && _isPlaying) {
                      setState(() => _showControls = false);
                    }
                  });
                }
              },
              child: Container(color: Colors.transparent),
            ),
            
            // Play/Pause overlay
            if (_showControls)
              Center(
                child: GestureDetector(
                  onTap: _togglePlayPause,
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.black.withValues(alpha: 0.5),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      _isPlaying ? Icons.pause : Icons.play_arrow,
                      size: 50,
                      color: Colors.white,
                    ),
                  ),
                ),
              ),
            
            // Top Bar
            if (_showControls)
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
                      // Show "Read Article" button if reel has article content
                      if (widget.article.hasArticle == true) ...[
                        Container(
                          margin: const EdgeInsets.only(right: 8),
                          child: ElevatedButton.icon(
                            onPressed: () {
                              Navigator.pushNamed(
                                context,
                                '/article-details',
                                arguments: widget.article,
                              );
                            },
                            icon: const Icon(Icons.article_outlined, size: 18),
                            label: const Text('Read Article'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: Colors.blue,
                              foregroundColor: Colors.white,
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(20),
                              ),
                            ),
                          ),
                        ),
                      ],
                      LikeButton(article: widget.article, iconColor: Colors.white),
                      BookmarkButton(article: widget.article, iconColor: Colors.white),
                      PostShareButton(article: widget.article),
                    ],
                  ),
                ),
              ),
            
            // Bottom Sheet with Details
            DraggableScrollableSheet(
              initialChildSize: 0.15,
              minChildSize: 0.15,
              maxChildSize: 0.8,
              builder: (context, scrollController) {
                return Container(
                  decoration: BoxDecoration(
                    color: Theme.of(context).scaffoldBackgroundColor,
                    borderRadius: const BorderRadius.vertical(top: Radius.circular(20)),
                  ),
                  child: SingleChildScrollView(
                    controller: scrollController,
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Center(
                            child: Container(
                              width: 40,
                              height: 4,
                              margin: const EdgeInsets.only(bottom: 15),
                              decoration: BoxDecoration(
                                color: Colors.grey.shade300,
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                          ),
                          Text(
                            AppService.getNormalText(widget.article.title),
                            style: Theme.of(context).textTheme.titleLarge?.copyWith(
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
                          if (widget.article.description.isNotEmpty) ...[
                            const SizedBox(height: 10),
                            Text(
                              widget.article.description,
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                          const SizedBox(height: 20),
                          ArticleTags(article: widget.article),
                          RelatedArticles(article: widget.article),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
