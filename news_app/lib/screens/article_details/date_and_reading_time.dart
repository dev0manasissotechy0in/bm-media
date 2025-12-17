import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:news_app/providers/app_settings_provider.dart';

import '../../models/article.dart';
import '../../services/app_service.dart';

class DateAndReadingTime extends ConsumerWidget {
  const DateAndReadingTime({
    super.key,
    required this.article,
    this.color,
    this.bottomPadding,
  });

  final Article article;
  final Color? color;
  final double? bottomPadding;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final settings = ref.read(appSettingsProvider);
    return Padding(
      padding: EdgeInsets.only(bottom: bottomPadding ?? 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: <Widget>[
          Wrap(
            children: [
              Icon(Icons.date_range, size: 20, color: color ?? Colors.grey),
              const SizedBox(
                width: 5,
              ),
              Text(
                AppService.getDate(article.createdAt),
                style: TextStyle(color: color ?? Theme.of(context).colorScheme.secondary),
              ),
            ],
          ),
          settings?.readingTime == false
              ? const SizedBox.shrink()
              : Wrap(
                  children: [
                    const SizedBox(width: 20),
                    Icon(CupertinoIcons.timer, size: 18, color: color ?? Colors.grey),
                    const SizedBox(width: 5),
                    Text(
                      AppService.getReadingTime(article.description),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(fontSize: 14, color: color ?? Theme.of(context).colorScheme.secondary),
                    ),
                  ],
                ),
        ],
      ),
    );
  }
}
