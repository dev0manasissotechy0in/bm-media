import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/models/mobile_story.dart';
import 'package:news_app/services/mobile_stories_service.dart';
import 'package:news_app/widgets/stories_player.dart';
import 'package:news_app/providers/story_provider.dart';

class MobileStories extends ConsumerStatefulWidget {
  const MobileStories({Key? key}) : super(key: key);

  @override
  ConsumerState<MobileStories> createState() => _MobileStoriesState();
}

class _MobileStoriesState extends ConsumerState<MobileStories> {
  final MobileStoriesService _storiesService = MobileStoriesService();
  List<MobileStory> _stories = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadStories();
  }

  void _autoPlayFirstStory() {
    if (_stories.isNotEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _openStories(0);
      });
    }
  }

  Future<void> _loadStories() async {
    setState(() => _isLoading = true);
    try {
      final stories = await _storiesService.getActiveStories();
      setState(() {
        _stories = stories;
        _isLoading = false;
      });
      // Auto-play first story after loading
      _autoPlayFirstStory();
    } catch (e) {
      print('Error loading stories: $e');
      setState(() => _isLoading = false);
    }
  }

  void _openStories(int initialIndex) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => StoriesPlayer(
          stories: _stories,
          initialIndex: initialIndex,
          categoryName: 'Latest Stories',
        ),
      ),
    );
    // Refresh stories after watching to update viewed status
    if (mounted) {
      setState(() {});
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mobile Stories'),
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _stories.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.auto_stories_outlined,
                        size: 64,
                        color: Colors.grey[400],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        'No stories available',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey[600],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Stories expire after 24 hours',
                        style: TextStyle(
                          fontSize: 13,
                          color: Colors.grey[500],
                        ),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadStories,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: const LinearGradient(
                                  colors: [
                                    Color(0xFFFF6B6B),
                                    Color(0xFFFFD93D),
                                    Color(0xFF6BCF7F),
                                    Color(0xFF4D96FF),
                                  ],
                                ),
                              ),
                              child: const Icon(
                                Icons.newspaper,
                                color: Colors.white,
                                size: 20,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Latest Stories',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  Text(
                                    '${_stories.length} ${_stories.length == 1 ? 'story' : 'stories'}',
                                    style: TextStyle(
                                      fontSize: 12,
                                      color: Colors.grey[600],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                      Expanded(
                        child: GridView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 16),
                          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                            crossAxisCount: 3,
                            childAspectRatio: 0.75,
                            crossAxisSpacing: 12,
                            mainAxisSpacing: 12,
                          ),
                          itemCount: _stories.length,
                          itemBuilder: (context, index) {
                            final story = _stories[index];
                            final isViewed = ref.watch(storyProvider).contains(story.id);
                            return GestureDetector(
                              onTap: () => _openStories(index),
                              child: Column(
                                children: [
                                  Stack(
                                    children: [
                                      Container(
                                        width: 90,
                                        height: 90,
                                        padding: const EdgeInsets.all(3),
                                        decoration: BoxDecoration(
                                          borderRadius: BorderRadius.circular(12),
                                          gradient: isViewed
                                              ? null
                                              : const LinearGradient(
                                                  colors: [
                                                    Color(0xFFFF6B6B),
                                                    Color(0xFFFFD93D),
                                                    Color(0xFF6BCF7F),
                                                    Color(0xFF4D96FF),
                                                  ],
                                                ),
                                          color: isViewed ? Colors.grey : null,
                                        ),
                                        child: Container(
                                          decoration: BoxDecoration(
                                            borderRadius: BorderRadius.circular(9),
                                            image: DecorationImage(
                                              image: NetworkImage(story.imageUrl),
                                              fit: BoxFit.cover,
                                            ),
                                          ),
                                        ),
                                      ),
                                      // Video play icon indicator
                                      if (story.isVideo)
                                        Positioned(
                                          top: 4,
                                          right: 4,
                                          child: Container(
                                            padding: const EdgeInsets.all(4),
                                            decoration: BoxDecoration(
                                              color: Colors.black.withOpacity(0.7),
                                              shape: BoxShape.circle,
                                            ),
                                            child: const Icon(
                                              Icons.play_arrow,
                                              color: Colors.white,
                                              size: 16,
                                            ),
                                          ),
                                        ),
                                      Positioned(
                                        bottom: 4,
                                        right: 4,
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: Colors.black.withOpacity(0.6),
                                            borderRadius: BorderRadius.circular(4),
                                          ),
                                          child: Text(
                                            story.uploadTimeText,
                                            style: const TextStyle(
                                              color: Colors.white,
                                              fontSize: 8,
                                              fontWeight: FontWeight.w500,
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Expanded(
                                    child: Text(
                                      story.title,
                                      style: const TextStyle(fontSize: 11),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      textAlign: TextAlign.center,
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
                    ],
                  ),
                ),
    );
  }
}
