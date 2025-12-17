import 'package:flutter/material.dart';
import '../../models/article.dart';

class ArticleSummary extends StatelessWidget {
  const ArticleSummary({super.key, required this.article});

  final Article article;

  @override
  Widget build(BuildContext context) {
    return Visibility(
      visible: article.summary?.isNotEmpty ?? false,
      child: IntrinsicHeight(
        child: Row(
          children: [
            const VerticalDivider(thickness: 3),
            Expanded(
              child: Text(
                article.summary.toString(),
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontSize: 18, fontWeight: FontWeight.w500,
                  height: 1.7
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
