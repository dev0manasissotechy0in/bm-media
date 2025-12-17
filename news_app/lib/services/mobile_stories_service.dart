import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/mobile_story.dart';
import 'api_service.dart';

class MobileStoriesService {
  final String baseUrl = '${ApiService.baseUrl}/api/mobile-stories';

  Future<List<MobileStory>> getActiveStories() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/list.php'),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          final List<dynamic> storiesJson = data['stories'] ?? [];
          return storiesJson.map((json) => MobileStory.fromJson(json)).toList();
        }
      }
      return [];
    } catch (e) {
      print('Error fetching mobile stories: $e');
      return [];
    }
  }

  Future<bool> incrementViewCount(String storyId) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/increment-view.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'story_id': storyId}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      print('Error incrementing view count: $e');
      return false;
    }
  }

  // Group stories by category
  Map<String, List<MobileStory>> groupStoriesByCategory(List<MobileStory> stories) {
    final Map<String, List<MobileStory>> grouped = {};
    
    for (var story in stories) {
      final category = story.categoryName ?? 'Other';
      if (!grouped.containsKey(category)) {
        grouped[category] = [];
      }
      grouped[category]!.add(story);
    }
    
    return grouped;
  }
}
