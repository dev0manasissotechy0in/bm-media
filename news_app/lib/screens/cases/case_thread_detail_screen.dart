import 'package:flutter/material.dart';
import '../../services/case_api_service.dart';
import '../../models/case_thread.dart';
import '../../models/article.dart';
import '../article_details/layouts/details_view1.dart';

class CaseThreadDetailScreen extends StatefulWidget {
  final int caseId;

  const CaseThreadDetailScreen({super.key, required this.caseId});

  @override
  State<CaseThreadDetailScreen> createState() => _CaseThreadDetailScreenState();
}

class _CaseThreadDetailScreenState extends State<CaseThreadDetailScreen> with SingleTickerProviderStateMixin {
  CaseThread? caseThread;
  List<TimelineEvent> timeline = [];
  List<Article> articles = [];
  List documents = [];
  List media = [];
  bool isLoading = true;
  late TabController tabController;

  @override
  void initState() {
    super.initState();
    tabController = TabController(length: 4, vsync: this);
    loadCaseDetail();
  }

  @override
  void dispose() {
    tabController.dispose();
    super.dispose();
  }

  Future<void> loadCaseDetail() async {
    setState(() => isLoading = true);

    try {
      final data = await CaseApiService.getCaseDetail(widget.caseId);

      setState(() {
        caseThread = data['case'];
        timeline = data['timeline'];
        articles = data['articles'];
        documents = data['documents'];
        media = data['media'];
        isLoading = false;
      });
    } catch (e) {
      setState(() => isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to load case: $e')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (isLoading) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (caseThread == null) {
      return Scaffold(
        appBar: AppBar(),
        body: const Center(child: Text('Case not found')),
      );
    }

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 300,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              title: Text(
                caseThread!.title,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
              ),
              background: Stack(
                fit: StackFit.expand,
                children: [
                  if (caseThread!.coverImage != null)
                    Image.network(
                      caseThread!.coverImage!,
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return Container(color: Colors.grey[300]);
                      },
                    )
                  else
                    Container(color: Colors.grey[300]),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [Colors.transparent, Colors.black.withOpacity(0.8)],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: _getStatusColor(caseThread!.status),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              caseThread!.status.toUpperCase(),
                              style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                            ),
                          ),
                          const Spacer(),
                          IconButton(
                            icon: Icon(caseThread!.isFollowing ? Icons.notifications_active : Icons.notifications_none),
                            onPressed: toggleFollow,
                            color: Colors.blue,
                          ),
                          IconButton(
                            icon: const Icon(Icons.share),
                            onPressed: () {},
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Text(caseThread!.fullDescription, style: const TextStyle(fontSize: 16, height: 1.6)),
                      const SizedBox(height: 20),
                      Row(
                        children: [
                          _buildStat(Icons.article, '${caseThread!.totalArticles}', 'Articles'),
                          const SizedBox(width: 24),
                          _buildStat(Icons.people, '${caseThread!.totalFollowers}', 'Followers'),
                          const SizedBox(width: 24),
                          _buildStat(Icons.visibility, '${caseThread!.totalViews}', 'Views'),
                        ],
                      ),
                    ],
                  ),
                ),
                Container(
                  color: Colors.grey[100],
                  child: TabBar(
                    controller: tabController,
                    labelColor: Colors.blue[900],
                    unselectedLabelColor: Colors.grey,
                    indicatorColor: Colors.blue[900],
                    tabs: const [
                      Tab(text: 'Timeline'),
                      Tab(text: 'Articles'),
                      Tab(text: 'Documents'),
                      Tab(text: 'Media'),
                    ],
                  ),
                ),
              ],
            ),
          ),
          SliverFillRemaining(
            child: TabBarView(
              controller: tabController,
              children: [
                TimelineTab(events: timeline),
                ArticlesTab(articles: articles),
                DocumentsTab(documents: documents),
                MediaTab(media: media),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStat(IconData icon, String value, String label) {
    return Row(
      children: [
        Icon(icon, size: 20, color: Colors.grey[600]),
        const SizedBox(width: 6),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
            Text(label, style: const TextStyle(color: Colors.grey, fontSize: 12)),
          ],
        ),
      ],
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'active':
        return Colors.green;
      case 'concluded':
        return Colors.grey;
      case 'archived':
        return Colors.orange;
      default:
        return Colors.blue;
    }
  }

  Future<void> toggleFollow() async {
    // TODO: Implement follow/unfollow with actual user token
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('Login required to follow cases')),
    );
  }
}

class TimelineTab extends StatelessWidget {
  final List<TimelineEvent> events;

