import 'dart:convert';
import 'package:http/http.dart' as http;
import 'api_service.dart';

class ContactService {
  /// Submit a contact form from the mobile app
  static Future<Map<String, dynamic>> submitContact({
    required String name,
    required String email,
    required String subject,
    required String message,
  }) async {
    try {
      final url = Uri.parse('${ApiService.baseUrl}/api/app/submit-contact.php');
      
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json',
        },
        body: jsonEncode({
          'name': name,
          'email': email,
          'subject': subject,
          'message': message,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data;
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }
}
