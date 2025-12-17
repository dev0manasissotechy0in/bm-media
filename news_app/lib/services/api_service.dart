import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../models/article.dart';
import '../models/category.dart';
import '../models/comment.dart';
import '../models/tag.dart';
import '../models/user_model.dart';
import '../models/podcast.dart';

class ApiService {
  // Update this to your actual website URL
  // For Android Emulator, use 10.0.2.2 to access host machine
  // For Real Device, use your computer's local IP (192.168.1.x)
  // For Web/Desktop/iOS Simulator, use localhost
  // static const String baseUrl = 'http://10.0.2.2'; // Android Emulator (DEFAULT FOR DEVELOPMENT)
  static const String baseUrl = 'http://192.168.1.3'; // Real Device on Wi-Fi - ACTIVE
  // static const String baseUrl = 'http://localhost'; // Web/Desktop/iOS Simulator
  // static const String baseUrl = 'https://brackoddmedia.com'; // Production domain
  static const String apiUrl = '$baseUrl/api';
  
  // Headers for API requests
  static Map<String, String> get headers => {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  // Backend now returns complete URLs, but this handles edge cases
  static String? fixImageUrl(String? url) {
    if (url == null || url.isEmpty) return null;
    
    // Backend should now return complete URLs like https://brackoddmedia.com/uploads/...
    // This is just a fallback for any edge cases
    if (url.startsWith('http://') || url.startsWith('https://')) {
      return url;
    }
    
    // If for some reason we get a relative path, prepend domain
    return url.startsWith('/') ? '$baseUrl$url' : '$baseUrl/$url';
  }

  // Get user profile using auth token
  Future<UserModel?> getUserProfile(String token) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/auth/profile.php'),
        headers: {
          ...headers,
          'Authorization': 'Bearer $token',
        },
      );