  const TimelineTab({super.key, required this.events});

  @override
  Widget build(BuildContext context) {
    if (events.isEmpty) {
      return const Center(child: Text('No timeline events yet'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: events.length,
      itemBuilder: (context, index) {
        final event = events[index];
        return TimelineItem(event: event, isLast: index == events.length - 1);
      },
    );
  }
}

class TimelineItem extends StatelessWidget {
  final TimelineEvent event;
  final bool isLast;

  const TimelineItem({super.key, required this.event, this.isLast = false});

  @override
  Widget build(BuildContext context) {
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Column(
            children: [
              Container(
                width: 12,
                height: 12,
                decoration: BoxDecoration(
                  color: event.isMajorEvent ? Colors.red : Colors.blue,
                  shape: BoxShape.circle,
                  border: Border.all(color: Colors.white, width: 2),
                ),
              ),
              if (!isLast)
                Expanded(
                  child: Container(width: 2, color: Colors.grey[300]),
                ),
            ],
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Padding(
              padding: const EdgeInsets.only(bottom: 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    _formatDate(event.eventDate),
                    style: const TextStyle(color: Colors.grey, fontSize: 12, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          event.eventTitle,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                      ),
                      if (event.isMajorEvent)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.red[100],
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Text(
                            'MAJOR',
                            style: TextStyle(
                              color: Colors.red,
                              fontSize: 10,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(event.eventDescription, style: TextStyle(color: Colors.grey[700])),
                  if (event.linkedArticlesCount > 0) ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.article, size: 14, color: Colors.blue),
                        const SizedBox(width: 4),
                        Text(
                          '${event.linkedArticlesCount} related articles',
                          style: const TextStyle(color: Colors.blue, fontSize: 12),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatDate(DateTime date) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return '${date.day} ${months[date.month - 1]} ${date.year}';
  }
}

class ArticlesTab extends StatelessWidget {
  final List<Article> articles;

  const ArticlesTab({super.key, required this.articles});

  @override
  Widget build(BuildContext context) {
    if (articles.isEmpty) {
      return const Center(child: Text('No articles yet'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: articles.length,
      itemBuilder: (context, index) {
        final article = articles[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.network(
                article.thumbnailUrl ?? '',
                width: 60,
                height: 60,
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) {
                  return Container(
                    width: 60,
                    height: 60,
                    color: Colors.grey[300],
                    child: const Icon(Icons.article),
                  );
                },
              ),
            ),
            title: Text(article.title, style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text(article.author?.name ?? 'Unknown'),
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ArticleDetailsView1(article: article),
                ),
              );
            },
          ),
        );
      },
    );
  }
}

class DocumentsTab extends StatelessWidget {
  final List documents;

  const DocumentsTab({super.key, required this.documents});

  @override
  Widget build(BuildContext context) {
    if (documents.isEmpty) {
      return const Center(child: Text('No documents yet'));
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: documents.length,
      itemBuilder: (context, index) {
        final doc = documents[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: Colors.blue[50],
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.description, color: Colors.blue),
            ),
            title: Text(doc['title'] ?? 'Document', style: const TextStyle(fontWeight: FontWeight.bold)),
            subtitle: Text(doc['document_type'] ?? ''),
            trailing: const Icon(Icons.arrow_forward_ios, size: 16),
            onTap: () {
              // TODO: Open document
            },
          ),
        );
      },
    );
  }
}

class MediaTab extends StatelessWidget {
  final List media;

  const MediaTab({super.key, required this.media});

  @override
  Widget build(BuildContext context) {
    if (media.isEmpty) {
      return const Center(child: Text('No media yet'));
    }

    return GridView.builder(
      padding: const EdgeInsets.all(16),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
        childAspectRatio: 1,
      ),
      itemCount: media.length,
      itemBuilder: (context, index) {
        final item = media[index];
        return ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Stack(
            fit: StackFit.expand,
            children: [
              Image.network(
                item['thumbnail_url'] ?? item['file_url'],
                fit: BoxFit.cover,
                errorBuilder: (context, error, stackTrace) {
                  return Container(
                    color: Colors.grey[300],
                    child: const Icon(Icons.broken_image, size: 50),
                  );
                },
              ),
              if (item['media_type'] == 'video')
                const Center(
                  child: Icon(Icons.play_circle_fill, size: 48, color: Colors.white),
                ),
              Positioned(
                bottom: 0,
                left: 0,
                right: 0,
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [Colors.transparent, Colors.black.withOpacity(0.7)],
                    ),
                  ),
                  child: Text(
                    item['title'] ?? '',
                    style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}
