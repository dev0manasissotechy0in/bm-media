import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:news_app/models/app_settings_model.dart';
import 'package:news_app/models/article.dart';
import '../../services/app_service.dart';

class ArticleTileBottom extends StatelessWidget {
  const ArticleTileBottom({super.key, required this.article, required this.settings, this.color});
  final Article article;
  final AppSettingsModel? settings;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: <Widget>[
        settings?.date == false
            ? const SizedBox.shrink()
            : Wrap(
                children: [
                  Icon(CupertinoIcons.time, color: color ?? Colors.grey, size: 18),
                  const SizedBox(width: 3),
                  Text(
                    AppService.getDate(article.createdAt),
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(fontSize: 13, color: color),
                  ),
                ],
              ),
        const Spacer(),
        settings?.views == false
            ? const SizedBox.shrink()
            : Wrap(
                children: [
                  Icon(Icons.remove_red_eye, size: 20, color: color ?? Colors.grey),
                  const SizedBox(width: 3),
                  Text(
                    article.views.toString(),
                    style: TextStyle(fontSize: 13, color: color),
                  ),
                ],
              ),
        settings?.likes == false
            ? const SizedBox.shrink()
            : Wrap(
                children: [
                  const SizedBox(width: 10),
                  Icon(Icons.favorite, size: 20, color: color ?? Colors.grey),
                  const SizedBox(width: 3),
                  Text(article.likes.toString(), style: TextStyle(fontSize: 13, color: color)),
                ],
              ),
        // Comments count
        article.isCommentsEnabled == true
            ? Wrap(
                children: [
                  const SizedBox(width: 10),
                  Icon(CupertinoIcons.chat_bubble, size: 18, color: color ?? Colors.grey),
                  const SizedBox(width: 3),
                  Text((article.comments ?? 0).toString(), style: TextStyle(fontSize: 13, color: color)),
                ],
              )
            : const SizedBox.shrink(),
      ],
    );
  }
}
