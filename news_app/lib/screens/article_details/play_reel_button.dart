import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../models/article.dart';
import '../../utils/next_screen.dart';
import '../reels/vertical_reel_player.dart';

class PlayReelButton extends ConsumerWidget {
  final Article article;
  
  const PlayReelButton({super.key, required this.article});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    // Only show for reel content type
    if (article.contentType != 'reel') {
      return const SizedBox.shrink();
    }

    // Centered icon button overlay
    return Center(
      child: Material(
        color: Colors.black.withOpacity(0.6),
        shape: const CircleBorder(),
        elevation: 8,
        child: InkWell(
          customBorder: const CircleBorder(),
          onTap: () {
            // Open the reel player
            NextScreen.normal(context, VerticalReelPlayer(initialArticle: article));
          },
          child: Container(
            padding: const EdgeInsets.all(20),
            child: const Icon(
              Icons.play_arrow_rounded,
              color: Colors.white,
              size: 40,
            ),
          ),
        ),
      ),
    );
  }
}
