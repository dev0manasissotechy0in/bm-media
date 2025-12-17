import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';

class EmailAuthService {
  static const String baseUrl = ApiService.baseUrl;
  
  /// Send OTP to email
  Future<Map<String, dynamic>> sendOTP({
    required String email,
    String? name,
    String userType = 'user',
  }) async {
    try {
      debugPrint('📧 Sending OTP to: $email');
      
      final response = await http.post(
        Uri.parse('$baseUrl/api/auth/send-otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'name': name ?? '',
          'user_type': userType,
        }),
      );

      debugPrint('📧 Response Status: ${response.statusCode}');
      debugPrint('📧 Response Body: ${response.body}');

      final data = json.decode(response.body);
      
      if (response.statusCode == 200 && data['success'] == true) {
        debugPrint('✅ OTP sent successfully');
        
        // Show OTP in debug mode
        if (data['debug'] != null && data['debug']['otp'] != null) {
          debugPrint('🔐 DEBUG OTP: ${data['debug']['otp']}');
        }
        
        return {
          'success': true,
          'message': data['message'],
          'expires_in_minutes': data['expires_in_minutes'],
          'debug_otp': data['debug']?['otp'], // Pass OTP for debugging
        };
      } else {
        debugPrint('❌ OTP send failed: ${data['message']}');
        return {
          'success': false,
          'message': data['message'] ?? 'Failed to send OTP',
        };
      }
    } catch (e) {
      debugPrint('❌ Error sending OTP: $e');
      return {
        'success': false,
        'message': 'Network error. Please check your connection.',
      };
    }
  }

  /// Verify OTP and login/register
  Future<Map<String, dynamic>> verifyOTP({
    required String email,
    required String otp,
    String? name,
    String userType = 'user',
    String? fcmToken,
  }) async {
    try {
      debugPrint('🔐 Verifying OTP for: $email');
      debugPrint('🔐 OTP Code: $otp');
      
      final requestBody = {
        'email': email,
        'otp': otp,
        'name': name ?? '',
        'user_type': userType,
        'fcm_token': fcmToken,
      };
      
      debugPrint('🔐 Request Body: ${json.encode(requestBody)}');
      
      final response = await http.post(
        Uri.parse('$baseUrl/api/auth/verify-otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode(requestBody),
      ).timeout(
        const Duration(seconds: 10),
        onTimeout: () {
          debugPrint('❌ Verify OTP request timed out');
          throw Exception('Request timed out after 10 seconds');
        },
      );

      debugPrint('🔐 Response Status: ${response.statusCode}');
      debugPrint('🔐 Response Body: ${response.body}');

      final data = json.decode(response.body);
      
      if (response.statusCode == 200 && data['success'] == true) {
        debugPrint('✅ OTP verified successfully');
        return {
          'success': true,
          'message': data['message'],
          'user': data['user'],
          'token': data['token'],
        };
      } else {
        debugPrint('❌ OTP verification failed: ${data['message']}');
        return {
          'success': false,
          'message': data['message'] ?? 'Invalid OTP',
        };
      }
    } catch (e, stackTrace) {
      debugPrint('❌ Error verifying OTP: $e');
      debugPrint('❌ Stack trace: $stackTrace');
      return {
        'success': false,
        'message': 'Network error: ${e.toString()}',
      };
    }
  }
}
