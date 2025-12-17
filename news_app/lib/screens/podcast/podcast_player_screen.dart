import 'package:flutter/material.dart';
import 'package:audioplayers/audioplayers.dart';
import '../../models/podcast.dart';
import '../../services/api_service.dart';

class PodcastPlayerScreen extends StatefulWidget {
  final Podcast podcast;
  final Episode episode;

  const PodcastPlayerScreen({
    Key? key,
    required this.podcast,
    required this.episode,
  }) : super(key: key);

  @override
  State<PodcastPlayerScreen> createState() => _PodcastPlayerScreenState();
}

class _PodcastPlayerScreenState extends State<PodcastPlayerScreen> {
  late AudioPlayer _audioPlayer;
  bool _isPlaying = false;
  Duration _duration = Duration.zero;
  Duration _position = Duration.zero;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _audioPlayer = AudioPlayer();
    _initPlayer();
  }

  Future<void> _initPlayer() async {
    // Load saved progress
    try {
      final api = ApiService();
      final progress = await api.getPodcastProgress(widget.podcast.id, widget.episode.id);
      
      if (progress != null) {
        await _audioPlayer.setSourceUrl(widget.episode.audioUrl);
        await _audioPlayer.seek(Duration(seconds: progress.progressSeconds));
      } else {
        await _audioPlayer.setSourceUrl(widget.episode.audioUrl);
      }
    } catch (e) {
      await _audioPlayer.setSourceUrl(widget.episode.audioUrl);
    }

    // Listen to player state
    _audioPlayer.onDurationChanged.listen((duration) {
      setState(() => _duration = duration);
    });

    _audioPlayer.onPositionChanged.listen((position) {
      setState(() => _position = position);
      _saveProgress(position.inSeconds);
    });

    _audioPlayer.onPlayerStateChanged.listen((state) {
      setState(() {
        _isPlaying = state == PlayerState.playing;
        _isLoading = state == PlayerState.stopped;
      });
    });

    _audioPlayer.onPlayerComplete.listen((event) {
      _markCompleted();
    });

    setState(() => _isLoading = false);
  }

  Future<void> _saveProgress(int seconds) async {
    try {
      final api = ApiService();
      await api.savePodcastProgress(
        widget.podcast.id,
        seconds,
        episodeId: widget.episode.id,
        completed: false,
      );
    } catch (e) {
      // Silently fail
    }
  }

  Future<void> _markCompleted() async {
    try {
      final api = ApiService();
      await api.savePodcastProgress(
        widget.podcast.id,
        widget.episode.duration,
        episodeId: widget.episode.id,
        completed: true,
      );
    } catch (e) {
      // Silently fail
    }
  }

  Future<void> _togglePlayPause() async {
    if (_isPlaying) {
      await _audioPlayer.pause();
    } else {
      await _audioPlayer.resume();
    }
  }

  Future<void> _skip(int seconds) async {
    final newPosition = _position + Duration(seconds: seconds);
    if (newPosition < Duration.zero) {
      await _audioPlayer.seek(Duration.zero);
    } else if (newPosition > _duration) {
      await _audioPlayer.seek(_duration);
    } else {
      await _audioPlayer.seek(newPosition);
    }
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, '0');
    final hours = duration.inHours;
    final minutes = duration.inMinutes.remainder(60);
    final seconds = duration.inSeconds.remainder(60);
    
    if (hours > 0) {
      return '$hours:${twoDigits(minutes)}:${twoDigits(seconds)}';
    }
    return '${twoDigits(minutes)}:${twoDigits(seconds)}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [
              Theme.of(context).primaryColor.withOpacity(0.8),
              Theme.of(context).primaryColor.withOpacity(0.3),
              Colors.black,
            ],
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              // App Bar
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.arrow_back, color: Colors.white),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () {
                        // TODO: Show episode options
                      },
                      icon: const Icon(Icons.more_vert, color: Colors.white),
                    ),
                  ],
                ),
              ),
              
              const Spacer(),
              
              // Podcast Thumbnail
              Container(
                width: 280,
                height: 280,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.3),
                      blurRadius: 20,
                      spreadRadius: 5,
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Image.network(
                    widget.podcast.imageUrl,
                    fit: BoxFit.cover,
                    errorBuilder: (context, error, stackTrace) {
                      return Container(
                        color: Colors.grey[800],
                        child: const Icon(Icons.podcasts, size: 80, color: Colors.white),
                      );
                    },
                  ),
                ),
              ),
              
              const SizedBox(height: 40),
              
              // Episode Title
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32),
                child: Column(
                  children: [
                    Text(
                      widget.episode.title,
                      style: const TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      textAlign: TextAlign.center,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      widget.podcast.title,
                      style: TextStyle(
                        fontSize: 16,
                        color: Colors.white.withOpacity(0.7),
                      ),
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              
              const SizedBox(height: 40),
              
              // Progress Slider
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 32),
                child: Column(
                  children: [
                    SliderTheme(
                      data: SliderThemeData(
                        thumbColor: Colors.white,
                        activeTrackColor: Colors.white,
                        inactiveTrackColor: Colors.white.withOpacity(0.3),
                        trackHeight: 3,
                      ),
                      child: Slider(
                        value: _position.inSeconds.toDouble(),
                        max: _duration.inSeconds.toDouble(),
                        onChanged: (value) {
                          _audioPlayer.seek(Duration(seconds: value.toInt()));
                        },
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            _formatDuration(_position),
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.7),
                              fontSize: 12,
                            ),
                          ),
                          Text(
                            _formatDuration(_duration),
                            style: TextStyle(
                              color: Colors.white.withOpacity(0.7),
                              fontSize: 12,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              
              const SizedBox(height: 24),
              
              // Player Controls
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    onPressed: () => _skip(-15),
                    icon: const Icon(Icons.replay_10, color: Colors.white),
                    iconSize: 40,
                  ),
                  const SizedBox(width: 24),
                  _isLoading
                      ? const CircularProgressIndicator(color: Colors.white)
                      : IconButton(
                          onPressed: _togglePlayPause,
                          icon: Icon(
                            _isPlaying ? Icons.pause_circle_filled : Icons.play_circle_filled,
                            color: Colors.white,
                          ),
                          iconSize: 72,
                        ),
                  const SizedBox(width: 24),
                  IconButton(
                    onPressed: () => _skip(15),
                    icon: const Icon(Icons.forward_10, color: Colors.white),
                    iconSize: 40,
                  ),
                ],
              ),
              
              const Spacer(),
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }
}
