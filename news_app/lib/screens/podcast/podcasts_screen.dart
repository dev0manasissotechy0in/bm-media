import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/podcast.dart';
import '../../services/api_service.dart';
import 'single_podcast_screen.dart';

class PodcastsScreen extends ConsumerStatefulWidget {
  const PodcastsScreen({Key? key}) : super(key: key);

  @override
  ConsumerState<PodcastsScreen> createState() => _PodcastsScreenState();
}

class _PodcastsScreenState extends ConsumerState<PodcastsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<Podcast> _allPodcasts = [];
  List<Podcast> _savedPodcasts = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadPodcasts();
  }

  Future<void> _loadPodcasts() async {
    setState(() => _isLoading = true);
    try {
      final api = ApiService();
      final all = await api.getAllPodcasts();
      final saved = await api.getSavedPodcasts();
      
      setState(() {
        _allPodcasts = all;
        _savedPodcasts = saved;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error loading podcasts: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Podcasts'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'All Podcasts'),
            Tab(text: 'Saved'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildPodcastList(_allPodcasts),
                _buildPodcastList(_savedPodcasts),
              ],
            ),
    );
  }

  Widget _buildPodcastList(List<Podcast> podcasts) {
    if (podcasts.isEmpty) {
      return const Center(
        child: Text('No podcasts available'),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadPodcasts,
      child: ListView.builder(
        itemCount: podcasts.length,
        itemBuilder: (context, index) {
          final podcast = podcasts[index];
          return _buildPodcastCard(podcast);
        },
      ),
    );
  }

  Widget _buildPodcastCard(Podcast podcast) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => SinglePodcastScreen(podcast: podcast),
            ),
          );
        },
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Thumbnail
              ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: Image.network(
                  podcast.imageUrl,
                  width: 100,
                  height: 100,
                  fit: BoxFit.cover,
                  errorBuilder: (context, error, stackTrace) {
                    return Container(
                      width: 100,
                      height: 100,
                      color: Colors.grey[300],
                      child: const Icon(Icons.podcasts, size: 40),
                    );
                  },
                ),
              ),
              const SizedBox(width: 12),
              
              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      podcast.title,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      podcast.description,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(Icons.person, size: 14, color: Colors.grey[600]),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            podcast.authorName,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        if (podcast.isSeries) ...[
                          Icon(Icons.list, size: 14, color: Colors.grey[600]),
                          const SizedBox(width: 4),
                          Text(
                            '${podcast.totalEpisodes} Episodes',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[600],
                            ),
                          ),
                          const SizedBox(width: 12),
                        ],
                        Icon(Icons.favorite, size: 14, color: Colors.grey[600]),
                        const SizedBox(width: 4),
                        Text(
                          '${podcast.likesCount}',
                          style: TextStyle(
                            fontSize: 12,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}
