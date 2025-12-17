import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

class StoryProvider extends StateNotifier<Set<String>> {
  StoryProvider() : super({}) {
    _loadViewedStories();
  }

  static const String _viewedStoriesKey = 'viewed_stories';
  static const String _lastCheckKey = 'last_story_check';

  Future<void> _loadViewedStories() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final viewedStories = prefs.getStringList(_viewedStoriesKey) ?? [];
      final lastCheck = prefs.getInt(_lastCheckKey) ?? 0;
      
      // Clear viewed stories if last check was more than 24 hours ago
      final now = DateTime.now().millisecondsSinceEpoch;
      if (now - lastCheck > 86400000) { // 24 hours in milliseconds
        await prefs.remove(_viewedStoriesKey);
        state = {};
      } else {
        state = Set<String>.from(viewedStories);
      }
    } catch (e) {
      print('Error loading viewed stories: $e');
    }
  }

  Future<void> markStoryAsViewed(String storyId) async {
    state = {...state, storyId};
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setStringList(_viewedStoriesKey, state.toList());
      await prefs.setInt(_lastCheckKey, DateTime.now().millisecondsSinceEpoch);
    } catch (e) {
      print('Error saving viewed story: $e');
    }
  }

  Future<void> markCategoryAsViewed(List<String> storyIds) async {
    state = {...state, ...storyIds};
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setStringList(_viewedStoriesKey, state.toList());
      await prefs.setInt(_lastCheckKey, DateTime.now().millisecondsSinceEpoch);
    } catch (e) {
      print('Error saving viewed stories: $e');
    }
  }

  bool hasViewedStory(String storyId) {
    return state.contains(storyId);
  }

  bool hasUnreadStories(List<String> allStoryIds) {
    return allStoryIds.any((id) => !state.contains(id));
  }

  Future<void> clearViewedStories() async {
    state = {};
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove(_viewedStoriesKey);
    } catch (e) {
      print('Error clearing viewed stories: $e');
    }
  }
}

final storyProvider = StateNotifierProvider<StoryProvider, Set<String>>((ref) {
  return StoryProvider();
});
