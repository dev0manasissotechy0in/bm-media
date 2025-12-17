import 'package:feather_icons/feather_icons.dart';
import 'package:flutter/material.dart';
import 'package:news_app/models/article.dart';
import '../../services/app_service.dart';

class PostSourceButton extends StatelessWidget {
  const PostSourceButton({super.key, required this.article, this.iconSize, this.hasCircle, this.leftPadding});

  final Article article;
  final double? iconSize;
  final bool? hasCircle;
  final double? leftPadding;

  @override
  Widget build(BuildContext context) {
    final button = IconButton(
      icon: Icon(FeatherIcons.externalLink, size: iconSize ?? 18),
      onPressed: () => AppService().openLinkWithCustomTab(article.sourceUrl.toString()),
    );
    if (article.sourceUrl?.isEmpty ?? false) {
      if (hasCircle == null || hasCircle == true) {
        return Padding(
          padding: EdgeInsets.only(left: leftPadding ?? 0),
          child: CircleAvatar(backgroundColor: Theme.of(context).cardColor, radius: 18, child: button),
        );
      } else {
        return button;
      }
    }
    return const SizedBox.shrink();
  }
}
