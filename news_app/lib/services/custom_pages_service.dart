import 'dart:convert';
import 'package:http/http.dart' as http;
import '../constants/api_constants.dart';
import '../models/custom_page.dart';
import '../models/custom_page_detail.dart';

class CustomPagesService {
  static const String _baseUrl = ApiConstants.baseUrl;

  Future<List<CustomPage>> getAppPages() async {
    try {
      final response = await http.get(
        Uri.parse('$_baseUrl/app/get-app-pages.php'),
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        
        if (data['success'] == true && data['pages'] != null) {
          final List<dynamic> pagesJson = data['pages'];
          return pagesJson.map((json) => CustomPage.fromJson(json)).toList();
        }
        return [];
      } else {
        throw Exception('Failed to load app pages: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching app pages: $e');
      return [];
    }
  }

  Future<CustomPageDetail?> getPageDetails({String? slug, int? pageId}) async {
    try {
      if (slug == null && pageId == null) {
        throw Exception('Either slug or pageId must be provided');
      }

      String url = '$_baseUrl/app/get-page-details.php?';
      if (slug != null) {
        url += 'slug=$slug';
      } else {
        url += 'id=$pageId';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        
        if (data['success'] == true && data['page'] != null) {
          return CustomPageDetail.fromJson(data['page']);
        }
        return null;
      } else {
        throw Exception('Failed to load page details: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching page details: $e');
      return null;
    }
  }
}
