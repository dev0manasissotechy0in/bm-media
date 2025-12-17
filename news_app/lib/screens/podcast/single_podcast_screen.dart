import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/podcast.dart';
import '../../services/api_service.dart';
import 'podcast_player_screen.dart';

class SinglePodcastScreen extends ConsumerStatefulWidget {
  final Podcast podcast;

  const SinglePodcastScreen({Key? key, required this.podcast}) : super(key: key);

  @override
  ConsumerState<SinglePodcastScreen> createState() => _SinglePodcastScreenState();
}

class _SinglePodcastScreenState extends ConsumerState<SinglePodcastScreen> {
  List<Episode> _episodes = [];
  bool _isLoading = true;
  bool _isLiked = false;
  bool _isSaved = false;
  bool _notificationEnabled = false;
  int _likesCount = 0;

  @override
  void initState() {
    super.initState();
    _likesCount = widget.podcast.likesCount;
    _loadPodcastDetails();
  }

  Future<void> _loadPodcastDetails() async {
    setState(() => _isLoading = true);
    try {
      final api = ApiService();
      final details = await api.getSinglePodcast(widget.podcast.id);
      
      setState(() {
        _episodes = details['episodes'] ?? [];
        _isLiked = details['user_liked'] ?? false;
        _isSaved = details['user_saved'] ?? false;
        _notificationEnabled = details['user_notification'] ?? false;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error loading details: $e')),
      );
    }
  }

  Future<void> _toggleLike() async {
    try {
      final api = ApiService();
      final result = await api.likePodcast(widget.podcast.id);
      
      setState(() {
        _isLiked = result['liked'];
        _likesCount = result['likes_count'];
      });
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  Future<void> _toggleSave() async {
    try {
      final api = ApiService();
      final result = await api.savePodcast(widget.podcast.id);
      
      setState(() {
        _isSaved = result['saved'];
      });
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_isSaved ? 'Saved to library' : 'Removed from library')),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  Future<void> _toggleNotification() async {
    try {
      final api = ApiService();
      final result = await api.togglePodcastNotification(widget.podcast.id, !_notificationEnabled);
      
      setState(() {
        _notificationEnabled = result['enabled'];
      });
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_notificationEnabled ? 'Notifications enabled' : 'Notifications disabled')),
      );
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  void _playEpisode(Episode episode) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => PodcastPlayerScreen(
          podcast: widget.podcast,
          episode: episode,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 300,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: Stack(
                fit: StackFit.expand,
                children: [
                  Image.network(
                    widget.podcast.imageUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Container(
                        color: Colors.grey[300],
                        child: const Icon(Icons.podcasts, size: 80),
                      );
                    },
                  ),
                  Container(
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                        colors: [
                          Colors.transparent,
                          Colors.black.withOpacity(0.7),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              title: Text(widget.podcast.title),
            ),
          ),
          
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Description
                  Text(
                    widget.podcast.description,
                    style: const TextStyle(fontSize: 16),
                  ),
                  const SizedBox(height: 16),
                  
                  // Author
                  Row(
                    children: [
                      const Icon(Icons.person, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        widget.podcast.authorName,
                        style: const TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  
                  // Action Buttons
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _toggleLike,
                          icon: Icon(
                            _isLiked ? Icons.favorite : Icons.favorite_border,
                            color: _isLiked ? Colors.red : null,
                          ),
                          label: Text('$_likesCount'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _toggleSave,
                          icon: Icon(
                            _isSaved ? Icons.bookmark : Icons.bookmark_border,
                            color: _isSaved ? Colors.blue : null,
                          ),
                          label: Text(_isSaved ? 'Saved' : 'Save'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _toggleNotification,
                          icon: Icon(
                            _notificationEnabled ? Icons.notifications_active : Icons.notifications_none,
                            color: _notificationEnabled ? Colors.green : null,
                          ),
                          label: Text(_notificationEnabled ? 'On' : 'Off'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  
                  // Episodes Header
                  if (widget.podcast.isSeries) ...[
                    Text(
                      'Episodes (${widget.podcast.totalEpisodes})',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                ],
              ),
            ),
          ),
          
          // Episodes List
          if (_isLoading)
            const SliverFillRemaining(
              child: Center(child: CircularProgressIndicator()),
            )
          else if (widget.podcast.isSeries && _episodes.isNotEmpty)
            SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  final episode = _episodes[index];
                  return _buildEpisodeCard(episode);
                },
                childCount: _episodes.length,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildEpisodeCard(Episode episode) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        onTap: () => _playEpisode(episode),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              // Episode Number
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: Theme.of(context).primaryColor,
                  shape: BoxShape.circle,
                ),
                child: Center(
                  child: Text(
                    '${episode.episodeNumber}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 18,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 16),
              
              // Episode Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      episode.title,
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      episode.description,
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.grey[600],
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      episode.durationFormatted,
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
              
              // Play Button
              IconButton(
                onPressed: () => _playEpisode(episode),
                icon: const Icon(Icons.play_circle_filled, size: 40),
                color: Theme.of(context).primaryColor,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
