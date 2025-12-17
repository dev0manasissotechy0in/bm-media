import 'package:flutter/material.dart';
import 'package:audioplayers/audioplayers.dart';
import 'package:cached_network_image/cached_network_image.dart';
import 'package:share_plus/share_plus.dart';
import '../models/podcast_model.dart';
import '../services/podcast_api_service.dart';

class PodcastPlayerScreen extends StatefulWidget {
  final Podcast podcast;
  final int? userId;

  const PodcastPlayerScreen({
    super.key,
    required this.podcast,
    this.userId,
  });

  @override
  State<PodcastPlayerScreen> createState() => _PodcastPlayerScreenState();
}

class _PodcastPlayerScreenState extends State<PodcastPlayerScreen> {
  final AudioPlayer _audioPlayer = AudioPlayer();
  
  bool _isPlaying = false;
  bool _isLoading = true;
  bool _isLiked = false;
  Duration _currentPosition = Duration.zero;
  Duration _totalDuration = Duration.zero;
  double _playbackSpeed = 1.0;
  double _volume = 1.0;
  String? _guestToken;
  
  final List<double> _speeds = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];

  @override
  void initState() {
    super.initState();
    _initPlayer();
  }

  Future<void> _initPlayer() async {
    try {
      // Get guest token
      _guestToken = await PodcastApiService.getGuestToken();

      // Fetch podcast detail with progress
      final detail = await PodcastApiService.fetchPodcastDetail(
        podcastId: widget.podcast.id,
        userId: widget.userId,
        guestToken: _guestToken,
      );

      if (detail != null) {
        setState(() {
          _isLiked = detail['is_liked'] ?? false;
        });

        // Load progress
        final progress = detail['progress'] as PodcastProgress?;
        if (progress != null) {
          _playbackSpeed = progress.playbackSpeed;
          _volume = progress.volume;
          
          // Set audio source
          await _audioPlayer.setSource(UrlSource('http://localhost/${widget.podcast.audioFile}'));
          await _audioPlayer.setPlaybackRate(_playbackSpeed);
          await _audioPlayer.setVolume(_volume);
          
          // Seek to saved position
          await _audioPlayer.seek(Duration(seconds: progress.currentTime));
        } else {
          // No progress, start from beginning
          await _audioPlayer.setSource(UrlSource('http://localhost/${widget.podcast.audioFile}'));
          await _audioPlayer.setPlaybackRate(_playbackSpeed);
          await _audioPlayer.setVolume(_volume);
        }

        // Setup listeners
        _audioPlayer.onDurationChanged.listen((duration) {
          setState(() {
            _totalDuration = duration;
            _isLoading = false;
          });
        });

        _audioPlayer.onPositionChanged.listen((position) {
          setState(() {
            _currentPosition = position;
          });

          // Save progress every 5 seconds
          if (position.inSeconds % 5 == 0) {
            _saveProgress();
          }
        });

        _audioPlayer.onPlayerStateChanged.listen((state) {
          setState(() {
            _isPlaying = state == PlayerState.playing;
          });
        });

        _audioPlayer.onPlayerComplete.listen((event) {
          setState(() {
            _isPlaying = false;
            _currentPosition = Duration.zero;
          });
          _saveProgress(); // Save final progress
        });
      }
    } catch (e) {
      print('Error initializing player: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _saveProgress() async {
    await PodcastApiService.saveProgress(
      podcastId: widget.podcast.id,
      userId: widget.userId,
      guestToken: _guestToken,
      currentTime: _currentPosition.inSeconds,
      duration: _totalDuration.inSeconds,
      playbackSpeed: _playbackSpeed,
      volume: _volume,
    );
  }

  Future<void> _togglePlayPause() async {
    if (_isPlaying) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.resume();
    }
  }

  Future<void> _seekTo(Duration position) async {
    await _audioPlayer.seek(position);
  }

  Future<void> _skip(int seconds) async {
    final newPosition = _currentPosition + Duration(seconds: seconds);
    if (newPosition < Duration.zero) {
      await _seekTo(Duration.zero);
    } else if (newPosition > _totalDuration) {
      await _seekTo(_totalDuration);
    } else {
      await _seekTo(newPosition);
    }
  }

  void _changeSpeed() {
    final currentIndex = _speeds.indexOf(_playbackSpeed);
    final nextIndex = (currentIndex + 1) % _speeds.length;
    final newSpeed = _speeds[nextIndex];
    
    setState(() {
      _playbackSpeed = newSpeed;
    });
    
    _audioPlayer.setPlaybackRate(newSpeed);
    _saveProgress();
  }

  Future<void> _changeVolume(double volume) async {
    setState(() {
      _volume = volume;
    });
    await _audioPlayer.setVolume(volume);
  }

  Future<void> _toggleLike() async {
    final result = await PodcastApiService.toggleLike(
      podcastId: widget.podcast.id,
      userId: widget.userId,
      guestToken: _guestToken,
    );

    if (result != null) {
      setState(() {
        _isLiked = result['liked'];
      });

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_isLiked ? 'Added to favorites' : 'Removed from favorites'),
          duration: const Duration(seconds: 1),
        ),
      );
    }
  }

  void _share() {
    Share.share(
      'Listen to "${widget.podcast.title}" on our app!',
      subject: widget.podcast.title,
    );
  }

  String _formatDuration(Duration duration) {
    final hours = duration.inHours;
    final minutes = duration.inMinutes.remainder(60);
    final seconds = duration.inSeconds.remainder(60);

    if (hours > 0) {
      return '${hours}:${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
    }
    return '${minutes}:${seconds.toString().padLeft(2, '0')}';
  }

  Color _hexToColor(String hexString) {
    final buffer = StringBuffer();
    if (hexString.length == 7) buffer.write('ff');
    buffer.write(hexString.replaceFirst('#', ''));
    return Color(int.parse(buffer.toString(), radix: 16));
  }

  @override
  void dispose() {
    _saveProgress();
    _audioPlayer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          image: DecorationImage(
            image: CachedNetworkImageProvider(
              'http://localhost/${widget.podcast.coverImage}',
            ),
            fit: BoxFit.cover,
            colorFilter: ColorFilter.mode(
              Colors.black.withOpacity(0.7),
              BlendMode.darken,
            ),
          ),
        ),
        child: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
              colors: [
                Colors.black.withOpacity(0.7),
                Colors.black.withOpacity(0.9),
              ],
            ),
          ),
          child: SafeArea(
            child: Column(
              children: [
                // Top Bar
                _buildTopBar(),
                
                // Content
                Expanded(
                  child: _isLoading
                      ? const Center(child: CircularProgressIndicator())
                      : _buildContent(),
                ),
                
                // Player Controls
                _buildPlayerControls(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildTopBar() {
    return Padding(
      padding: const EdgeInsets.all(16.0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          IconButton(
            icon: const Icon(Icons.arrow_back, color: Colors.white),
            onPressed: () => Navigator.pop(context),
          ),
          IconButton(
            icon: const Icon(Icons.more_vert, color: Colors.white),
            onPressed: () {
              // Show options menu
            },
          ),
        ],
      ),
    );
  }

  Widget _buildContent() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24.0),
      child: Column(
        children: [
          // Cover Image
          Container(
            width: 300,
            height: 300,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.5),
                  blurRadius: 30,
                  spreadRadius: 5,
                ),
              ],
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: CachedNetworkImage(
                imageUrl: 'http://localhost/${widget.podcast.coverImage}',
                fit: BoxFit.cover,
              ),
            ),
          ),
          
          const SizedBox(height: 32),
          
          // Category Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: _hexToColor(widget.podcast.categoryColor),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              widget.podcast.categoryName,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          
          const SizedBox(height: 16),
          
          // Title
          Text(
            widget.podcast.title,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 28,
              fontWeight: FontWeight.bold,
            ),
            textAlign: TextAlign.center,
          ),
          
          const SizedBox(height: 12),
          
          // Host & Episode Info
          if (widget.podcast.host != null || widget.podcast.episodeNumber != null)
            Text(
              [
                if (widget.podcast.host != null) widget.podcast.host!,
                if (widget.podcast.episodeNumber != null)
                  'Episode ${widget.podcast.episodeNumber}',
              ].join(' • '),
              style: TextStyle(
                color: Colors.white.withOpacity(0.7),
                fontSize: 14,
              ),
              textAlign: TextAlign.center,
            ),
          
          const SizedBox(height: 24),
          
          // Description
          Text(
            widget.podcast.description,
            style: TextStyle(
              color: Colors.white.withOpacity(0.8),
              fontSize: 14,
              height: 1.6,
            ),
            textAlign: TextAlign.center,
            maxLines: 4,
            overflow: TextOverflow.ellipsis,
          ),
          
          const SizedBox(height: 24),
          
          // Action Buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildActionButton(
                icon: _isLiked ? Icons.favorite : Icons.favorite_border,
                label: 'Like',
                onTap: _toggleLike,
                color: _isLiked ? Colors.red : null,
              ),
              const SizedBox(width: 16),
              _buildActionButton(
                icon: Icons.share,
                label: 'Share',
                onTap: _share,
              ),
              const SizedBox(width: 16),
              _buildActionButton(
                icon: Icons.download,
                label: 'Download',
                onTap: () {
                  // Handle download
                },
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildActionButton({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
    Color? color,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(25),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          color: color ?? Colors.white.withOpacity(0.1),
          borderRadius: BorderRadius.circular(25),
          border: Border.all(
            color: Colors.white.withOpacity(0.2),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Text(
              label,
              style: const TextStyle(color: Colors.white, fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPlayerControls() {
    return Container(
      padding: const EdgeInsets.all(24.0),
      decoration: BoxDecoration(
        color: Colors.black.withOpacity(0.8),
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Timeline Slider
          Column(
            children: [
              SliderTheme(
                data: SliderTheme.of(context).copyWith(
                  trackHeight: 4,
                  thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 8),
                  overlayShape: const RoundSliderOverlayShape(overlayRadius: 16),
                  activeTrackColor: const Color(0xFF1DB954),
                  inactiveTrackColor: Colors.white.withOpacity(0.2),
                  thumbColor: Colors.white,
                  overlayColor: const Color(0xFF1DB954).withOpacity(0.3),
                ),
                child: Slider(
                  value: _currentPosition.inSeconds.toDouble(),
                  max: _totalDuration.inSeconds.toDouble(),
                  onChanged: (value) {
                    _seekTo(Duration(seconds: value.toInt()));
                  },
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 8.0),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      _formatDuration(_currentPosition),
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.6),
                        fontSize: 12,
                      ),
                    ),
                    Text(
                      _formatDuration(_totalDuration),
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.6),
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          
          const SizedBox(height: 20),
          
          // Main Controls
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: [
              // Volume
              IconButton(
                icon: Icon(
                  _volume == 0 ? Icons.volume_off : Icons.volume_up,
                  color: Colors.white,
                ),
                onPressed: () {
                  showDialog(
                    context: context,
                    builder: (context) => _buildVolumeDialog(),
                  );
                },
              ),
              
              // Rewind 15s
              IconButton(
                icon: const Icon(Icons.replay_15, color: Colors.white, size: 28),
                onPressed: () => _skip(-15),
              ),
              
              // Play/Pause
              Container(
                width: 64,
                height: 64,
                decoration: const BoxDecoration(
                  color: Color(0xFF1DB954),
                  shape: BoxShape.circle,
                ),
                child: IconButton(
                  icon: Icon(
                    _isPlaying ? Icons.pause : Icons.play_arrow,
                    color: Colors.white,
                    size: 32,
                  ),
                  onPressed: _togglePlayPause,
                ),
              ),
              
              // Forward 15s
              IconButton(
                icon: const Icon(Icons.forward_15, color: Colors.white, size: 28),
                onPressed: () => _skip(15),
              ),
              
              // Speed
              InkWell(
                onTap: _changeSpeed,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    '${_playbackSpeed}x',
                    style: const TextStyle(color: Colors.white, fontSize: 14),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildVolumeDialog() {
    return AlertDialog(
      title: const Text('Volume'),
      content: StatefulBuilder(
        builder: (context, setState) {
          return Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Slider(
                value: _volume,
                min: 0,
                max: 1,
                divisions: 10,
                label: '${(_volume * 100).toInt()}%',
                onChanged: (value) {
                  setState(() {
                    _volume = value;
                  });
                  _changeVolume(value);
                  this.setState(() {}); // Update parent state
                },
              ),
              Text('${(_volume * 100).toInt()}%'),
            ],
          );
        },
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Close'),
        ),
      ],
    );
  }
}