      debugPrint('Profile API Response Status: ${response.statusCode}');
      debugPrint('Profile API Response: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['user'] != null) {
          return UserModel.fromJson(data['user']);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error fetching user profile: $e');
      return null;
    }
  }

  // Get all published articles
  Future<List<Article>> getAllArticles() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/all.php'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching articles: $e');
      return [];
    }
  }

  // Get banner articles (Live + Featured for Explore tab)
  Future<List<Article>> getBannerArticles() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/banner.php?limit=10'),
        headers: headers,
      );

      debugPrint('Banner Articles Response Status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('Banner Articles: ${data['count']} articles');
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching banner articles: $e');
      return [];
    }
  }

  // Get featured articles (kept for backward compatibility)
  Future<List<Article>> getFeaturedArticles() async {
    return getBannerArticles();
  }

  // Get breaking news articles
  Future<List<Article>> getBreakingNews(int limit) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/breaking.php?limit=$limit'),
        headers: headers,
      );

      debugPrint('Breaking News Response Status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('Breaking News: ${data['count']} articles');
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching breaking news: $e');
      return [];
    }
  }

  // Get popular articles (kept for backward compatibility, now uses views_count)
  Future<List<Article>> getPopularArticles(int limit) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/popular.php?limit=$limit'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching popular articles: $e');
      return [];
    }
  }

  // Get reel articles
  Future<List<Article>> getReelArticles({int limit = 20}) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/reels.php?limit=$limit'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching reel articles: $e');
      return [];
    }
  }

  // Get video articles
  Future<List<Article>> getVideoArticles({int limit = 20, int page = 1}) async {
    try {
      debugPrint('🎬 Fetching video articles from: $apiUrl/articles/videos.php?limit=$limit&page=$page');
      final response = await http.get(
        Uri.parse('$apiUrl/articles/videos.php?limit=$limit&page=$page'),
        headers: headers,
      );

      debugPrint('🎬 Video API Response Status: ${response.statusCode}');
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('🎬 Video API Success: ${data['success']}, Count: ${data['count']}');
        if (data['success'] == true) {
          final articles = (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
          debugPrint('🎬 Parsed ${articles.length} video articles');
          return articles;
        }
      }
      debugPrint('🎬 Video API returned empty - Status: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('❌ Error fetching video articles: $e');
      return [];
    }
  }

  // Get podcast articles
  Future<List<Article>> getPodcastArticles({int limit = 20, int page = 1}) async {
    try {
      debugPrint('🎙️ Fetching podcast articles from: $apiUrl/articles/podcasts.php?limit=$limit&page=$page');
      final response = await http.get(
        Uri.parse('$apiUrl/articles/podcasts.php?limit=$limit&page=$page'),
        headers: headers,
      );

      debugPrint('🎙️ Podcast API Response Status: ${response.statusCode}');
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('🎙️ Podcast API Success: ${data['success']}, Count: ${data['count']}');
        if (data['success'] == true) {
          final articles = (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
          debugPrint('🎙️ Parsed ${articles.length} podcast articles');
          return articles;
        }
      }
      debugPrint('🎙️ Podcast API returned empty - Status: ${response.statusCode}');
      return [];
    } catch (e) {
      debugPrint('❌ Error fetching podcast articles: $e');
      return [];
    }
  }

  // Get related articles by category
  Future<List<Article>> getRelatedArticlesByCategory(Article article, int limit) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/related.php?category_id=${article.category?.id}&exclude_id=${article.id}&limit=$limit'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching related articles: $e');
      return [];
    }
  }

  // Get articles by category (including subcategories)
  Future<List<Article>> getArticlesByCategory(String categoryId, {int limit = 20, int offset = 0}) async {
    try {
      final url = '$apiUrl/articles/by-category-with-subcategories.php?category_id=$categoryId&limit=$limit&offset=$offset';
      print('API Request: $url');
      
      final response = await http.get(
        Uri.parse(url),
        headers: headers,
      );

      print('API Response Status: ${response.statusCode}');
      print('API Response Body: ${response.body.substring(0, response.body.length > 500 ? 500 : response.body.length)}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          final articles = (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
          print('Parsed ${articles.length} articles for category $categoryId');
          return articles;
        } else {
          print('API Error: ${data['message']}');
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching articles by category: $e');
      return [];
    }
  }

  // Get articles by tag
  Future<List<Article>> getArticlesByTag(String tagId) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/by-tag.php?tag_id=$tagId'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching articles by tag: $e');
      return [];
    }
  }

  // Get single article by ID
  Future<Article?> getArticleById(String articleId) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/single.php?id=$articleId'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return Article.fromJson(data['article']);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error fetching article: $e');
      return null;
    }
  }

  // Get all categories
  Future<List<Category>> getCategories() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/categories/all.php'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['categories'] as List)
              .map((category) => Category.fromJson(category))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching categories: $e');
      return [];
    }
  }

  // Get all tags
  Future<List<Tag>> getTags() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/tags/all.php'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['tags'] as List)
              .map((tag) => Tag.fromJson(tag))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching tags: $e');
      return [];
    }
  }

  // Increment article views
  Future<void> incrementArticleViews(String articleId) async {
    try {
      await http.post(
        Uri.parse('$apiUrl/articles/increment-views.php'),
        headers: headers,
        body: json.encode({'article_id': articleId}),
      );
    } catch (e) {
      debugPrint('Error incrementing views: $e');
    }
  }

  // Like article
  Future<bool> likeArticle(String articleId, String userId) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/articles/like.php'),
        headers: headers,
        body: json.encode({
          'article_id': articleId,
          'user_id': userId,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      debugPrint('Error liking article: $e');
      return false;
    }
  }

  // Save article
  Future<bool> saveArticle(String articleId, String userId) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/articles/save.php'),
        headers: headers,
        body: json.encode({
          'article_id': articleId,
          'user_id': userId,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      debugPrint('Error saving article: $e');
      return false;
    }
  }

  // Get comments for article
  Future<List<Comment>> getComments(String articleId) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/comments/get.php?article_id=$articleId'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['comments'] as List)
              .map((comment) => Comment.fromJson(comment))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching comments: $e');
      return [];
    }
  }

  // Add comment
  Future<bool> addComment({
    required String articleId,
    required String userId,
    required String userName,
    required String userEmail,
    required String comment,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/comments/add.php'),
        headers: headers,
        body: json.encode({
          'article_id': articleId,
          'user_id': userId,
          'user_name': userName,
          'user_email': userEmail,
          'comment': comment,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      debugPrint('Error adding comment: $e');
      return false;
    }
  }

  // Search articles
  Future<List<Article>> searchArticles(String query) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/articles/search.php?q=${Uri.encodeComponent(query)}'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['articles'] as List)
              .map((article) => Article.fromJson(article))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error searching articles: $e');
      return [];
    }
  }

  // User authentication - Login
  Future<UserModel?> loginUser(String email, String password) async {
    try {
      debugPrint('🔐 Login API Call - Email: $email, Password length: ${password.length}');
      
      final requestBody = json.encode({
        'email': email,
        'password': password,
      });
      
      debugPrint('🔐 Login Request Body: $requestBody');
      
      final response = await http.post(
        Uri.parse('$apiUrl/auth/login.php'),
        headers: headers,
        body: requestBody,
      );

      debugPrint('🔐 Login Response Status: ${response.statusCode}');
      debugPrint('🔐 Login Response Body: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          debugPrint('✅ Login SUCCESS - User: ${data['user']['email']}');
          return UserModel.fromJson(data['user']);
        } else {
          debugPrint('❌ Login FAILED - Message: ${data['message']}');
        }
      } else {
        debugPrint('❌ Login FAILED - Status Code: ${response.statusCode}');
      }
      return null;
    } catch (e) {
      debugPrint('❌ Error logging in: $e');
      return null;
    }
  }

  // User authentication - Register
  Future<UserModel?> registerUser({
    required String name,
    required String email,
    required String password,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/auth/register.php'),
        headers: headers,
        body: json.encode({
          'name': name,
          'email': email,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return UserModel.fromJson(data['user']);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error registering: $e');
      return null;
    }
  }

  // Get app configuration from backend
  Future<Map<String, dynamic>?> getAppConfig() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/settings/app-config.php'),
        headers: headers,
      );

      debugPrint('App Config Response: ${response.statusCode}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('App Config Data: $data');
        if (data['success'] == true) {
          return data;
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error fetching app config: $e');
      return null;
    }
  }

  // Google login
  Future<Map<String, dynamic>?> googleLogin({
    required String idToken,
    required String email,
    required String name,
    String? photoUrl,
    String? uid,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/auth/google-login.php'),
        headers: headers,
        body: json.encode({
          'id_token': idToken,
          'email': email,
          'name': name,
          'photo_url': photoUrl,
          'uid': uid,
        }),
      );

      debugPrint('Google Login Response: ${response.statusCode}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return data;
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error with Google login: $e');
      return null;
    }
  }

  // Facebook login
  Future<Map<String, dynamic>?> facebookLogin({
    required String accessToken,
    required String email,
    required String name,
    String? photoUrl,
    String? uid,
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/auth/facebook-login.php'),
        headers: headers,
        body: json.encode({
          'access_token': accessToken,
          'email': email,
          'name': name,
          'photo_url': photoUrl,
          'uid': uid,
        }),
      );

      debugPrint('Facebook Login Response: ${response.statusCode}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return data;
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error with Facebook login: $e');
      return null;
    }
  }

  // ==================== PODCAST METHODS ====================
  
  // Get all podcasts
  Future<List<Podcast>> getAllPodcasts({int limit = 20, int page = 1}) async {
    try {
      debugPrint('🎙️ Fetching podcasts from: $apiUrl/podcasts/all.php?limit=$limit&page=$page');
      final response = await http.get(
        Uri.parse('$apiUrl/podcasts/all.php?limit=$limit&page=$page'),
        headers: headers,
      );

      debugPrint('🎙️ Podcast API Response Status: ${response.statusCode}');
      if (response.statusCode == 200) {
        // Check if response is valid JSON before decoding
        final body = response.body.trim();
        if (body.isEmpty) {
          debugPrint('❌ Podcast API returned empty response');
          return [];
        }
        
        // Check if response starts with HTML tags (error case)
        if (body.startsWith('<') || body.contains('<br')) {
          debugPrint('❌ Podcast API returned HTML instead of JSON: ${body.substring(0, 100)}...');
          return [];
        }
        
        final data = json.decode(body);
        debugPrint('🎙️ Podcast API Success: ${data['success']}, Count: ${data['count'] ?? 0}');
        if (data['success'] == true) {
          final podcasts = (data['podcasts'] as List)
              .map((podcast) => Podcast.fromJson(podcast))
              .toList();
          debugPrint('🎙️ Parsed ${podcasts.length} podcasts');
          return podcasts;
        }
      } else {
        debugPrint('🎙️ Podcast API failed with status: ${response.statusCode}');
        debugPrint('🎙️ Response body: ${response.body}');
      }
      return [];
    } catch (e) {
      debugPrint('❌ Error fetching podcasts: $e');
      debugPrint('❌ Stack trace: ${StackTrace.current}');
      return [];
    }
  }

  // Get single podcast with episodes
  Future<Map<String, dynamic>> getSinglePodcast(int podcastId) async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/podcasts/single.php?id=$podcastId'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          List<Episode> episodes = [];
          if (data['episodes'] != null) {
            episodes = (data['episodes'] as List)
                .map((episode) => Episode.fromJson(episode))
                .toList();
          }
          
          return {
            'podcast': Podcast.fromJson(data['podcast']),
            'episodes': episodes,
            'user_liked': data['user_liked'] ?? false,
            'user_saved': data['user_saved'] ?? false,
            'user_notification': data['user_notification'] ?? false,
          };
        }
      }
      throw Exception('Failed to load podcast');
    } catch (e) {
      debugPrint('Error fetching single podcast: $e');
      rethrow;
    }
  }

  // Like/Unlike podcast
  Future<Map<String, dynamic>> likePodcast(int podcastId, {int? episodeId}) async {
    try {
      final body = {'podcast_id': podcastId};
      if (episodeId != null) body['episode_id'] = episodeId;
      
      final response = await http.post(
        Uri.parse('$apiUrl/podcasts/like.php'),
        headers: headers,
        body: json.encode(body),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return {
            'liked': data['liked'],
            'likes_count': data['likes_count'],
          };
        }
      }
      throw Exception('Failed to like podcast');
    } catch (e) {
      debugPrint('Error liking podcast: $e');
      rethrow;
    }
  }

  // Save/Unsave podcast
  Future<Map<String, dynamic>> savePodcast(int podcastId) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/podcasts/save.php'),
        headers: headers,
        body: json.encode({'podcast_id': podcastId}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return {
            'saved': data['saved'],
            'saves_count': data['saves_count'],
          };
        }
      }
      throw Exception('Failed to save podcast');
    } catch (e) {
      debugPrint('Error saving podcast: $e');
      rethrow;
    }
  }

  // Get saved podcasts
  Future<List<Podcast>> getSavedPodcasts() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/podcasts/saved.php'),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['podcasts'] as List)
              .map((podcast) => Podcast.fromJson(podcast))
              .toList();
        }
      }
      return [];
    } catch (e) {
      debugPrint('Error fetching saved podcasts: $e');
      return [];
    }
  }

  // Save podcast progress
  Future<void> savePodcastProgress(
    int podcastId,
    int progressSeconds, {
    int? episodeId,
    bool completed = false,
  }) async {
    try {
      final body = {
        'podcast_id': podcastId,
        'progress_seconds': progressSeconds,
        'completed': completed,
      };
      if (episodeId != null) body['episode_id'] = episodeId;
      
      await http.post(
        Uri.parse('$apiUrl/podcasts/progress.php'),
        headers: headers,
        body: json.encode(body),
      );
    } catch (e) {
      debugPrint('Error saving progress: $e');
    }
  }

  // Get podcast progress
  Future<PodcastProgress?> getPodcastProgress(int podcastId, int? episodeId) async {
    try {
      String url = '$apiUrl/podcasts/progress.php?podcast_id=$podcastId';
      if (episodeId != null) url += '&episode_id=$episodeId';
      
      final response = await http.get(
        Uri.parse(url),
        headers: headers,
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true && data['progress'] != null) {
          return PodcastProgress.fromJson(data['progress']);
        }
      }
      return null;
    } catch (e) {
      debugPrint('Error fetching progress: $e');
      return null;
    }
  }

  // Toggle podcast notification
  Future<Map<String, dynamic>> togglePodcastNotification(int podcastId, bool enabled) async {
    try {
      final response = await http.post(
        Uri.parse('$apiUrl/podcasts/notification.php'),
        headers: headers,
        body: json.encode({
          'podcast_id': podcastId,
          'enabled': enabled,
        }),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return {'enabled': data['enabled']};
        }
      }
      throw Exception('Failed to toggle notification');
    } catch (e) {
      debugPrint('Error toggling notification: $e');
      rethrow;
    }
  }

  // Get app settings from database
  Future<Map<String, dynamic>> getAppSettings() async {
    try {
      final response = await http.get(
        Uri.parse('$apiUrl/settings/app.php'),
        headers: headers,
      );

      debugPrint('📱 App Settings API Response: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('📱 App Settings Data: $data');
        
        if (data['success'] == true && data['settings'] != null) {
          return data['settings'] as Map<String, dynamic>;
        }
      }
      
      // Return defaults if API fails
      return {
        'post_details_layout': 'random',
        'category_tile_layout': 'grid',
        'video_tab': true,
        'audio_tab': true,
      };
    } catch (e) {
      debugPrint('❌ Error fetching app settings: $e');
      // Return defaults on error
      return {
        'post_details_layout': 'random',
        'category_tile_layout': 'grid',
        'video_tab': true,
        'audio_tab': true,
      };
    }
  }
}
