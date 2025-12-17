import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:line_icons/line_icons.dart';
import 'package:news_app/constants/app_constants.dart';

import '../models/article.dart';

class MediaIcon extends StatelessWidget {
  final Article article;
  final double iconSize;
  const MediaIcon({super.key, required this.article, required this.iconSize});

  @override
  Widget build(BuildContext context) {
    if (article.contentType == contentTypes.keys.elementAt(1) && (article.videoUrl?.isNotEmpty ?? false)) {
      return Align(
        alignment: Alignment.center,
        child: Icon(FeatherIcons.youtube, color: Colors.white, size: iconSize),
      );
    } else if (article.contentType == contentTypes.keys.elementAt(2) && (article.audioUrl?.isNotEmpty ?? false)) {
      return Align(
        alignment: Alignment.center,
        child: Icon(LineIcons.microphone, color: Colors.white, size: iconSize),
      );
    } else {
      return const SizedBox.shrink();
    }
  }
}
