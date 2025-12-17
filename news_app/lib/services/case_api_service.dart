import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/case_thread.dart';
import '../models/article.dart';
import 'api_service.dart';

class CaseApiService {
  static const String baseUrl = ApiService.apiUrl;

  static Map<String, String> get headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  static Map<String, String> headersWithAuth(String token) => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      };

  // Get all cases with filters
  static Future<Map<String, dynamic>> getCases({
    String? category,
    String? status,
    String? search,
    int page = 1,
    int limit = 20,
  }) async {
    final queryParams = {
      'page': page.toString(),
      'limit': limit.toString(),
      if (category != null && category.isNotEmpty) 'category': category,
      if (status != null && status.isNotEmpty) 'status': status,
      if (search != null && search.isNotEmpty) 'search': search,
    };

    final uri = Uri.parse('$baseUrl/cases/list.php').replace(queryParameters: queryParams);
    print('📋 Fetching cases from: $uri');
    
    final response = await http.get(uri, headers: headers);
    print('📋 Case API Response Status: ${response.statusCode}');

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      print('📋 Case API Success: ${data['success']}, Cases: ${data['data']?['cases']?.length ?? 0}');
      if (data['success'] == true) {
        return {
          'cases': (data['data']['cases'] as List).map((c) => CaseThread.fromJson(c)).toList(),
          'pagination': data['data']['pagination'],
        };
      }
    } else {
      print('📋 Case API failed with status: ${response.statusCode}');
      print('📋 Response body: ${response.body}');
    }
    throw Exception('Failed to load cases - Status: ${response.statusCode}');
  }

  // Get single case with all details
  static Future<Map<String, dynamic>> getCaseDetail(int caseId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/cases/single.php?id=$caseId'),
      headers: headers,
    );

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return {
          'case': CaseThread.fromJson(data['data']['case']),
          'timeline': (data['data']['timeline']['events'] as List).map((e) => TimelineEvent.fromJson(e)).toList(),
          'articles': (data['data']['recent_articles']['articles'] as List).map((a) => Article.fromJson(a)).toList(),
          'documents': data['data']['documents']['documents'],
          'media': data['data']['media']['items'],
          'reviews': data['data']['reviews']['reviews'],
        };
      }
    }
    throw Exception('Failed to load case');
  }

  // Get articles for a specific case
  static Future<Map<String, dynamic>> getCaseArticles(
    int caseId, {
    int page = 1,
    int limit = 20,
  }) async {
    final uri = Uri.parse('$baseUrl/cases/articles.php').replace(queryParameters: {
      'case_id': caseId.toString(),
      'page': page.toString(),
      'limit': limit.toString(),
    });

    final response = await http.get(uri, headers: headers);

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success'] == true) {
        return {
          'articles': (data['data']['articles'] as List).map((a) => Article.fromJson(a)).toList(),
          'pagination': data['data']['pagination'],
        };
      }
    }
    throw Exception('Failed to load case articles');
  }

  // Follow case
  static Future<bool> followCase(int caseId, String token) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/cases/follow.php'),
        headers: headersWithAuth(token),
        body: json.encode({'case_id': caseId}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  // Unfollow case
  static Future<bool> unfollowCase(int caseId, String token) async {
    try {
      final response = await http.delete(
        Uri.parse('$baseUrl/cases/unfollow.php'),
        headers: headersWithAuth(token),
        body: json.encode({'case_id': caseId}),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        return data['success'] == true;
      }
      return false;
    } catch (e) {
      return false;
    }
  }

  // Get user's followed cases
  static Future<List<CaseThread>> getFollowedCases(String token) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/cases/followed.php'),
        headers: headersWithAuth(token),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success'] == true) {
          return (data['data']['cases'] as List).map((c) => CaseThread.fromJson(c)).toList();
        }
      }
      return [];
    } catch (e) {
      return [];
    }
  }
}
