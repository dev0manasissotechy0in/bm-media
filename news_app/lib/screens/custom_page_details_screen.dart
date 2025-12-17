import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:share_plus/share_plus.dart';
import 'package:http/http.dart' as http;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'dart:convert';
import '../constants/api_constants.dart';
import '../models/custom_page_detail.dart';
import '../models/article.dart';
import '../models/article_category.dart';
import '../utils/next_screen.dart';
import '../components/article_tiles/article_tile4.dart';
import '../utils/loading_widget.dart';

class CustomPageDetailsScreen extends ConsumerStatefulWidget {
  final String? slug;
  final int? pageId;
  final String? pageTitle;

  const CustomPageDetailsScreen({
    Key? key,
    this.slug,
    this.pageId,
    this.pageTitle,
  }) : super(key: key);

  @override
  ConsumerState<CustomPageDetailsScreen> createState() => _CustomPageDetailsScreenState();
}

class _CustomPageDetailsScreenState extends ConsumerState<CustomPageDetailsScreen> {
  bool _isLoading = true;
  CustomPageDetail? _pageDetail;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _fetchPageDetails();
  }

  Future<void> _fetchPageDetails() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      String url = '${ApiConstants.baseUrl}/api/app/get-page-details.php?';
      if (widget.slug != null) {
        url += 'slug=${widget.slug}';
      } else if (widget.pageId != null) {
        url += 'id=${widget.pageId}';
      } else {
        throw Exception('Page slug or ID is required');
      }

      debugPrint('📄 Fetching page details from: $url');

      final response = await http.get(
        Uri.parse(url),
        headers: {'Content-Type': 'application/json'},
      ).timeout(const Duration(seconds: 15));

      debugPrint('📄 Response status: ${response.statusCode}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        debugPrint('📄 Response data: ${data['success']}');

        if (data['success'] == true && data['page'] != null) {
          setState(() {
            _pageDetail = CustomPageDetail.fromJson(data['page']);
            _isLoading = false;
          });
        } else {
          setState(() {
            _errorMessage = data['message'] ?? 'Failed to load page';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _errorMessage = 'Failed to load page. Status: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      debugPrint('❌ Error fetching page details: $e');
      setState(() {
        _errorMessage = 'Error: $e';
        _isLoading = false;
      });
    }
  }

  void _handleShare() {
    if (_pageDetail != null) {
      Share.share(
        '${_pageDetail!.title}\n\n${_pageDetail!.shareUrl}',
        subject: _pageDetail!.title,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.pageTitle ?? _pageDetail?.title ?? 'Page'),
        actions: [
          if (_pageDetail != null)
            IconButton(
              icon: const Icon(Icons.share),
              onPressed: _handleShare,
              tooltip: 'Share',
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(child: LoadingIndicatorWidget());
    }

    if (_errorMessage != null) {
      return _buildErrorView();
    }

    if (_pageDetail == null) {
      return _buildErrorView();
    }

    return RefreshIndicator(
      onRefresh: _fetchPageDetails,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Page Title
            Text(
              _pageDetail!.title,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 8),

            // Views count
            Row(
              children: [
                Icon(
                  Icons.visibility,
                  size: 16,
                  color: Theme.of(context).textTheme.bodySmall?.color,
                ),
                const SizedBox(width: 4),
                Text(
                  '${_pageDetail!.views} views',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
            const SizedBox(height: 20),

            // Page Content based on type
            if (_pageDetail!.type == 'text') _buildTextContent(),
            if (_pageDetail!.type == 'category_articles' ||
                _pageDetail!.type == 'tag_articles')
              _buildArticlesContent(),

            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildTextContent() {
    return Card(
      elevation: 0,
      color: Theme.of(context).cardColor,
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Html(
          data: _pageDetail!.content,
          style: {
            "body": Style(
              margin: Margins.zero,
              padding: HtmlPaddings.zero,
              fontSize: FontSize(16),
              lineHeight: const LineHeight(1.6),
            ),
            "p": Style(
              margin: Margins.only(bottom: 12),
            ),
            "h1": Style(
              fontSize: FontSize(24),
              fontWeight: FontWeight.bold,
              margin: Margins.only(top: 16, bottom: 12),
            ),
            "h2": Style(
              fontSize: FontSize(22),
              fontWeight: FontWeight.bold,
              margin: Margins.only(top: 16, bottom: 12),
            ),
            "h3": Style(
              fontSize: FontSize(20),
              fontWeight: FontWeight.bold,
              margin: Margins.only(top: 16, bottom: 12),
            ),
            "ul": Style(
              margin: Margins.only(left: 16, bottom: 12),
            ),
            "ol": Style(
              margin: Margins.only(left: 16, bottom: 12),
            ),
            "a": Style(
              color: Theme.of(context).primaryColor,
              textDecoration: TextDecoration.underline,
            ),
            "img": Style(
              width: Width(double.infinity),
              margin: Margins.only(top: 12, bottom: 12),
            ),
          },
        ),
      ),
    );
  }

  Widget _buildArticlesContent() {
    if (_pageDetail!.articles == null || _pageDetail!.articles!.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32.0),
          child: Column(
            children: [
              Icon(
                Icons.article_outlined,
                size: 64,
                color: Theme.of(context).textTheme.bodySmall?.color,
              ),
              const SizedBox(height: 16),
              Text(
                'No articles found',
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ],
          ),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Related Articles',
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.bold,
              ),
        ),
        const SizedBox(height: 16),
        ListView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _pageDetail!.articles!.length,
          itemBuilder: (context, index) {
            final article = _pageDetail!.articles![index];
            return _buildArticleCard(article);
          },
        ),
      ],
    );
  }

  Widget _buildArticleCard(CustomPageArticle pageArticle) {
    // Convert CustomPageArticle to Article model for compatibility
    final article = Article(
      id: pageArticle.id,
      title: pageArticle.title,
      description: pageArticle.description,
      thumbnailUrl: pageArticle.imageUrl,
      contentType: pageArticle.contentType,
      views: pageArticle.views,
      likes: pageArticle.likes,
      createdAt: DateTime.parse(pageArticle.publishedAt),
      status: 'published',
      priceStatus: 'free',
      category: ArticleCategory(
        id: '0',
        name: pageArticle.category['name'] ?? '',
      ),
    );

    return GestureDetector(
      onTap: () {
        NextScreen().handlePostNavigation(
          context,
          article,
          UniqueKey(),
          false,
          ref,
        );
      },
      child: ArticleTile4(
        article: article,
      ),
    );
  }

  Widget _buildErrorView() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32.0),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              Icons.error_outline,
              size: 64,
              color: Theme.of(context).colorScheme.error,
            ),
            const SizedBox(height: 16),
            Text(
              _errorMessage ?? 'Failed to load page',
              style: Theme.of(context).textTheme.titleMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _fetchPageDetails,
              icon: const Icon(Icons.refresh),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}
