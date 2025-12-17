import 'package:flutter/material.dart';
import 'package:flutter_tts/flutter_tts.dart';
import 'package:html/parser.dart' as html_parser;
import '../../models/article.dart';
import '../../services/app_service.dart';

enum TtsState { stopped, playing, paused }

class TtsButton extends StatefulWidget {
  final Article article;
  
  const TtsButton({super.key, required this.article});

  @override
  State<TtsButton> createState() => _TtsButtonState();
}

class _TtsButtonState extends State<TtsButton> {
  final FlutterTts _flutterTts = FlutterTts();
  TtsState _ttsState = TtsState.stopped;
  bool _isLoading = false;
  String? _textToSpeak;

  @override
  void initState() {
    super.initState();
    _initTts();
  }

  Future<void> _initTts() async {
    await _flutterTts.setLanguage("en-US");
    await _flutterTts.setSpeechRate(0.5);
    await _flutterTts.setVolume(1.0);
    await _flutterTts.setPitch(1.0);

    _flutterTts.setCompletionHandler(() {
      if (mounted) {
        setState(() {
          _ttsState = TtsState.stopped;
        });
      }
    });

    _flutterTts.setErrorHandler((msg) {
      if (mounted) {
        setState(() {
          _ttsState = TtsState.stopped;
          _isLoading = false;
        });
      }
      debugPrint('TTS Error: $msg');
    });
  }

  String _extractTextFromHtml(String htmlContent) {
    final document = html_parser.parse(htmlContent);
    return document.body?.text ?? '';
  }

  String _prepareText() {
    if (_textToSpeak != null) return _textToSpeak!;
    
    String textToSpeak = '';
    
    // Use content field (full article) instead of description (summary)
    final contentToRead = widget.article.content ?? widget.article.description;
    
    if (contentToRead.isNotEmpty) {
      String plainText = _extractTextFromHtml(contentToRead);
      textToSpeak = plainText;
    }

    if (textToSpeak.isEmpty) {
      textToSpeak = 'No content available to read.';
    }
    
    _textToSpeak = textToSpeak;
    return textToSpeak;
  }

  Future<void> _play() async {
    setState(() {
      _isLoading = true;
    });

    try {
      String text = _prepareText();
      setState(() {
        _isLoading = false;
        _ttsState = TtsState.playing;
      });
      await _flutterTts.speak(text);
    } catch (e) {
      setState(() {
        _isLoading = false;
        _ttsState = TtsState.stopped;
      });
      debugPrint('Error speaking: $e');
      
      // Show user-friendly error message
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: const Text(
              'Text-to-Speech requires app rebuild. Please stop and restart the app completely.',
              style: TextStyle(color: Colors.white),
            ),
            backgroundColor: Colors.orange,
            duration: const Duration(seconds: 5),
            action: SnackBarAction(
              label: 'OK',
              textColor: Colors.white,
              onPressed: () {},
            ),
          ),
        );
      }
    }
  }

  Future<void> _pause() async {
    await _flutterTts.pause();
    setState(() {
      _ttsState = TtsState.paused;
    });
  }

  Future<void> _resume() async {
    // Note: Some platforms don't support resume, will restart instead
    if (_ttsState == TtsState.paused) {
      await _play();
    }
  }

  Future<void> _stop() async {
    await _flutterTts.stop();
    setState(() {
      _ttsState = TtsState.stopped;
    });
  }

  @override
  void dispose() {
    _flutterTts.stop();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: Theme.of(context).primaryColor.withOpacity(0.3),
          width: 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: Theme.of(context).primaryColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  Icons.record_voice_over,
                  color: Theme.of(context).primaryColor,
                  size: 24,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Listen to Article',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _ttsState == TtsState.playing
                          ? 'Playing...'
                          : _ttsState == TtsState.paused
                              ? 'Paused'
                              : 'Text-to-Speech',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: Colors.grey[600],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          
          // Controls
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              // Play/Pause Button
              if (_ttsState == TtsState.stopped)
                _buildControlButton(
                  icon: Icons.play_arrow,
                  label: 'Play',
                  onPressed: _isLoading ? null : _play,
                  isPrimary: true,
                  isLoading: _isLoading,
                )
              else if (_ttsState == TtsState.playing)
                _buildControlButton(
                  icon: Icons.pause,
                  label: 'Pause',
                  onPressed: _pause,
                  isPrimary: true,
                )
              else
                _buildControlButton(
                  icon: Icons.play_arrow,
                  label: 'Resume',
                  onPressed: _resume,
                  isPrimary: true,
                ),
              
              const SizedBox(width: 12),
              
              // Stop Button
              if (_ttsState != TtsState.stopped)
                _buildControlButton(
                  icon: Icons.stop,
                  label: 'Stop',
                  onPressed: _stop,
                  isPrimary: false,
                ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildControlButton({
    required IconData icon,
    required String label,
    required VoidCallback? onPressed,
    required bool isPrimary,
    bool isLoading = false,
  }) {
    return Expanded(
      child: Material(
        color: isPrimary
            ? Theme.of(context).primaryColor
            : Colors.grey[300],
        borderRadius: BorderRadius.circular(12),
        elevation: isPrimary ? 2 : 0,
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: onPressed,
          child: Container(
            padding: const EdgeInsets.symmetric(vertical: 14),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (isLoading)
                  SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 2,
                    ),
                  )
                else
                  Icon(
                    icon,
                    color: isPrimary ? Colors.white : Colors.grey[700],
                    size: 22,
                  ),
                const SizedBox(width: 8),
                Text(
                  label,
                  style: TextStyle(
                    color: isPrimary ? Colors.white : Colors.grey[700],
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
