import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/api_constants.dart';
import '../models/app_branding.dart';

class BrandingService {
  static const String _cacheKey = 'app_branding_cache';
  static const Duration _cacheDuration = Duration(hours: 6);

  // Fetch app branding from API with caching
  Future<AppBranding> getAppBranding() async {
    try {
      // Try to get cached data first
      final cachedBranding = await _getCachedBranding();
      if (cachedBranding != null) {
        return cachedBranding;
      }

      // Fetch from API
      final response = await http.get(
        Uri.parse('${ApiConstants.apiUrl}/get-app-branding.php'),
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        if (jsonData['success'] == true) {
          final branding = AppBranding.fromJson(jsonData['data']);
          
          // Cache the branding data
          await _cacheBranding(branding);
          
          return branding;
        }
      }

      // If API fails, return default branding
      return AppBranding.defaultBranding();
    } catch (e) {
      print('Error fetching app branding: $e');
      // Return cached data if available, otherwise default
      final cachedBranding = await _getCachedBranding();
      return cachedBranding ?? AppBranding.defaultBranding();
    }
  }

  // Cache branding data
  Future<void> _cacheBranding(AppBranding branding) async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final brandingJson = json.encode(branding.toJson());
      await prefs.setString(_cacheKey, brandingJson);
      await prefs.setInt('${_cacheKey}_timestamp', DateTime.now().millisecondsSinceEpoch);
    } catch (e) {
      print('Error caching branding: $e');
    }
  }

  // Get cached branding if not expired
  Future<AppBranding?> _getCachedBranding() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final brandingJson = prefs.getString(_cacheKey);
      final timestamp = prefs.getInt('${_cacheKey}_timestamp');

      if (brandingJson != null && timestamp != null) {
        final cacheTime = DateTime.fromMillisecondsSinceEpoch(timestamp);
        final now = DateTime.now();
        
        // Check if cache is still valid
        if (now.difference(cacheTime) < _cacheDuration) {
          final brandingData = json.decode(brandingJson);
          return AppBranding.fromJson(brandingData);
        }
      }
    } catch (e) {
      print('Error reading cached branding: $e');
    }
    return null;
  }

  // Clear cache (useful for testing)
  Future<void> clearCache() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_cacheKey);
    await prefs.remove('${_cacheKey}_timestamp');
  }
}
