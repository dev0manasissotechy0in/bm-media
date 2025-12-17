import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:just_audio/just_audio.dart';
import 'package:line_icons/line_icons.dart';
import 'package:lottie/lottie.dart';
import 'package:news_app/configs/app_assets.dart';
import 'package:news_app/utils/loading_widget.dart';
import '../../../../services/audio_service.dart';

class AudioPlayerWidget extends ConsumerStatefulWidget {
  final String audioUrl;

  const AudioPlayerWidget({super.key, required this.audioUrl});

  @override
  ConsumerState createState() => _AudioPlayerWidgetState();
}

class _AudioPlayerWidgetState extends ConsumerState<AudioPlayerWidget> {
  bool _isLoaded = false;
  final AudioPlayer _audioPlayer = AudioPlayer();

  @override
  void initState() {
    super.initState();
    _initAudioPlayer();
  }

  Future<void> _initAudioPlayer() async {
    final String? url = await AudioService.getStreamUrl(widget.audioUrl);
    if (url != null) {
      await _audioPlayer.setUrl(url);
      _isLoaded = true;
      _audioPlayer.play();
    } else {
      debugPrint('failed to load podcast');
    }
  }

  @override
  void dispose() {
    _audioPlayer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SizedBox(
          height: 60,
          child: StreamBuilder<PlayerState>(
            stream: _audioPlayer.playerStateStream,
            builder: (context, snapshot) {
              final playerState = snapshot.data;
              final processingState = playerState?.processingState;
              final playing = playerState?.playing;

              if (processingState == ProcessingState.loading || processingState == ProcessingState.buffering || !_isLoaded) {
                return const LoadingIndicatorWidget(color: Colors.white);
              } else if (playing != true) {
                return IconButton(
                  icon: const Icon(LineIcons.play),
                  iconSize: 50.0,
                  onPressed: _audioPlayer.play,
                  color: Colors.white,
                );
              } else if (processingState != ProcessingState.completed) {
                return IconButton(
                  icon: const Icon(LineIcons.pause),
                  iconSize: 50.0,
                  onPressed: _audioPlayer.pause,
                  color: Colors.white,
                );
              } else {
                return IconButton(
                  icon: const Icon(Icons.replay),
                  iconSize: 50.0,
                  color: Colors.white,
                  onPressed: () {
                    _audioPlayer.seek(Duration.zero);
                    _audioPlayer.play();
                  },
                );
              }
            },
          ),
        ),
        SizedBox(
          // height: 70,
          child: StreamBuilder<Duration>(
            stream: _audioPlayer.positionStream,
            builder: (context, snapshot) {
              final position = snapshot.data ?? Duration.zero;
              final duration = _audioPlayer.duration ?? Duration.zero;
              if (duration == Duration.zero) {
                return Container();
              }
              return Column(
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 0),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          _formatDuration(position), // Display current position
                          style: Theme.of(context).textTheme.labelMedium?.copyWith(color: Colors.white),
                        ),
                        Text(
                          _formatDuration(duration), // Display full duration
                          style: Theme.of(context).textTheme.labelMedium?.copyWith(color: Colors.white),
                        ),
                      ],
                    ),
                  ),
                  Slider(
                    activeColor: Colors.white,
                    value: position.inMilliseconds.toDouble().clamp(0.0, duration.inMilliseconds.toDouble()),
                    onChanged: (value) {
                      _audioPlayer.seek(Duration(milliseconds: value.toInt()));
                    },
                    min: 0.0,
                    max: duration.inMilliseconds.toDouble(),
                  ),
                  Container(
                    height: 70,
                    width: MediaQuery.sizeOf(context).width,
                    color: Colors.transparent,
                    child: LottieBuilder.asset(
                      waveAnimation,
                      animate: _audioPlayer.playing && _audioPlayer.processingState != ProcessingState.completed,
                      height: 70,
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }

  String _formatDuration(Duration duration) {
    String twoDigits(int n) => n.toString().padLeft(2, "0");
    String twoDigitMinutes = twoDigits(duration.inMinutes.remainder(60));
    String twoDigitSeconds = twoDigits(duration.inSeconds.remainder(60));
    return "$twoDigitMinutes:$twoDigitSeconds";
  }
}
