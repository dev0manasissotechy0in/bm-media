import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:video_player/video_player.dart';
import 'package:url_launcher/url_launcher_string.dart';
import '../models/mobile_story.dart';
import '../services/mobile_stories_service.dart';
import '../providers/story_provider.dart';
import 'dart:async';

class StoriesPlayer extends ConsumerStatefulWidget {
  final List<MobileStory> stories;
  final int initialIndex;
  final String categoryName;

  const StoriesPlayer({
    Key? key,
    required this.stories,
    this.initialIndex = 0,
    required this.categoryName,
  }) : super(key: key);

  @override
  ConsumerState<StoriesPlayer> createState() => _StoriesPlayerState();
}

class _StoriesPlayerState extends ConsumerState<StoriesPlayer> with SingleTickerProviderStateMixin {
  late int _currentIndex;
  late AnimationController _animationController;
  Timer? _timer;
  final MobileStoriesService _storiesService = MobileStoriesService();
  bool _isPaused = false;
  VideoPlayerController? _videoController;
  bool _isVideoInitialized = false;
  
  // Story display duration (5 seconds per story)
  static const Duration _storyDuration = Duration(seconds: 5);

  @override
  void initState() {
    super.initState();
    _currentIndex = widget.initialIndex;
    _animationController = AnimationController(vsync: this, duration: _storyDuration);
    
    // Increment view count for first story
    _storiesService.incrementViewCount(widget.stories[_currentIndex].id);
    
    _animationController.addStatusListener((status) {
      if (status == AnimationStatus.completed) {
        _nextStory();
      }
    });
    
    _loadStory(_currentIndex);
  }
  
  Future<void> _loadStory(int index) async {
    final story = widget.stories[index];
    
    // Dispose previous video if any
    await _videoController?.dispose();
    _videoController?.dispose();
    _videoController = null;
    setState(() => _isVideoInitialized = false);
    
    if (story.isVideo && story.videoUrl != null) {
      // Initialize video player
      _videoController = VideoPlayerController.networkUrl(Uri.parse(story.videoUrl!));
      
      try {
        await _videoController!.initialize();
        setState(() => _isVideoInitialized = true);
        
        // Set animation duration to video duration
        _animationController.duration = _videoController!.value.duration;
        
        // Play video and start animation
        await _videoController!.play();
        _animationController.forward(from: 0);
        
        // Listen to video completion
        _videoController!.addListener(() {
          if (_videoController!.value.position >= _videoController!.value.duration) {
            _nextStory();
          }
        });
      } catch (e) {
        print('Error loading video: $e');
        // Fallback to default duration if video fails
        _animationController.duration = _storyDuration;
        _animationController.forward(from: 0);
      }
    } else {
      // Image story - use default duration
      _animationController.duration = _storyDuration;
      _animationController.forward(from: 0);
    }
    
    _animationController.forward();
  }

  @override
  void dispose() {
    _animationController.dispose();
    _timer?.cancel();
    super.dispose();
  }

  void _nextStory() {
    // Mark current story as viewed
    ref.read(storyProvider.notifier).markStoryAsViewed(widget.stories[_currentIndex].id);
    
    if (_currentIndex < widget.stories.length - 1) {
      _currentIndex++;
      _storiesService.incrementViewCount(widget.stories[_currentIndex].id);
      _loadStory(_currentIndex);
    } else {
      // Mark last story as viewed and navigate back to home
      ref.read(storyProvider.notifier).markStoryAsViewed(widget.stories[_currentIndex].id);
      Navigator.of(context).popUntil((route) => route.isFirst);
    }
  }

  void _previousStory() {
    if (_currentIndex > 0) {
      setState(() {
        _currentIndex--;
      });
      _animationController.reset();
      _loadStory(_currentIndex);
    }
  }
  void _pauseStory() {
    if (!_isPaused) {
      _animationController.stop();
      _videoController?.pause();
      setState(() => _isPaused = true);
    }
  }

