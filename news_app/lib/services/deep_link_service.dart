import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:app_links/app_links.dart';
import '../models/article.dart';
import '../services/api_service.dart';
import '../utils/next_screen.dart';
import '../providers/app_settings_provider.dart';
import '../providers/user_data_provider.dart';

class DeepLinkService {
  static final DeepLinkService _instance = DeepLinkService._internal();
  factory DeepLinkService() => _instance;
  DeepLinkService._internal();

  StreamSubscription? _linkSubscription;
  BuildContext? _context;
  WidgetRef? _ref;
  final _appLinks = AppLinks();

  /// Initialize deep link handling
  Future<void> init(BuildContext context, WidgetRef ref) async {
    _context = context;
    _ref = ref;
    
    // Handle initial deep link if app was closed
    try {
      final initialLink = await _appLinks.getInitialLink();
      if (initialLink != null) {
        debugPrint('📱 Initial Deep Link: $initialLink');
        await _handleDeepLink(initialLink.toString());
      }
    } catch (e) {
      debugPrint('Error handling initial link: $e');
    }

    // Listen for deep links while app is running
    _linkSubscription = _appLinks.uriLinkStream.listen((Uri uri) {
      debugPrint('📱 Deep Link Received: $uri');
      _handleDeepLink(uri.toString());
    }, onError: (err) {
      debugPrint('Deep link error: $err');
    });
  }

  /// Handle incoming deep link
  Future<void> _handleDeepLink(String link) async {
    if (_context == null || !_context!.mounted) return;

    try {
      final uri = Uri.parse(link);
      debugPrint('🔗 Parsing URI: ${uri.toString()}');
      debugPrint('   Scheme: ${uri.scheme}');
      debugPrint('   Host: ${uri.host}');
      debugPrint('   Path: ${uri.path}');
      debugPrint('   Query: ${uri.queryParameters}');

      // Handle custom scheme (newshour://)
      if (uri.scheme == 'newshour') {
        await _handleCustomScheme(uri);
        return;
      }

      // Handle HTTP/HTTPS links
      if (uri.scheme == 'http' || uri.scheme == 'https') {
        await _handleWebLink(uri);
        return;
      }
    } catch (e) {
      debugPrint('Error parsing deep link: $e');
    }
  }

  /// Handle custom scheme deep links (newshour://)
  Future<void> _handleCustomScheme(Uri uri) async {
    if (_context == null || !_context!.mounted) return;

    final path = uri.host; // e.g., newshour://article/123
    final segments = uri.pathSegments;

    switch (path) {
      case 'article':
        if (segments.isNotEmpty) {
          final articleId = segments.first;
          await _openArticle(articleId);
        }
        break;

      case 'category':
        if (segments.isNotEmpty) {
          final categoryId = segments.first;
          await _openCategory(categoryId);
        }
        break;

      case 'tag':
        if (segments.isNotEmpty) {
          final tagId = segments.first;
          await _openTag(tagId);
        }
        break;

      case 'home':
        // Navigate to home tab
        Navigator.of(_context!).popUntil((route) => route.isFirst);
        break;

      default:
        debugPrint('Unknown custom scheme path: $path');
    }
  }

  /// Handle web links (http://yourdomain.com/article.php?id=123)
  Future<void> _handleWebLink(Uri uri) async {
    if (_context == null || !_context!.mounted) return;

    final path = uri.path;
    final params = uri.queryParameters;

    if (path.contains('article.php') && params.containsKey('id')) {
      await _openArticle(params['id']!);
    } else if (path.contains('category.php') && params.containsKey('id')) {
      await _openCategory(params['id']!);
    } else if (path.contains('tag.php') && params.containsKey('id')) {
      await _openTag(params['id']!);
    } else {
      debugPrint('Unknown web link path: $path');
    }
  }

  /// Open article by ID
  Future<void> _openArticle(String articleId) async {
    if (_context == null || !_context!.mounted) return;

    try {
      // Show loading indicator
      showDialog(
        context: _context!,
        barrierDismissible: false,
        builder: (context) => const Center(
          child: CircularProgressIndicator(),
        ),
      );

      // Fetch article from API
      final response = await ApiService().getArticleById(articleId);
      
      if (_context!.mounted) {
        Navigator.pop(_context!); // Close loading dialog
      }

      if (response != null) {
        if (_context!.mounted && _ref != null) {
          // Use NextScreen to handle navigation with random layouts
          NextScreen().handlePostNavigation(_context!, response, null, false, _ref!);
        }
      } else {
        if (_context!.mounted) {
          ScaffoldMessenger.of(_context!).showSnackBar(
            const SnackBar(content: Text('Article not found')),
          );
        }
      }
    } catch (e) {
      if (_context!.mounted) {
        Navigator.pop(_context!); // Close loading dialog
        ScaffoldMessenger.of(_context!).showSnackBar(
          SnackBar(content: Text('Error loading article: $e')),
        );
      }
    }
  }

  /// Open category by ID
  Future<void> _openCategory(String categoryId) async {
    if (_context == null || !_context!.mounted) return;

    // TODO: Navigate to category screen
    // You'll need to fetch category details and navigate
    debugPrint('Opening category: $categoryId');
  }

  /// Open tag by ID
  Future<void> _openTag(String tagId) async {
    if (_context == null || !_context!.mounted) return;

    // TODO: Navigate to tag screen
    debugPrint('Opening tag: $tagId');
  }

  /// Generate deep link for article
  static String generateArticleLink(String articleId) {
    return 'newshour://article/$articleId';
  }

  /// Generate web link for article
  static String generateArticleWebLink(String articleId) {
    return '${ApiService.baseUrl}/article.php?id=$articleId';
  }

  /// Dispose the service
  void dispose() {
    _linkSubscription?.cancel();
    _context = null;
  }
}
