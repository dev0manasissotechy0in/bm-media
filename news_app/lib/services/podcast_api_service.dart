import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/podcast_model.dart';

class PodcastApiService {
  static const String baseUrl = 'http://localhost/api/podcasts';

  /// Get guest token (for non-logged-in users)
  static Future<String> getGuestToken() async {
    final prefs = await SharedPreferences.getInstance();
    String? token = prefs.getString('guest_token');
    
    if (token == null) {
      // Generate new token
      token = DateTime.now().millisecondsSinceEpoch.toString() + 
              '_' + 
              (DateTime.now().microsecondsSinceEpoch % 100000).toString();
      await prefs.setString('guest_token', token);
    }
    
    return token;
  }

  /// Fetch list of podcasts
  static Future<List<Podcast>> fetchPodcasts({
    int? categoryId,
    bool featured = false,
    int limit = 20,
    int offset = 0,
    String search = '',
  }) async {
    try {
      final queryParams = {
        'limit': limit.toString(),
        'offset': offset.toString(),
      };
      
      if (categoryId != null && categoryId > 0) {
        queryParams['category_id'] = categoryId.toString();
      }
      
      if (featured) {
        queryParams['featured'] = '1';
      }
      
      if (search.isNotEmpty) {
        queryParams['search'] = search;
      }

      final uri = Uri.parse('$baseUrl/list.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        if (jsonData['success'] == true) {
          final List<dynamic> podcastsJson = jsonData['podcasts'];
          return podcastsJson.map((json) => Podcast.fromJson(json)).toList();
        }
      }
      return [];
    } catch (e) {
      print('Error fetching podcasts: $e');
      return [];
    }
  }

  /// Fetch podcast detail with progress
  static Future<Map<String, dynamic>?> fetchPodcastDetail({
    required int podcastId,
    int? userId,
    String? guestToken,
  }) async {
    try {
      final queryParams = {'id': podcastId.toString()};
      
      if (userId != null) {
        queryParams['user_id'] = userId.toString();
      } else if (guestToken != null) {
        queryParams['guest_token'] = guestToken;
      }

      final uri = Uri.parse('$baseUrl/detail.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        if (jsonData['success'] == true) {
          return {
            'podcast': Podcast.fromJson(jsonData['podcast']),
            'progress': jsonData['progress'] != null 
                ? PodcastProgress.fromJson(jsonData['progress']) 
                : null,
            'is_liked': jsonData['is_liked'] ?? false,
          };
        }
      }
      return null;
    } catch (e) {
      print('Error fetching podcast detail: $e');
      return null;
    }
  }

  /// Save playback progress
  static Future<bool> saveProgress({
    required int podcastId,
    int? userId,
    String? guestToken,
    required int currentTime,
    required int duration,
    required double playbackSpeed,
    required double volume,
  }) async {
    try {
      final data = {
        'podcast_id': podcastId,
        'user_id': userId,
        'guest_token': guestToken,
        'current_time': currentTime,
        'duration': duration,
        'playback_speed': playbackSpeed,
        'volume': volume,
      };

      final response = await http.post(
        Uri.parse('$baseUrl/save-progress.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(data),
      );

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return jsonData['success'] == true;
      }
      return false;
    } catch (e) {
      print('Error saving progress: $e');
      return false;
    }
  }

  /// Toggle like on podcast
  static Future<Map<String, dynamic>?> toggleLike({
    required int podcastId,
    int? userId,
    String? guestToken,
  }) async {
    try {
      final data = {
        'podcast_id': podcastId,
        'user_id': userId,
        'guest_token': guestToken,
      };

      final response = await http.post(
        Uri.parse('$baseUrl/toggle-like.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(data),
      );

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        if (jsonData['success'] == true) {
          return {
            'liked': jsonData['liked'],
            'like_count': jsonData['like_count'],
          };
        }
      }
      return null;
    } catch (e) {
      print('Error toggling like: $e');
      return null;
    }
  }

  /// Fetch podcast categories
  static Future<List<PodcastCategory>> fetchCategories() async {
    try {
      final response = await http.get(Uri.parse('$baseUrl/categories.php'));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        if (jsonData['success'] == true) {
          final List<dynamic> categoriesJson = jsonData['categories'];
          return categoriesJson.map((json) => PodcastCategory.fromJson(json)).toList();
        }
      }
      return [];
    } catch (e) {
      print('Error fetching categories: $e');
      return [];
    }
  }

  /// Fetch listening history
  static Future<List<Map<String, dynamic>>> fetchHistory({
    int? userId,
    String? guestToken,
    int limit = 10,
  }) async {
    try {
      final queryParams = {'limit': limit.toString()};
      
      if (userId != null) {
        queryParams['user_id'] = userId.toString();
      } else if (guestToken != null) {
        queryParams['guest_token'] = guestToken;
      }

      final uri = Uri.parse('$baseUrl/history.php').replace(queryParameters: queryParams);
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        if (jsonData['success'] == true) {
          return List<Map<String, dynamic>>.from(jsonData['history']);
        }
      }
      return [];
    } catch (e) {
      print('Error fetching history: $e');
      return [];
    }
  }

  /// Download podcast
  static String getDownloadUrl(int podcastId) {
    return '$baseUrl/download.php?id=$podcastId';
  }
}
