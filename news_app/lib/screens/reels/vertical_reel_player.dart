import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:video_player/video_player.dart';
import '../../models/article.dart';
import '../../services/api_service.dart';
import '../../utils/next_screen.dart';
import '../article_details/like_button.dart';
import '../article_details/post_share_button.dart';
import '../article_details/bookmark_button.dart';

// Provider to fetch all reels
final allReelsProvider = FutureProvider<List<Article>>((ref) async {
  return await ApiService().getReelArticles();
});

class VerticalReelPlayer extends ConsumerStatefulWidget {
  final Article? initialArticle;
  final int? initialIndex;
  final bool showBackButton;

  const VerticalReelPlayer({
    super.key,
    this.initialArticle,
    this.initialIndex,
    this.showBackButton = true,
  });

  @override
  ConsumerState<VerticalReelPlayer> createState() => _VerticalReelPlayerState();
}

class _VerticalReelPlayerState extends ConsumerState<VerticalReelPlayer> {
  late PageController _pageController;
  int _currentIndex = 0;
  List<VideoPlayerController?> _controllers = [];
  List<Article> _reels = [];

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex ?? 0;
    _pageController = PageController(initialPage: _currentIndex);
  }

  @override
  void dispose() {
    _pageController.dispose();
    for (var controller in _controllers) {
      controller?.dispose();
    }
    super.dispose();
  }

  void _initializeControllers(List<Article> reels) {
    if (_controllers.isNotEmpty) return;
    
    _reels = reels;
    _controllers = List.generate(reels.length, (index) => null);
    
    // Initialize first video
    if (_currentIndex < reels.length) {
      _initializeVideoAt(_currentIndex);
    }
  }

  void _initializeVideoAt(int index) {
    if (index < 0 || index >= _reels.length) return;
    if (_controllers[index] != null) return;
    
    final article = _reels[index];
    if (article.videoUrl != null && article.videoUrl!.isNotEmpty) {
      debugPrint('🎬 Initializing video at index $index');
      debugPrint('📹 Video URL: ${article.videoUrl}');
      
      try {
        final uri = Uri.parse(article.videoUrl!);
        debugPrint('✅ Parsed URI: $uri');
        
        _controllers[index] = VideoPlayerController.networkUrl(uri)
          ..initialize().then((_) {
            debugPrint('✅ Video initialized at index $index');
            if (mounted && _currentIndex == index) {
              _controllers[index]?.play();
              _controllers[index]?.setLooping(true);
              setState(() {});
            }
          }).catchError((error) {
            debugPrint('❌ Video initialization error at index $index: $error');
          });
      } catch (e) {
        debugPrint('❌ Error parsing video URL at index $index: $e');
        debugPrint('❌ URL: ${article.videoUrl}');
      }
    } else {
      debugPrint('⚠️ No video URL for article at index $index');
    }
  }

  void _onPageChanged(int index) {
    // Pause previous video
    if (_currentIndex < _controllers.length && _controllers[_currentIndex] != null) {
      _controllers[_currentIndex]?.pause();
    }
    
    setState(() {
      _currentIndex = index;
    });
    
    // Initialize and play current video
    _initializeVideoAt(index);
    if (_controllers[index] != null && _controllers[index]!.value.isInitialized) {
      _controllers[index]?.play();
    }
    
    // Preload next video
    if (index + 1 < _reels.length) {
      _initializeVideoAt(index + 1);
    }
  }

  void _togglePlayPause() {
    final controller = _controllers[_currentIndex];
    if (controller != null && controller.value.isInitialized) {
      setState(() {
        if (controller.value.isPlaying) {
          controller.pause();
        } else {
          controller.play();
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final reelsAsync = ref.watch(allReelsProvider);

    return Scaffold(
      backgroundColor: Colors.black,
      body: reelsAsync.when(
        data: (reels) {
          if (reels.isEmpty) {
            return const Center(
              child: Text(
                'No reels available',
                style: TextStyle(color: Colors.white),
              ),
            );
          }

          _initializeControllers(reels);

          return PageView.builder(
            controller: _pageController,
            scrollDirection: Axis.vertical,
            itemCount: reels.length,
            onPageChanged: _onPageChanged,
            itemBuilder: (context, index) {
              return _ReelItem(
                article: reels[index],
                controller: _controllers[index],
                isActive: index == _currentIndex,
                onTogglePlay: _togglePlayPause,
                showBackButton: widget.showBackButton,
              );
            },
          );
        },
        loading: () => const Center(
          child: CircularProgressIndicator(color: Colors.white),
        ),
        error: (error, stack) => Center(
          child: Text(
            'Failed to load reels',
            style: TextStyle(color: Colors.white),
          ),
        ),
      ),
    );
  }
}

class _ReelItem extends ConsumerStatefulWidget {
  final Article article;
  final VideoPlayerController? controller;
  final bool isActive;
  final VoidCallback onTogglePlay;
  final bool showBackButton;

  const _ReelItem({
    required this.article,
    required this.controller,
    required this.isActive,
    required this.onTogglePlay,
    required this.showBackButton,
  });

  @override
  ConsumerState<_ReelItem> createState() => _ReelItemState();
}

class _ReelItemState extends ConsumerState<_ReelItem> {
  bool _showControls = true;

  void _toggleControls() {
    setState(() => _showControls = !_showControls);
    if (_showControls && widget.controller?.value.isPlaying == true) {
      Future.delayed(const Duration(seconds: 3), () {
        if (mounted && widget.controller?.value.isPlaying == true) {
          setState(() => _showControls = false);
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final isPlaying = widget.controller?.value.isPlaying ?? false;

    return GestureDetector(
      onTap: () {
        widget.onTogglePlay();
        _toggleControls();
      },
      child: Stack(
        children: [
          // Video Player - Full Screen
          Positioned.fill(
            child: Container(
              color: Colors.black,
              child: widget.controller != null && widget.controller!.value.isInitialized
                  ? Center(
                      child: AspectRatio(
                        aspectRatio: widget.controller!.value.aspectRatio,
                        child: VideoPlayer(widget.controller!),
                      ),
                    )
                  : const Center(
                      child: CircularProgressIndicator(color: Colors.white),
                    ),
            ),
          ),

          // Play/Pause Button Overlay
          if (_showControls && !isPlaying)
            Center(
              child: IgnorePointer(
                child: Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.black.withOpacity(0.5),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.play_arrow,
                    size: 60,
                    color: Colors.white,
                  ),
                ),
              ),
            ),

          // Right Sidebar with Controls
          Positioned(
            right: 0,
            top: 0,
            bottom: 0,
            child: Container(
              width: 80,
              padding: const EdgeInsets.symmetric(vertical: 20),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  // Thumbnail with Play Icon
                  InkWell(
                    onTap: () {
                      widget.onTogglePlay();
                    },
                    child: Stack(
                      alignment: Alignment.center,
                      children: [
                        Container(
                          width: 60,
                          height: 60,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: Colors.white, width: 2),
                            image: widget.article.thumbnailUrl != null
                                ? DecorationImage(
                                    image: NetworkImage(widget.article.thumbnailUrl!),
                                    fit: BoxFit.cover,
                                  )
                                : null,
                          ),
                          child: widget.article.thumbnailUrl == null
                              ? const Icon(Icons.video_library, color: Colors.white)
                              : null,
                        ),
                        Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.6),
                            shape: BoxShape.circle,
                          ),
                          child: Icon(
                            isPlaying ? Icons.pause : Icons.play_arrow,
                            color: Colors.white,
                            size: 20,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 25),

                  // Read Article Button (if has article content)
                  if (widget.article.hasArticle == true)
                    InkWell(
                      onTap: () {
                        widget.controller?.pause();
                        NextScreen().handlePostNavigation(context, widget.article, null, false, ref);
                      },
                      child: Column(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(
                              color: Colors.red,
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.red.withOpacity(0.5),
                                  blurRadius: 8,
                                  spreadRadius: 2,
                                ),
                              ],
                            ),
                            child: const Icon(
                              Icons.article_outlined,
                              color: Colors.white,
                              size: 30,
                            ),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Article',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              shadows: [
                                Shadow(
                                  color: Colors.black54,
                                  offset: Offset(0, 1),
                                  blurRadius: 2,
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  if (widget.article.hasArticle == true) const SizedBox(height: 25),

                  // Like Button
                  Column(
                    children: [
                      LikeButton(
                        article: widget.article,
                        iconColor: Colors.white,
                        iconSize: 32,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${widget.article.likes ?? 0}',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 25),

                  // Share Button
                  Column(
                    children: [
                      PostShareButton(
                        article: widget.article,
                        iconColor: Colors.white,
                        iconSize: 32,
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Share',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 25),

                  // Bookmark Button
                  Column(
                    children: [
                      BookmarkButton(
                        article: widget.article,
                        iconColor: Colors.white,
                        iconSize: 32,
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Save',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),

          // Top Bar with Back Button (only show if showBackButton is true)
          if (widget.showBackButton)
            Positioned(
            top: 0,
            left: 0,
            right: 80, // Don't overlap with sidebar
            child: SafeArea(
              child: Container(
                padding: const EdgeInsets.all(15),
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [
                      Colors.black.withOpacity(0.7),
                      Colors.transparent,
                    ],
                  ),
                ),
                child: Row(
                  children: [
                    Material(
                      color: Colors.transparent,
                      child: IconButton(
                        icon: const Icon(Icons.arrow_back, color: Colors.white, size: 28),
                        onPressed: () => Navigator.of(context).pop(),
                      ),
                    ),
                    const Spacer(),
                    const Text(
                      'Reels',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const Spacer(),
                    const SizedBox(width: 48), // Balance back button
                  ],
                ),
              ),
            ),
          ),

          // Bottom Info Section
          Positioned(
            left: 16,
            right: 100,
            bottom: 20,
            child: IgnorePointer(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    widget.article.title,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 8),
                  if (widget.article.description.isNotEmpty)
                    Text(
                      widget.article.description,
                      style: const TextStyle(
                        color: Colors.white70,
                        fontSize: 14,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.remove_red_eye, color: Colors.white70, size: 16),
                      const SizedBox(width: 4),
                      Text(
                        '${widget.article.views ?? 0} views',
                        style: const TextStyle(
                          color: Colors.white70,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
