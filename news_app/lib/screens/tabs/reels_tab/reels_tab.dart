import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../reels/vertical_reel_player.dart';

class ReelsTab extends ConsumerWidget {
  const ReelsTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return const VerticalReelPlayer(showBackButton: false);
  }
}
