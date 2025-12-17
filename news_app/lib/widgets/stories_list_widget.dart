import 'package:flutter/material.dart';
import '../models/mobile_story.dart';
import '../services/mobile_stories_service.dart';
import 'stories_player.dart';

class StoriesListWidget extends StatefulWidget {
  const StoriesListWidget({Key? key}) : super(key: key);

  @override
  State<StoriesListWidget> createState() => _StoriesListWidgetState();
}

class _StoriesListWidgetState extends State<StoriesListWidget> {
  final MobileStoriesService _storiesService = MobileStoriesService();
  List<MobileStory> _stories = [];
  Map<String, List<MobileStory>> _groupedStories = {};
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadStories();
  }

  Future<void> _loadStories() async {
    setState(() => _isLoading = true);
    try {
      final stories = await _storiesService.getActiveStories();
      setState(() {
        _stories = stories;
        _groupedStories = _storiesService.groupStoriesByCategory(stories);
        _isLoading = false;
      });
    } catch (e) {
      print('Error loading stories: $e');
      setState(() => _isLoading = false);
    }
  }

  void _openStories(String categoryName, List<MobileStory> stories, int initialIndex) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => StoriesPlayer(
          stories: stories,
          initialIndex: initialIndex,
          categoryName: categoryName,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const SizedBox(
        height: 100,
        child: Center(child: CircularProgressIndicator()),
      );
    }

    if (_stories.isEmpty) {
      return const SizedBox.shrink();
    }

    return Container(
      height: 110,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: _groupedStories.keys.length,
        padding: const EdgeInsets.symmetric(horizontal: 8),
        itemBuilder: (context, index) {
          final categoryName = _groupedStories.keys.elementAt(index);
          final categoryStories = _groupedStories[categoryName]!;
          final firstStory = categoryStories.first;

          return GestureDetector(
            onTap: () => _openStories(categoryName, categoryStories, 0),
            child: Container(
              width: 80,
              margin: const EdgeInsets.symmetric(horizontal: 4),
              child: Column(
                children: [
                  // Story Ring
                  Stack(
                    alignment: Alignment.center,
                    children: [
                      // Gradient Ring
                      Container(
                        width: 70,
                        height: 70,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          gradient: const LinearGradient(
                            colors: [
                              Color(0xFFFF6B6B),
                              Color(0xFFFFD93D),
                              Color(0xFF6BCF7F),
                              Color(0xFF4D96FF),
                            ],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                        ),
                      ),
                      // White Border
                      Container(
                        width: 66,
                        height: 66,
                        decoration: const BoxDecoration(
                          shape: BoxShape.circle,
                          color: Colors.white,
                        ),
                      ),
                      // Story Image
                      Container(
                        width: 62,
                        height: 62,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          image: DecorationImage(
                            image: NetworkImage(firstStory.imageUrl),
                            fit: BoxFit.cover,
                          ),
                        ),
                      ),
                      // Story Count Badge
                      if (categoryStories.length > 1)
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: Theme.of(context).primaryColor,
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: Colors.white, width: 2),
                            ),
                            child: Text(
                              '${categoryStories.length}',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  // Category Name
                  Text(
                    categoryName,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: Theme.of(context).textTheme.bodyMedium?.color,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
