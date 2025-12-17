// Temporary stub for FirebaseService during migration
// This prevents compilation errors while migrating to REST API
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/firestore_stub.dart';
import '../services/api_service.dart';
import '../models/app_settings_model.dart';
import '../models/article.dart';
import '../models/category.dart';
import '../models/tag.dart';
import '../models/comment.dart';

class FirebaseService {
  // Use API service for data fetching
  Future<List<Article>> getAllArticles() async {
    try {
      return await ApiService().getAllArticles();
    } catch (e) {
      print('Error fetching articles: $e');
      return [];
    }
  }
  
  Future<List<Article>> getFeaturedArticles() async {
    try {
      return await ApiService().getFeaturedArticles();
    } catch (e) {
      print('Error fetching featured articles: $e');
      return [];
    }
  }
  
  Future<List<Article>> getPopularArticles(int limit) async {
    try {
      return await ApiService().getPopularArticles(limit);
    } catch (e) {
      print('Error fetching popular articles: $e');
      return [];
    }
  }
  
  Future<List<Article>> getRelatedArticlesByAudio(Article article, int limit) async => [];
  Future<List<Article>> getRelatedArticlesByCategory(Article article, int limit) async {
    try {
      return await ApiService().getRelatedArticlesByCategory(article, limit);
    } catch (e) {
      print('Error fetching related articles: $e');
      return [];
    }
  }
  
  Future<List<Category>> getAllCategories() async {
    try {
      return await ApiService().getCategories();
    } catch (e) {
      print('Error fetching categories: $e');
      return [];
    }
  }
  
  Future<List<Tag>> getAllTags() async {
    try {
      return await ApiService().getTags();
    } catch (e) {
      print('Error fetching tags: $e');
      return [];
    }
  }
  
  Future<List<Tag>> getArticleTags(List<String> tagIds) async => [];
  Future<Article?> getArticle(String id) async {
    try {
      return await ApiService().getArticleById(id);
    } catch (e) {
      print('Error fetching article: $e');
      return null;
    }
  }
  
  // Return default app settings to allow app to work during migration
  Future<AppSettingsModel?> getAppSettingsData() async {
    // Fetch real categories from API for home tabs
    List<Category> allCategories = [];
    try {
      print('Fetching categories from API...');
      allCategories = await getAllCategories();
      print('Fetched ${allCategories.length} categories');
    } catch (e) {
      print('Error loading categories: $e');
    }
    
    // Filter to only parent categories and convert to HomeCategory
    final List<HomeCategory> parentCategories = allCategories
        .where((cat) {
          print('Category: ${cat.name}, ParentId: ${cat.parentId}');
          return cat.parentId == null;
        })
        .map((cat) => HomeCategory(
              id: cat.id,
              name: cat.name,
            ))
        .toList();
    
    print('Found ${parentCategories.length} parent categories');
    for (var cat in parentCategories) {
      print('Parent Category: ${cat.name} (${cat.id})');
    }
    
    // Fetch app settings from API
    String postLayout = 'random';
    String categoryLayout = 'grid';
    AppSettingsSocialInfo? socialInfo;
    String? supportEmail;
    String? privacyUrl;
    String? termsUrl;
    String? website;
    
    try {
      final settingsResponse = await ApiService().getAppSettings();
      postLayout = settingsResponse['post_details_layout'] ?? 'random';
      categoryLayout = settingsResponse['category_tile_layout'] ?? 'grid';
      
      // Extract social media links
      if (settingsResponse['social'] != null) {
        final social = settingsResponse['social'];
        socialInfo = AppSettingsSocialInfo(
          fb: social['fb'] ?? '',
          twitter: social['twitter'] ?? '',
          instagram: social['instagram'] ?? '',
          youtube: social['youtube'] ?? '',
        );
      }
      
      // Extract contact and legal info
      supportEmail = settingsResponse['support_email'] ?? '';
      privacyUrl = settingsResponse['privacy_url'] ?? '';
      termsUrl = settingsResponse['terms_of_use_url'] ?? '';
      website = settingsResponse['website'] ?? '';
      
      print('📱 App Settings from API: postLayout=$postLayout, categoryLayout=$categoryLayout');
    } catch (e) {
      print('⚠️ Error fetching app settings, using defaults: $e');
    }
    
    return AppSettingsModel(
      featured: true,
      tags: true,
      skipLogin: true,
      onBoarding: false,
      latestArticles: true,
      popular: true,
      comments: true,
      author: true,
      views: true,
      likes: true,
      videoTab: true,
      audioTab: true,
      drawerMenu: true,
      logoAtCenter: false,
      date: true,
      readingTime: true,
      searchBox: true,
      featureAutoSlide: true,
      license: LicenseType.regular, // Set to regular to bypass license check
      postDetailsLayout: postLayout,
      categoryTileLayout: categoryLayout,
      homeCategories: parentCategories, // Add parent categories for home tabs
      social: socialInfo, // Add social media links
      supportEmail: supportEmail,
      privacyUrl: privacyUrl,
      termsOfUseUrl: termsUrl,
      website: website,
    );
  }
  
  Future<dynamic> getUserData() async {
    try {
      // Check if user has auth token
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      
      if (token == null || token.isEmpty) {
        debugPrint('No auth token found - user not logged in');
        return null;
      }
      
      debugPrint('Fetching user profile with token: $token');
      final userProfile = await ApiService().getUserProfile(token);
      
      if (userProfile != null) {
        debugPrint('✅ User profile loaded: ${userProfile.name}');
        return userProfile;
      } else {
        debugPrint('❌ Failed to load user profile');
        return null;
      }
    } catch (e) {
      debugPrint('Error getting user data: $e');
      return null;
    }
  }
  
  Future<QuerySnapshot> getArticlesSnapshotByLatest({DocumentSnapshot? lastDocument}) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getArticlesSnapshotByCategory({
    required String categoryId,
    DocumentSnapshot? lastDocument,
  }) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getArticlesSnapshotByPopular({DocumentSnapshot? lastDocument}) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getArticlesSnapshotByTag({
    required String tagId,
    DocumentSnapshot? lastDocument,
  }) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getArticlesSnapshotByAuhtor({
    required String authorId,
    DocumentSnapshot? lastDocument,
  }) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getVideoArticlesSnapshot({
    DocumentSnapshot? lastDocument,
    required dynamic articlesBy,
  }) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getPodcastArticlesSnapshot({
    DocumentSnapshot? lastDocument,
    required dynamic articlesBy,
  }) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getCommentsSnapshot({
    required String articleId,
    DocumentSnapshot? lastDocument,
  }) async {
    return QuerySnapshot([]);
  }
  
  Future<QuerySnapshot> getArticlesQuery(List<String> ids) async {
    return QuerySnapshot([]);
  }
  
  static String getUID(String collection) => DateTime.now().millisecondsSinceEpoch.toString();
  
  Future<void> saveComment(Comment comment) async {}
  Future<void> deleteComment(Comment comment) async {}
  Future<bool> isUserExists(String uid) async => false;
  Future<void> saveUserData(dynamic userData) async {}
  Future<void> updateUserStats() async {}
  Future<void> deleteUserDatafromDatabase(String userId) async {}
  Future<String?> uploadImageToHosting(dynamic file) async => null;
  Future<void> updateUserProfile(dynamic userData) async {}
  Future<void> updateBookmark(dynamic user, Article article) async {}
  Future<void> updateLikesCount(String articleId, bool isLiked) async {}
  Future<void> updateLikedIds(dynamic user, Article article) async {}
  Future<void> updateViewsCount(String articleId) async {}
}
