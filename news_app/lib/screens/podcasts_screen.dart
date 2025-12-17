import 'package:flutter/material.dart';
import 'package:cached_network_image/cached_network_image.dart';
import '../models/podcast_model.dart';
import '../services/podcast_api_service.dart';
import 'podcast_player_screen.dart';

class PodcastsScreen extends StatefulWidget {
  final int? userId;

  const PodcastsScreen({super.key, this.userId});

  @override
  State<PodcastsScreen> createState() => _PodcastsScreenState();
}

class _PodcastsScreenState extends State<PodcastsScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  List<PodcastCategory> _categories = [];
  List<Podcast> _featuredPodcasts = [];
  List<Podcast> _allPodcasts = [];
  List<Map<String, dynamic>> _history = [];
  bool _isLoading = true;
  int _selectedCategoryIndex = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
    });

    // Load categories
    final categories = await PodcastApiService.fetchCategories();
    
    // Load featured podcasts
    final featured = await PodcastApiService.fetchPodcasts(featured: true, limit: 10);
    
    // Load all podcasts
    final all = await PodcastApiService.fetchPodcasts(limit: 20);
    
    // Load history
    final guestToken = await PodcastApiService.getGuestToken();
    final history = await PodcastApiService.fetchHistory(
      userId: widget.userId,
      guestToken: guestToken,
      limit: 10,
    );

    setState(() {
      _categories = categories;
      _featuredPodcasts = featured;
      _allPodcasts = all;
      _history = history;
      _isLoading = false;
    });
  }

  Future<void> _loadPodcastsByCategory(int categoryId) async {
    final podcasts = await PodcastApiService.fetchPodcasts(
      categoryId: categoryId,
      limit: 20,
    );
    
    setState(() {
      _allPodcasts = podcasts;
    });
  }

  Color _hexToColor(String hexString) {
    final buffer = StringBuffer();
    if (hexString.length == 7) buffer.write('ff');
    buffer.write(hexString.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  String _formatDuration(int seconds) {
    final hours = seconds ~/ 3600;
    final minutes = (seconds % 3600) ~/ 60;
    final secs = seconds % 60;

    if (hours > 0) {
      return '$hours:${minutes.toString().padLeft(2, '0')}:${secs.toString().padLeft(2, '0')}';
    }
    return '$minutes:${secs.toString().padLeft(2, '0')}';
  }

  void _navigateToPlayer(Podcast podcast) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PodcastPlayerScreen(
          podcast: podcast,
          userId: widget.userId,
        ),
      ),
    ).then((_) {
      // Reload history when returning
      _loadData();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Podcasts'),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'Featured'),
            Tab(text: 'All'),
            Tab(text: 'History'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : TabBarView(
              controller: _tabController,
              children: [
                _buildFeaturedTab(),
                _buildAllTab(),
                _buildHistoryTab(),
              ],
            ),
    );
  }

  Widget _buildFeaturedTab() {
    if (_featuredPodcasts.isEmpty) {
      return const Center(
        child: Text('No featured podcasts available'),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _featuredPodcasts.length,
      itemBuilder: (context, index) {
        final podcast = _featuredPodcasts[index];
        return _buildPodcastCard(podcast);
      },
    );
  }

  Widget _buildAllTab() {
    return Column(
      children: [
        // Category Filter
        if (_categories.isNotEmpty)
          Container(
            height: 60,
            padding: const EdgeInsets.symmetric(vertical: 8),
            child: ListView.builder(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: _categories.length + 1,
              itemBuilder: (context, index) {
                if (index == 0) {
                  return _buildCategoryChip('All', 0, Colors.blue);
                }
                final category = _categories[index - 1];
                return _buildCategoryChip(
                  category.name,
                  category.id,
                  _hexToColor(category.color),
                );
              },
            ),
          ),
        
        // Podcasts List
        Expanded(
          child: _allPodcasts.isEmpty
              ? const Center(child: Text('No podcasts available'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _allPodcasts.length,
                  itemBuilder: (context, index) {
                    final podcast = _allPodcasts[index];
                    return _buildPodcastCard(podcast);
                  },
                ),
        ),
      ],
    );
  }

  Widget _buildHistoryTab() {
    if (_history.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.history, size: 64, color: Colors.grey),
            SizedBox(height: 16),
            Text(
              'No listening history yet',
              style: TextStyle(fontSize: 16, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: _history.length,
      itemBuilder: (context, index) {
        final item = _history[index];
        final progress = item['progress_percentage'] ?? 0.0;
        
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          child: ListTile(
            leading: ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: CachedNetworkImage(
                imageUrl: 'http://localhost/${item['cover_image']}',
                width: 60,
                height: 60,
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(
                  color: Colors.grey[300],
                  child: const Icon(Icons.podcast),
                ),
              ),
            ),
            title: Text(
              item['title'] ?? '',
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            subtitle: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                Text(item['category_name'] ?? ''),
                const SizedBox(height: 4),
                LinearProgressIndicator(
                  value: progress / 100,
                  backgroundColor: Colors.grey[300],
                  valueColor: const AlwaysStoppedAnimation<Color>(Colors.green),
                ),
                const SizedBox(height: 2),
                Text(
                  '${progress.toStringAsFixed(0)}% complete',
                  style: const TextStyle(fontSize: 12),
                ),
              ],
            ),
            trailing: IconButton(
              icon: const Icon(Icons.play_circle_filled),
              onPressed: () {
                // Fetch full podcast and navigate
                _fetchAndPlayPodcast(item['podcast_id']);
              },
            ),
          ),
        );
      },
    );
  }

  Widget _buildCategoryChip(String label, int categoryId, Color color) {
    final isSelected = _selectedCategoryIndex == categoryId;
    
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: FilterChip(
        label: Text(label),
        selected: isSelected,
        onSelected: (selected) {
          setState(() {
            _selectedCategoryIndex = categoryId;
          });
          
          if (categoryId == 0) {
            _loadData(); // Load all
          } else {
            _loadPodcastsByCategory(categoryId);
          }
        },
        backgroundColor: Colors.grey[200],
        selectedColor: color.withOpacity(0.3),
        checkmarkColor: color,
        labelStyle: TextStyle(
          color: isSelected ? color : Colors.black,
          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
        ),
      ),
    );
  }

  Widget _buildPodcastCard(Podcast podcast) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: InkWell(
        onTap: () => _navigateToPlayer(podcast),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Cover Image
            Stack(
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(12)),
                  child: CachedNetworkImage(
                    imageUrl: 'http://localhost/${podcast.coverImage}',
                    width: double.infinity,
                    height: 200,
                    fit: BoxFit.cover,
                    placeholder: (context, url) => Container(
                      height: 200,
                      color: Colors.grey[300],
                      child: const Center(child: CircularProgressIndicator()),
                    ),
                  ),
                ),
                
                // Featured Badge
                if (podcast.isFeatured)
                  Positioned(
                    top: 8,
                    right: 8,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.yellow[700],
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(Icons.star, size: 14, color: Colors.white),
                          SizedBox(width: 4),
                          Text(
                            'Featured',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                
                // Play Button Overlay
                Positioned(
                  bottom: 8,
                  right: 8,
                  child: Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFF1DB954),
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.3),
                          blurRadius: 8,
                          spreadRadius: 2,
                        ),
                      ],
                    ),
                    child: IconButton(
                      icon: const Icon(Icons.play_arrow, color: Colors.white, size: 28),
                      onPressed: () => _navigateToPlayer(podcast),
                    ),
                  ),
                ),
              ],
            ),
            
            // Content
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Category
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: _hexToColor(podcast.categoryColor).withOpacity(0.2),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      podcast.categoryName,
                      style: TextStyle(
                        color: _hexToColor(podcast.categoryColor),
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                  
                  const SizedBox(height: 8),
                  
                  // Title
                  Text(
                    podcast.title,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  
                  const SizedBox(height: 4),
                  
                  // Host & Episode
                  if (podcast.host != null || podcast.episodeNumber != null)
                    Text(
                      [
                        if (podcast.host != null) podcast.host!,
                        if (podcast.episodeNumber != null) 'Ep. ${podcast.episodeNumber}',
                      ].join(' • '),
                      style: TextStyle(
                        color: Colors.grey[600],
                        fontSize: 13,
                      ),
                    ),
                  
                  const SizedBox(height: 8),
                  
                  // Stats
                  Row(
                    children: [
                      Icon(Icons.access_time, size: 14, color: Colors.grey[600]),
                      const SizedBox(width: 4),
                      Text(
                        _formatDuration(podcast.duration),
                        style: TextStyle(color: Colors.grey[600], fontSize: 12),
                      ),
                      const SizedBox(width: 16),
                      Icon(Icons.play_circle_outline, size: 14, color: Colors.grey[600]),
                      const SizedBox(width: 4),
                      Text(
                        '${podcast.playCount} plays',
                        style: TextStyle(color: Colors.grey[600], fontSize: 12),
                      ),
                      const Spacer(),
                      Icon(Icons.favorite, size: 14, color: Colors.red[400]),
                      const SizedBox(width: 4),
                      Text(
                        '${podcast.likeCount}',
                        style: TextStyle(color: Colors.grey[600], fontSize: 12),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _fetchAndPlayPodcast(int podcastId) async {
    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );

    final detail = await PodcastApiService.fetchPodcastDetail(
      podcastId: podcastId,
      userId: widget.userId,
    );

    // Close loading
    if (mounted) Navigator.pop(context);

    if (detail != null && mounted) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PodcastPlayerScreen(
            podcast: detail['podcast'] as Podcast,
            userId: widget.userId,
          ),
        ),
      );
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }
}