  void _resumeStory() {
    if (_isPaused) {
      _animationController.forward();
      _videoController?.play();
      setState(() => _isPaused = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final story = widget.stories[_currentIndex];
    
    return Scaffold(
      backgroundColor: Colors.black,
      body: GestureDetector(
        onTapDown: (details) {
          final screenWidth = MediaQuery.of(context).size.width;
          final tapPosition = details.globalPosition.dx;
          
          if (tapPosition < screenWidth / 3) {
            // Tap on left third - previous story
            _previousStory();
          } else if (tapPosition > (screenWidth * 2 / 3)) {
            // Tap on right third - next story
            _nextStory();
          } else {
            // Tap on middle - pause/resume
            if (_isPaused) {
              _resumeStory();
            } else {
              _pauseStory();
            }
          }
        },
        onLongPressStart: (_) => _pauseStory(),
        onLongPressEnd: (_) => _resumeStory(),
        onVerticalDragEnd: (details) {
          if (details.primaryVelocity! > 0) {
            // Swipe down - mark as viewed and close stories
            ref.read(storyProvider.notifier).markStoryAsViewed(widget.stories[_currentIndex].id);
            Navigator.of(context).popUntil((route) => route.isFirst);
          }
        },
        child: Stack(
          children: [
            // Story Media (Image or Video)
            Positioned.fill(
              child: story.isVideo
                  ? (_isVideoInitialized && _videoController != null
                      ? FittedBox(
                          fit: BoxFit.contain,
                          child: SizedBox(
                            width: _videoController!.value.size.width,
                            height: _videoController!.value.size.height,
                            child: VideoPlayer(_videoController!),
                          ),
                        )
                      : Container(
                          color: Colors.grey[900],
                          child: const Center(
                            child: CircularProgressIndicator(color: Colors.white),
                          ),
                        ))
                  : CachedNetworkImage(
                      imageUrl: story.imageUrl,
                      fit: BoxFit.contain,
                      placeholder: (context, url) => Container(
                        color: Colors.grey[900],
                        child: const Center(
                          child: CircularProgressIndicator(color: Colors.white),
                        ),
                      ),
                      errorWidget: (context, url, error) => Container(
                        color: Colors.grey[900],
                        child: const Center(
                          child: Icon(Icons.broken_image, color: Colors.white, size: 48),
                        ),
                      ),
                    ),
            ),

            // Gradient overlay at top
            Positioned(
              top: 0,
              left: 0,
              right: 0,
              height: 150,
              child: Container(
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
              ),
            ),

            // Timeline Progress Bars
            Positioned(
              top: 40,
              left: 8,
              right: 8,
              child: _StoryProgressBars(
                storyCount: widget.stories.length,
                currentIndex: _currentIndex,
                animationController: _animationController,
              ),
            ),

            // Story Header
            Positioned(
              top: 60,
              left: 16,
              right: 16,
              child: Row(
                children: [
                  // Category Avatar
                  Container(
                    width: 32,
                    height: 32,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      border: Border.all(color: Colors.white, width: 2),
                      color: Colors.grey[800],
                    ),
                    child: const Icon(Icons.newspaper, color: Colors.white, size: 16),
                  ),
                  const SizedBox(width: 8),
                  // Latest Stories & Time
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Latest Stories',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 14,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Row(
                          children: [
                            Text(
                              story.uploadTimeText,
                              style: TextStyle(
                                color: Colors.white.withOpacity(0.7),
                                fontSize: 11,
                              ),
                            ),
                            Text(
                              ' • ${story.timeRemainingText}',
                              style: TextStyle(
                                color: Colors.white.withOpacity(0.7),
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  // Close Button
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.of(context).popUntil((route) => route.isFirst),
                  ),
                ],
              ),
            ),

            // Story Title and Category at Bottom
            if (story.title.isNotEmpty)
              Positioned(
                bottom: 40,
                left: 16,
                right: 16,
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.black.withOpacity(0.5),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        story.title,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        story.categoryName ?? 'General',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.8),
                          fontSize: 13,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      if (story.description != null && story.description!.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          story.description!,
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.9),
                            fontSize: 13,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                      if (story.link != null && story.link!.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        GestureDetector(
                          onTap: () {
                            _pauseStory();
                            // Open URL in external browser
                            launchUrlString(story.link!);
                            Future.delayed(const Duration(seconds: 1), () {
                              if (mounted) _resumeStory();
                            });
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(Icons.link, size: 16, color: Colors.black87),
                                SizedBox(width: 6),
                                Text(
                                  'Read More',
                                  style: TextStyle(
                                    color: Colors.black87,
                                    fontSize: 13,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),

            // Pause Indicator
            if (_isPaused)
              const Center(
                child: Icon(
                  Icons.pause_circle_filled,
                  color: Colors.white,
                  size: 64,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _StoryProgressBars extends StatelessWidget {
  final int storyCount;
  final int currentIndex;
  final AnimationController animationController;

  const _StoryProgressBars({
    required this.storyCount,
    required this.currentIndex,
    required this.animationController,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(storyCount, (index) {
        return Expanded(
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 2),
            height: 3,
            child: AnimatedBuilder(
              animation: animationController,
              builder: (context, child) {
                double progress;
                if (index < currentIndex) {
                  // Completed stories
                  progress = 1.0;
                } else if (index == currentIndex) {
                  // Current story
                  progress = animationController.value;
                } else {
                  // Upcoming stories
                  progress = 0.0;
                }

                return LinearProgressIndicator(
                  value: progress,
                  backgroundColor: Colors.white.withOpacity(0.3),
                  valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                );
              },
            ),
          ),
        );
      }),
    );
  }
}
