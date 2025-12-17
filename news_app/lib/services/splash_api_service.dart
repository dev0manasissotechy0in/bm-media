import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/splash_settings.dart';

class SplashApiService {
  static const String baseUrl = 'http://localhost/api/splash';
  
  /// Fetch splash screen settings from server
  /// 
  /// [platform] should be 'android', 'ios', or 'web'
  /// [isFirstTime] indicates if this is user's first app launch
  static Future<SplashSettings?> getSplashSettings({
    String platform = 'all',
    bool? isFirstTime,
  }) async {
    try {
      // Auto-detect if first time if not provided
      if (isFirstTime == null) {
        final prefs = await SharedPreferences.getInstance();
        isFirstTime = !(prefs.getBool('has_launched_before') ?? false);
        
        // Mark as launched
        if (isFirstTime) {
          await prefs.setBool('has_launched_before', true);
        }
      }
      
      // Build URL with query parameters
      final uri = Uri.parse('$baseUrl/settings.php').replace(
        queryParameters: {
          'platform': platform,
          'first_time': isFirstTime.toString(),
        },
      );
      
      final response = await http.get(
        uri,
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 10));
      
      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return SplashSettings.fromJson(jsonData);
      } else {
        print('Failed to fetch splash settings: ${response.statusCode}');
        return null;
      }
    } catch (e) {
      print('Error fetching splash settings: $e');
      return null;
    }
  }
  
  /// Track button click on dynamic splash
  static Future<bool> trackClick() async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/track.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'action': 'button_click'}),
      ).timeout(const Duration(seconds: 5));
      
      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        return jsonData['success'] ?? false;
      }
      return false;
    } catch (e) {
      print('Error tracking splash click: $e');
      return false;
    }
  }
  
  /// Get platform identifier
  static String getPlatform() {
    // In a real app, use Platform.isAndroid, Platform.isIOS, etc.
    // For now, returning 'all' as fallback
    try {
      // ignore: undefined_identifier
      if (Platform.isAndroid) return 'android';
      // ignore: undefined_identifier
      if (Platform.isIOS) return 'ios';
      return 'web';
    } catch (e) {
      return 'all';
    }
  }
}
